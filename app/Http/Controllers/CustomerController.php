<?php

namespace App\Http\Controllers;

use App\Exports\CustomerTemplateExport;
use App\Exports\CustomerImportErrorExport;
use App\Imports\CustomerImport;
use App\Models\Customer;
use App\Models\Area;
use App\Models\CustomerImportHistory;
use App\Models\Invoice;
use App\Models\Setting;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{
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

    private function resolveTaxType(?string $customerPosCode): string
    {
        $firmCode = $this->firmStateCode();
        $customerCode = $this->normalizeStateCode($customerPosCode) ?: $firmCode;

        if ($customerCode !== $firmCode) {
            return 'IGST';
        }

        return $firmCode === '26' ? 'CGST_UTGST' : 'CGST_SGST';
    }

    private function resolveAreaId(?string $areaName): ?int
    {
        $name = trim((string) $areaName);
        if ($name === '') {
            return null;
        }

        $area = Area::firstOrCreate(['name' => $name]);
        return $area->id;
    }

    public function index(Request $request)
    {
        $areas = Area::orderBy('name')->get();
        $importHistories = CustomerImportHistory::orderByDesc('uploaded_at')->limit(10)->get();
        return view('customers.index', compact('areas', 'importHistories'));
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $perPage = (int) $request->query('per_page', 100);
        if ($perPage <= 0) {
            $perPage = 100;
        }
        if ($perPage > 100000) {
            $perPage = 100000;
        }
        $page = max(1, (int) $request->query('page', 1));

        $outstandingSub = Invoice::query()
            ->active()
            ->whereNull('deleted_at')
            ->selectRaw('customer_id, COALESCE(SUM(pending_amount), 0) as outstanding_amount')
            ->groupBy('customer_id');

        $query = Customer::query()
            ->leftJoinSub($outstandingSub, 'inv_sum', function ($join) {
                $join->on('customers.id', '=', 'inv_sum.customer_id');
            })
            ->select('customers.*')
            ->selectRaw('COALESCE(inv_sum.outstanding_amount, 0) as outstanding_amount')
            ->when($q !== '', function ($qbuilder) use ($q) {
                $qbuilder->where(function ($sq) use ($q) {
                    $sq->where('customers.name', 'like', '%' . $q . '%')
                        ->orWhere('customers.mobile', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('customers.name')
            ->orderBy('customers.id');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $data = collect($paginator->items())->map(function (Customer $c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'code' => $c->code,
                'gstin' => $c->gstin,
                'mobile' => $c->mobile,
                'state' => $c->state,
                'tax_type' => $c->tax_type,
                'credit_limit' => $c->credit_limit,
                'outstanding_amount' => $c->outstanding_amount ?? 0,
                'is_active' => (bool) $c->is_active,
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
                'next_page' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
            ],
        ]);
    }

    public function downloadTemplate()
    {
        return Excel::download(new CustomerTemplateExport(), 'customer_excel_template.xlsx');
    }

    public function uploadExcel(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,csv',
        ]);

        $token = (string) Str::uuid();
        $ext = strtolower($validated['file']->getClientOriginalExtension() ?: 'xlsx');
        $relativePath = $validated['file']->storeAs('imports/customers', $token . '.' . $ext);

        $history = CustomerImportHistory::create([
            'token' => $token,
            'file_name' => $validated['file']->getClientOriginalName(),
            'stored_file_path' => $relativePath,
            'status' => 'preview',
            'uploaded_at' => now(),
        ]);

        $import = new CustomerImport(true, 'preview');
        Excel::import($import, Storage::path($relativePath));

        $errorRows = [];
        $errors = [];
        foreach ($import->previewRows as $r) {
            $errs = $r['errors'] ?? [];
            if (!empty($errs)) {
                $errors[] = ['row' => $r['row'], 'message' => implode(' ', $errs)];
                $data = $r['data'] ?? [];
                $data['error_message'] = implode(' ', $errs);
                $errorRows[] = $data;
            }
        }

        $history->update([
            'created_count' => $import->created,
            'updated_count' => $import->updated,
            'imported_count' => $import->created + $import->updated,
            'failed_count' => $import->skipped,
            'duplicate_count' => $import->duplicates,
            'errors' => $errors,
            'error_rows' => $errorRows,
        ]);

        return view('customers.import-preview', [
            'token' => $token,
            'rows' => $import->previewRows,
            'summary' => [
                'created' => $import->created,
                'updated' => $import->updated,
                'skipped' => $import->skipped,
                'duplicates' => $import->duplicates,
            ],
        ]);
    }

    public function downloadErrorExcel(string $token)
    {
        $history = CustomerImportHistory::where('token', $token)->first();
        $rows = $history?->error_rows ?? [];
        if (empty($rows)) {
            return redirect()->route('app.customers.index')->with('error', 'Error file not available. Please upload again.');
        }

        return Excel::download(new CustomerImportErrorExport($rows), 'customer_import_errors.xlsx');
    }

    public function commitUpload(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'duplicate_mode' => 'required|in:skip,update',
        ]);

        $token = $validated['token'];
        $history = CustomerImportHistory::where('token', $token)->first();
        $relativePath = $history?->stored_file_path;
        if (!$history || !$relativePath || !Storage::exists($relativePath)) {
            if ($history) {
                $history->update(['status' => 'expired']);
            }
            return redirect()->route('app.customers.index')->with('error', 'Upload session expired. Please upload again.');
        }

        $import = new CustomerImport(false, $validated['duplicate_mode']);
        Excel::import($import, Storage::path($relativePath));

        Storage::delete($relativePath);

        $errorRows = [];
        $errors = [];
        foreach ($import->previewRows as $r) {
            $errs = $r['errors'] ?? [];
            if (empty($errs)) {
                continue;
            }
            $data = $r['data'] ?? [];
            $data['error_message'] = implode(' ', $errs);
            $errorRows[] = $data;
            $errors[] = ['row' => $r['row'], 'message' => implode(' ', $errs)];
        }

        $imported = $import->created + $import->updated;
        $failed = $import->skipped;

        $history->update([
            'stored_file_path' => null,
            'status' => 'completed',
            'duplicate_mode' => $validated['duplicate_mode'],
            'created_count' => $import->created,
            'updated_count' => $import->updated,
            'imported_count' => $imported,
            'failed_count' => $failed,
            'duplicate_count' => $import->duplicates,
            'errors' => $errors,
            'error_rows' => $errorRows,
            'completed_at' => now(),
        ]);

        $redirect = redirect()->route('app.customers.index')
            ->with('success', "Customer import completed. Imported: {$imported}, Failed: {$failed}.")
            ->with('import_summary', [
                'created' => $import->created,
                'updated' => $import->updated,
                'imported' => $imported,
                'failed' => $failed,
                'skipped' => $import->skipped,
            ]);

        if (!empty($errors)) {
            $redirect = $redirect->with('import_errors', $errors);
        }
        if (!empty($errorRows)) {
            $redirect = $redirect->with('import_error_token', $token);
        }

        return $redirect;
    }

    public function cancelUpload(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
        ]);

        $token = $validated['token'];
        $history = CustomerImportHistory::where('token', $token)->first();
        $relativePath = $history?->stored_file_path;
        if ($relativePath && Storage::exists($relativePath)) {
            Storage::delete($relativePath);
        }
        if ($history) {
            $history->update(['stored_file_path' => null, 'status' => 'cancelled']);
        }

        return redirect()->route('app.customers.index')->with('success', 'Upload cancelled.');
    }

    public function importHistory()
    {
        $imports = CustomerImportHistory::orderByDesc('uploaded_at')->paginate(20);
        return view('customers.import-history', compact('imports'));
    }

    public function importHistoryShow(string $token)
    {
        $import = CustomerImportHistory::where('token', $token)->firstOrFail();
        return view('customers.import-history-show', compact('import'));
    }

    public function store(StoreCustomerRequest $request)
    {
        $validated = $request->validated();

        if (array_key_exists('area_name', $validated)) {
            $validated['area_id'] = $this->resolveAreaId($validated['area_name']);
            unset($validated['area_name']);
        }

        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
        $validated['pos_code'] = $validated['pos_code'] ?? $this->firmStateCode();
        $validated['tax_type'] = $this->resolveTaxType($validated['pos_code'] ?? null);

        Customer::create($validated);
        
        return redirect()->route('app.customers.index')->with('success', 'Customer created successfully.');
    }

    public function edit($id)
    {
        $customer = Customer::with('area')->findOrFail($id);
        $data = $customer->toArray();
        $data['area_name'] = $customer->area?->name ?? '';
        $data['outstanding_amount'] = (float) Invoice::query()
            ->active()
            ->whereNull('deleted_at')
            ->where('customer_id', $customer->id)
            ->sum('pending_amount');
        return response()->json($data);
    }

    public function update(UpdateCustomerRequest $request, $id)
    {
        $customer = Customer::findOrFail($id);
        $validated = $request->validated();

        if (array_key_exists('area_name', $validated)) {
            $validated['area_id'] = $this->resolveAreaId($validated['area_name']);
            unset($validated['area_name']);
        }

        $validated['is_active'] = (bool) ($validated['is_active'] ?? $customer->is_active);
        $validated['pos_code'] = $validated['pos_code'] ?? $customer->pos_code ?? $this->firmStateCode();
        $validated['tax_type'] = $this->resolveTaxType($validated['pos_code'] ?? null);

        $customer->update($validated);
        
        return redirect()->route('app.customers.index')->with('success', 'Customer updated successfully.');
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();
        return redirect()->route('app.customers.index')->with('success', 'Customer deleted successfully.');
    }

    public function toggle($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->is_active = !$customer->is_active;
        $customer->save();
        return back()->with('success', 'Status updated.');
    }
}
