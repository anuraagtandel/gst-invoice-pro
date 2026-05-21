<?php

namespace App\Imports;

use App\Models\Area;
use App\Models\Customer;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CustomerImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    private const GSTIN_REGEX = '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/';
    private const GSTIN_EMPTY_VALUES = ['NA', 'N/A', 'NONE', 'NULL', 'NIL', '-', '--', '0'];

    public bool $dryRun;
    public string $duplicateMode;
    public int $created = 0;
    public int $updated = 0;
    public int $skipped = 0;
    public int $duplicates = 0;
    public array $errors = [];
    public array $previewRows = [];

    public function __construct(bool $dryRun = false, string $duplicateMode = 'skip')
    {
        $this->dryRun = $dryRun;
        $this->duplicateMode = $duplicateMode;
    }

    public function collection(Collection $rows)
    {
        $firmCode = $this->firmStateCode();
        $seenCodes = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $data = is_array($row) ? $row : $row->toArray();

            $payload = [
                'name' => $this->upper($this->get($data, ['business_name', 'name'])),
                'mobile' => $this->digits($this->get($data, ['mobile_number', 'mobile'])),
                'code' => $this->upper($this->get($data, ['customer_code', 'code'])),
                'address' => $this->upper($this->get($data, ['billing_address', 'address'])),
                'gstin' => $this->upper($this->get($data, ['gstin'])),
                'city' => $this->upper($this->get($data, ['city'])),
                'area_name' => $this->upper($this->get($data, ['area'])),
                'state' => $this->upper($this->get($data, ['state'])),
                'pos_code' => $this->normalizeStateCode($this->get($data, ['pos_code_state_code', 'pos_code', 'pos_code_state'])),
                'fssai_no' => $this->upper($this->get($data, ['fssai_no', 'fssai'])),
            ];
            $payload['gstin'] = $this->normalizeGstin($payload['gstin']);

            $rowErrors = [];
            $validator = Validator::make($payload, [
                'name' => ['required', 'string', 'max:150'],
                'mobile' => ['required', 'string', 'regex:/^\d{10}$/'],
                'code' => ['required', 'string', 'max:150'],
                'address' => ['required', 'string'],
                'gstin' => ['nullable', 'string', 'max:20'],
                'city' => ['required', 'string', 'max:150'],
                'area_name' => ['required', 'string', 'max:100'],
                'state' => ['required', 'string', 'max:150'],
                'pos_code' => ['required', 'string', 'max:2'],
                'fssai_no' => ['nullable', 'string', 'max:50'],
            ]);

            if ($validator->fails()) {
                $rowErrors = $validator->errors()->all();
                $this->errors[] = ['row' => $rowNumber, 'message' => implode(' ', $rowErrors)];
                $this->skipped++;
                $this->previewRows[] = [
                    'row' => $rowNumber,
                    'data' => $payload,
                    'action' => 'SKIP',
                    'errors' => $rowErrors,
                    'warnings' => [],
                ];
                continue;
            }

            if (!empty($payload['code'])) {
                if (isset($seenCodes[$payload['code']])) {
                    $rowErrors[] = 'Duplicate customer code in uploaded file.';
                    $this->errors[] = ['row' => $rowNumber, 'message' => implode(' ', $rowErrors)];
                    $this->skipped++;
                    $this->previewRows[] = [
                        'row' => $rowNumber,
                        'data' => $payload,
                        'action' => 'SKIP',
                        'errors' => $rowErrors,
                        'warnings' => [],
                    ];
                    continue;
                }
                $seenCodes[$payload['code']] = true;
            }

            $posCode = $payload['pos_code'] ?: $firmCode;
            $payload['pos_code'] = $posCode;
            $payload['tax_type'] = $this->resolveTaxType($firmCode, $posCode);
            $payload['is_active'] = true;
            $rowWarnings = [];

            if (empty($payload['gstin'])) {
                $payload['gst_status'] = 'NON_GST';
                $display = array_merge($payload, ['gst_status' => 'Non-GST']);
            } elseif (preg_match(self::GSTIN_REGEX, (string) $payload['gstin'])) {
                $payload['gst_status'] = 'GST_REGISTERED';
                $display = array_merge($payload, ['gst_status' => 'GST Registered']);
            } else {
                $payload['gst_status'] = 'INVALID_GST';
                $rowWarnings[] = 'Invalid GSTIN format';
                $display = array_merge($payload, ['gst_status' => 'Invalid GST']);
            }

            if ($this->dryRun) {
                $payload['area_id'] = null;
            } else {
                $area = Area::firstOrCreate(['name' => $payload['area_name']]);
                $payload['area_id'] = $area->id;
            }
            unset($payload['area_name']);

            $existingByCode = Customer::withTrashed()->where('code', $payload['code'])->first();

            if ($existingByCode) {
                if ($existingByCode->trashed()) {
                    if (!$this->dryRun) {
                        $existingByCode->restore();
                    }
                }

                if (!$this->dryRun) {
                    $existingByCode->update($payload);
                }
                $this->updated++;
                $this->previewRows[] = [
                    'row' => $rowNumber,
                    'data' => $display,
                    'action' => 'UPDATE',
                    'errors' => [],
                    'warnings' => $rowWarnings,
                ];
                continue;
            }

            $codeValidator = Validator::make($payload, [
                'code' => ['required', Rule::unique('customers', 'code')],
            ]);
            if ($codeValidator->fails()) {
                $rowErrors = $codeValidator->errors()->all();
                $this->errors[] = ['row' => $rowNumber, 'message' => implode(' ', $rowErrors)];
                $this->skipped++;
                $this->previewRows[] = [
                    'row' => $rowNumber,
                    'data' => $display,
                    'action' => 'SKIP',
                    'errors' => $rowErrors,
                    'warnings' => [],
                ];
                continue;
            }

            if (!$this->dryRun) {
                Customer::create($payload);
            }
            $this->created++;
            $this->previewRows[] = [
                'row' => $rowNumber,
                'data' => $display,
                'action' => 'CREATE',
                'errors' => [],
                'warnings' => $rowWarnings,
            ];
        }
    }

    private function get(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null && trim((string) $row[$key]) !== '') {
                return $row[$key];
            }
        }
        return null;
    }

    private function upper(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $v = trim((string) $value);
        return $v === '' ? null : strtoupper($v);
    }

    private function normalizeGstin(?string $gstin): ?string
    {
        $v = $this->upper($gstin);
        if ($v === null) {
            return null;
        }
        return in_array($v, self::GSTIN_EMPTY_VALUES, true) ? null : $v;
    }

    private function digits(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $v = preg_replace('/\D+/', '', (string) $value);
        return $v === '' ? null : $v;
    }

    private function normalizeStateCode(?string $code): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $code);
        if ($digits === '') {
            return null;
        }
        if (strlen($digits) === 1) {
            return '0' . $digits;
        }
        return substr($digits, 0, 2);
    }

    private function firmStateCode(): string
    {
        $gstin = trim((string) Setting::get('firm_gstin', '26'));
        $fromGstin = $this->normalizeStateCode(substr($gstin, 0, 2));
        return $fromGstin ?: '26';
    }

    private function resolveTaxType(string $firmCode, string $customerPosCode): string
    {
        if ($customerPosCode !== $firmCode) {
            return 'IGST';
        }

        return $firmCode === '26' ? 'CGST_UTGST' : 'CGST_SGST';
    }
}
