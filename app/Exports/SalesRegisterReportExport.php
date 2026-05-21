<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SalesRegisterReportExport implements FromGenerator, WithEvents, ShouldAutoSize
{
    public function __construct(
        private Carbon $from,
        private Carbon $to,
        private array $supplier,
        private array $tableHeadings,
        private array $meta,
        private array $qtyCols,
        private array $currencyCols,
        private array $percentCols,
        private int $srNoCol,
        private int $productDescCol
    ) {
    }

    private int $dataRowCount = 0;
    private int $processedRowCount = 0;
    private string $lastInvoiceNo = '';
    private int $lastInvoiceId = 0;

    private function padRow(array $row, int $cols): array
    {
        $out = $row;
        $missing = $cols - count($out);
        if ($missing > 0) {
            $out = array_merge($out, array_fill(0, $missing, ''));
        }
        return array_slice($out, 0, $cols);
    }

    private function normalizeSchemeRows(array $row): array
    {
        // 1-based columns in spec:
        // Sr No. = 5, Product Description = 16, UNIT = 22, CT = 23
        // Tax/GST/value range = 28..35
        $sr = $row[4] ?? null;
        $desc = trim((string) ($row[15] ?? ''));
        $unit = $row[21] ?? 0;
        $ct = $row[22] ?? 0;
        $taxable = $row[27] ?? null;
        $invoiceValue = $row[34] ?? null;

        $srNum = is_numeric($sr) ? (int) $sr : null;
        $unitNum = is_numeric($unit) ? (float) $unit : 0.0;
        $ctNum = is_numeric($ct) ? (float) $ct : 0.0;
        $taxableNum = is_numeric($taxable) ? (float) $taxable : null;
        $invoiceNum = is_numeric($invoiceValue) ? (float) $invoiceValue : null;

        $isFreeLikeRow = $srNum !== null
            && $srNum > 0
            && $desc !== 'TOTAL'
            && (($unitNum > 0) || ($ctNum > 0))
            && ($taxableNum === null || abs($taxableNum) < 1e-9)
            && ($invoiceNum === null || abs($invoiceNum) < 1e-9);

        if ($isFreeLikeRow) {
            for ($i = 27; $i <= 34; $i++) {
                $row[$i] = '0';
            }
        }

        return $row;
    }

    private function normalizeStateCode($v): string
    {
        return preg_replace('/\D/', '', (string) ($v ?? ''));
    }

    public function generator(): \Generator
    {
        $cols = count($this->tableHeadings);

        yield $this->padRow([(string) ($this->meta['company'] ?? '')], $cols);
        yield $this->padRow([(string) ($this->meta['report'] ?? '')], $cols);
        yield $this->padRow([(string) ($this->meta['date_range'] ?? '')], $cols);
        yield $this->padRow([(string) ($this->meta['generated_line'] ?? '')], $cols);
        yield $this->padRow([''], $cols);
        yield $this->padRow($this->tableHeadings, $cols);

        $supplierStateNorm = $this->normalizeStateCode($this->supplier['supplier_state_code_norm'] ?? ($this->supplier['supplier_state_code'] ?? ''));

        $currentInvoice = null;
        $sr = 0;
        $invTotals = [
            'ct' => 0,
            'total_mrp' => 0.0,
            'taxable' => 0.0,
            'cgst' => 0.0,
            'sgst' => 0.0,
            'igst' => 0.0,
            'grand_total' => 0.0,
            'meta' => [],
            'same_state' => true,
        ];

        $pushTotalRow = function () use (&$invTotals) {
            if ($invTotals['meta'] === []) {
                return null;
            }

            $m = $invTotals['meta'];
            $sameState = (bool) ($invTotals['same_state'] ?? true);

            return [
                ($this->supplier['supplier_name'] ?? '') !== '' ? (string) $this->supplier['supplier_name'] : '-',
                ($this->supplier['supplier_gstin'] ?? '') !== '' ? (string) $this->supplier['supplier_gstin'] : '-',
                ($this->supplier['supplier_state_code'] ?? '') !== '' ? (string) $this->supplier['supplier_state_code'] : '-',
                ($this->supplier['supplier_state'] ?? '') !== '' ? (string) $this->supplier['supplier_state'] : '-',
                0,
                $m['invoice_no'],
                $m['invoice_date'],
                $m['customer_code'],
                $m['customer_name'],
                $m['customer_gstin'],
                $m['customer_state_code'],
                $m['customer_state'],
                $m['invoice_type'],
                $m['salesman'],
                '-',
                'TOTAL',
                '-',
                '-',
                '-',
                '-',
                '-',
                '',
                $invTotals['ct'],
                '',
                '',
                $invTotals['total_mrp'],
                '',
                $invTotals['taxable'],
                '',
                $invTotals['cgst'],
                '',
                $sameState ? $invTotals['sgst'] : '-',
                '',
                $sameState ? '-' : $invTotals['igst'],
                $invTotals['grand_total'],
            ];
        };

        $query = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->leftJoin('products', 'invoice_items.product_id', '=', 'products.id')
            ->where('invoices.is_deleted', false)
            ->whereNull('invoices.deleted_at')
            ->whereBetween('invoices.invoice_date', [$this->from, $this->to])
            ->select([
                'invoices.id as invoice_id',
                'invoices.invoice_no',
                'invoices.invoice_date',
                'invoices.salesman',
                'invoices.grand_total',
                'customers.code as customer_code',
                'customers.name as customer_name',
                'customers.gstin as customer_gstin',
                'customers.pos_code as customer_pos_code',
                'customers.state_code as customer_state_code',
                'customers.state as customer_state',
                'invoice_items.is_free',
                'invoice_items.qty_un',
                'invoice_items.qty_ct',
                'invoice_items.total_units',
                'invoice_items.mrp',
                'invoice_items.taxable',
                'invoice_items.line_total',
                'invoice_items.hsn_code',
                'invoice_items.product_description',
                'products.product_code as product_code',
                'products.name as product_name',
                'products.volume as product_volume',
                'products.category as product_category',
                'products.brand as product_brand',
                'products.pack_type as product_pack_type',
                'products.pack_size as product_pack_size',
                'products.trade_price as product_trade_price',
                'products.gst_rate as product_gst_rate',
            ])
            ->orderBy('invoices.invoice_date')
            ->orderBy('invoices.id')
            ->orderBy('invoice_items.id');

        foreach ($query->cursor() as $r) {
            try {
                $invoiceId = (int) ($r->invoice_id ?? 0);

                if ($currentInvoice === null) {
                    $currentInvoice = $invoiceId;
                    $sr = 0;
                    $invTotals = [
                        'ct' => 0,
                        'total_mrp' => 0.0,
                        'taxable' => 0.0,
                        'cgst' => 0.0,
                        'sgst' => 0.0,
                        'igst' => 0.0,
                        'grand_total' => (float) ($r->grand_total ?? 0),
                        'meta' => [],
                        'same_state' => true,
                    ];
                }

                if ($invoiceId !== $currentInvoice) {
                    $totalRow = $pushTotalRow();
                    if (is_array($totalRow)) {
                        $this->dataRowCount++;
                        $this->processedRowCount++;
                        yield $this->padRow($this->normalizeSchemeRows($totalRow), $cols);
                    }

                    $currentInvoice = $invoiceId;
                    $sr = 0;
                    $invTotals = [
                        'ct' => 0,
                        'total_mrp' => 0.0,
                        'taxable' => 0.0,
                        'cgst' => 0.0,
                        'sgst' => 0.0,
                        'igst' => 0.0,
                        'grand_total' => (float) ($r->grand_total ?? 0),
                        'meta' => [],
                        'same_state' => true,
                    ];
                }

                $invoiceNo = trim((string) ($r->invoice_no ?? ''));
                $this->lastInvoiceNo = $invoiceNo;
                $this->lastInvoiceId = $invoiceId;

                $customerGstin = trim((string) ($r->customer_gstin ?? ''));
                $invoiceType = $customerGstin !== '' ? 'B2B' : 'B2C';

                $customerStateCode = $this->normalizeStateCode(($r->customer_pos_code ?: $r->customer_state_code) ?? '');
                $sameState = $supplierStateNorm !== '' && $customerStateCode !== '' && $customerStateCode === $supplierStateNorm;
                $invTotals['same_state'] = $sameState;

                $gstRate = $r->product_gst_rate !== null ? (float) $r->product_gst_rate : 0.0;
                $halfRate = $gstRate / 2;
                $taxable = (float) ($r->taxable ?? 0);
                $gstAmt = $taxable * ($gstRate / 100);
                $halfGstAmt = $gstAmt / 2;

                $cgstPct = $halfRate;
                $cgstVal = $halfGstAmt;
                $sgstPct = $sameState ? $halfRate : null;
                $sgstVal = $sameState ? $halfGstAmt : null;
                $igstPct = $sameState ? null : $halfRate;
                $igstVal = $sameState ? null : $halfGstAmt;

                $unit = (int) round((float) ($r->qty_un ?? 0));
                $ct = (int) round((float) ($r->qty_ct ?? 0));
                $packSize = $r->product_pack_size !== null ? (int) $r->product_pack_size : 0;
                $totalQty = $ct * $packSize;

                $mrp = (float) ($r->mrp ?? 0);
                $totalUnits = (float) ($r->total_units ?? 0);
                $totalMrp = $mrp * $totalUnits;

                $tradePrice = $r->product_trade_price !== null ? (float) $r->product_trade_price : 0.0;

                $productCode = (string) ($r->product_code ?? '');
                $productDesc = (string) ($r->product_description ?? '');
                if ($productDesc === '') {
                    $name = (string) ($r->product_name ?? '');
                    $vol = trim((string) ($r->product_volume ?? ''));
                    $productDesc = trim($name . ($vol !== '' ? ' ' . $vol : ''));
                }

                $sr++;

                $isFree = (bool) ($r->is_free ?? false);
                if (!$isFree) {
                    $invTotals['ct'] += $ct;
                    $invTotals['total_mrp'] += $totalMrp;
                    $invTotals['taxable'] += $taxable;
                    $invTotals['cgst'] += $cgstVal;
                    $invTotals['sgst'] += (float) ($sgstVal ?? 0);
                    $invTotals['igst'] += (float) ($igstVal ?? 0);
                }
                $invTotals['meta'] = [
                    'invoice_no' => $invoiceNo !== '' ? $invoiceNo : '-',
                    'invoice_date' => $r->invoice_date ? Carbon::parse($r->invoice_date)->format('d-m-Y') : '-',
                    'customer_code' => (string) ($r->customer_code ?? '') !== '' ? (string) $r->customer_code : '-',
                    'customer_name' => (string) ($r->customer_name ?? '') !== '' ? (string) $r->customer_name : '-',
                    'customer_gstin' => $customerGstin !== '' ? $customerGstin : '-',
                    'customer_state_code' => $customerStateCode !== '' ? $customerStateCode : '-',
                    'customer_state' => (string) ($r->customer_state ?? '') !== '' ? (string) $r->customer_state : '-',
                    'invoice_type' => $invoiceType,
                    'salesman' => trim((string) ($r->salesman ?? '')) !== '' ? (string) $r->salesman : '-',
                ];

                $taxCols = $isFree ? array_fill(0, 8, '0') : null;

                $row = [
                    ($this->supplier['supplier_name'] ?? '') !== '' ? (string) $this->supplier['supplier_name'] : '-',
                    ($this->supplier['supplier_gstin'] ?? '') !== '' ? (string) $this->supplier['supplier_gstin'] : '-',
                    ($this->supplier['supplier_state_code'] ?? '') !== '' ? (string) $this->supplier['supplier_state_code'] : '-',
                    ($this->supplier['supplier_state'] ?? '') !== '' ? (string) $this->supplier['supplier_state'] : '-',
                    $sr,
                    $invoiceNo !== '' ? $invoiceNo : '-',
                    $r->invoice_date ? Carbon::parse($r->invoice_date)->format('d-m-Y') : '-',
                    (string) ($r->customer_code ?? '') !== '' ? (string) $r->customer_code : '-',
                    (string) ($r->customer_name ?? '') !== '' ? (string) $r->customer_name : '-',
                    $customerGstin !== '' ? $customerGstin : '-',
                    $customerStateCode !== '' ? $customerStateCode : '-',
                    (string) ($r->customer_state ?? '') !== '' ? (string) $r->customer_state : '-',
                    $invoiceType,
                    trim((string) ($r->salesman ?? '')) !== '' ? (string) $r->salesman : '-',
                    $productCode !== '' ? $productCode : '-',
                    $productDesc !== '' ? $productDesc : '-',
                    (string) ($r->product_category ?? '') !== '' ? (string) $r->product_category : '-',
                    (string) ($r->product_brand ?? '') !== '' ? (string) $r->product_brand : '-',
                    (string) ($r->product_pack_type ?? '') !== '' ? (string) $r->product_pack_type : '-',
                    (string) ($r->hsn_code ?? '') !== '' ? (string) $r->hsn_code : '-',
                    $r->product_pack_size !== null ? (int) $r->product_pack_size : '-',
                    $unit,
                    $ct,
                    $totalQty,
                    $mrp,
                    $totalMrp,
                    $tradePrice,
                    $isFree ? $taxCols[0] : $taxable,
                    $isFree ? $taxCols[1] : $cgstPct,
                    $isFree ? $taxCols[2] : $cgstVal,
                    $isFree ? $taxCols[3] : ($sgstPct ?? '-'),
                    $isFree ? $taxCols[4] : ($sgstVal ?? '-'),
                    $isFree ? $taxCols[5] : ($igstPct ?? '-'),
                    $isFree ? $taxCols[6] : ($igstVal ?? '-'),
                    $isFree ? $taxCols[7] : (float) ($r->line_total ?? 0),
                ];

                $this->dataRowCount++;
                $this->processedRowCount++;
                yield $this->padRow($this->normalizeSchemeRows($row), $cols);
            } catch (\Throwable $e) {
                Log::error('sales_register_export_row_failed', [
                    'invoice_id' => $this->lastInvoiceId,
                    'invoice_no' => $this->lastInvoiceNo,
                    'processed_rows' => $this->processedRowCount,
                    'error' => $e->getMessage(),
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                throw $e;
            }
        }

        $totalRow = $pushTotalRow();
        if (is_array($totalRow)) {
            $this->dataRowCount++;
            $this->processedRowCount++;
            yield $this->padRow($this->normalizeSchemeRows($totalRow), $cols);
        }

        yield $this->padRow([''], $cols);
        yield $this->padRow(['Prepared By: _______________________'], $cols);
        yield $this->padRow(['System Generated Report'], $cols);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $cols = count($this->tableHeadings);
                $lastCol = Coordinate::stringFromColumnIndex(max(1, $cols));

                $headingsRow = 6;
                $firstDataRow = 7;
                $lastDataRow = $headingsRow + $this->dataRowCount;
                $footerPreparedRow = $lastDataRow + 2;
                $footerSystemRow = $lastDataRow + 3;

                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->mergeCells("A2:{$lastCol}2");

                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 13],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle("A3:{$lastCol}4")->applyFromArray([
                    'font' => ['size' => 10],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                $sheet->getStyle("A{$headingsRow}:{$lastCol}{$headingsRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E2E8F0']],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
                ]);

                if ($lastDataRow >= $headingsRow) {
                    $sheet->getStyle("A{$headingsRow}:{$lastCol}{$lastDataRow}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
                    ]);
                }

                $sheet->freezePane("A{$firstDataRow}");

                if ($lastDataRow >= 1) {
                    $sheet->getStyle("A1:{$lastCol}{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("A1:{$lastCol}{$lastDataRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                }

                $srCol = Coordinate::stringFromColumnIndex($this->srNoCol);
                $sheet->getColumnDimension($srCol)->setAutoSize(false);
                $sheet->getColumnDimension($srCol)->setWidth(9);
                $sheet->getStyle("{$srCol}{$firstDataRow}:{$srCol}{$lastDataRow}")->getNumberFormat()->setFormatCode('0');

                $prodCol = Coordinate::stringFromColumnIndex($this->productDescCol);
                $sheet->getColumnDimension($prodCol)->setAutoSize(false);
                $sheet->getColumnDimension($prodCol)->setWidth(45);

                $unitCol = Coordinate::stringFromColumnIndex(22);
                $ctCol = Coordinate::stringFromColumnIndex(23);
                $taxableCol = Coordinate::stringFromColumnIndex(28);
                $cgstPctCol = Coordinate::stringFromColumnIndex(29);
                $cgstValCol = Coordinate::stringFromColumnIndex(30);
                $sgstPctCol = Coordinate::stringFromColumnIndex(31);
                $sgstValCol = Coordinate::stringFromColumnIndex(32);
                $igstPctCol = Coordinate::stringFromColumnIndex(33);
                $igstValCol = Coordinate::stringFromColumnIndex(34);
                $invValCol = Coordinate::stringFromColumnIndex(35);

                foreach ($this->qtyCols as $idx) {
                    $col = Coordinate::stringFromColumnIndex((int) $idx);
                    $sheet->getStyle("{$col}{$firstDataRow}:{$col}{$lastDataRow}")->getNumberFormat()->setFormatCode('#,##0');
                }

                foreach ($this->currencyCols as $idx) {
                    $col = Coordinate::stringFromColumnIndex((int) $idx);
                    $sheet->getStyle("{$col}{$firstDataRow}:{$col}{$lastDataRow}")
                        ->getNumberFormat()
                        ->setFormatCode('"₹" #,##,##0.00');
                }

                foreach ($this->percentCols as $idx) {
                    $col = Coordinate::stringFromColumnIndex((int) $idx);
                    $sheet->getStyle("{$col}{$firstDataRow}:{$col}{$lastDataRow}")
                        ->getNumberFormat()
                        ->setFormatCode('0.00');
                }

                for ($r = $firstDataRow; $r <= $lastDataRow; $r++) {
                    $pd = $sheet->getCell($prodCol . $r)->getValue();
                    if (trim((string) $pd) === 'TOTAL') {
                        $sheet->setCellValueExplicit($srCol . $r, 0, DataType::TYPE_NUMERIC);
                        $sheet->getStyle($srCol . $r)->getNumberFormat()->setFormatCode('0');
                    }

                    $taxableVal = $sheet->getCell($taxableCol . $r)->getValue();
                    $invoiceVal = $sheet->getCell($invValCol . $r)->getValue();
                    $srVal = $sheet->getCell($srCol . $r)->getValue();
                    $unitVal = $sheet->getCell($unitCol . $r)->getValue();
                    $ctVal = $sheet->getCell($ctCol . $r)->getValue();

                    $isSchemeRow = false;
                    if (trim((string) $pd) !== 'TOTAL') {
                        $t = is_numeric($taxableVal) ? (float) $taxableVal : null;
                        $iv = is_numeric($invoiceVal) ? (float) $invoiceVal : null;
                        $srn = is_numeric($srVal) ? (int) $srVal : null;
                        $u = is_numeric($unitVal) ? (float) $unitVal : 0.0;
                        $c = is_numeric($ctVal) ? (float) $ctVal : 0.0;
                        $hasQty = ($u > 0) || ($c > 0);
                        $isSchemeRow = $srn !== null && $srn > 0 && $hasQty && $t !== null && abs($t) < 1e-9 && $iv !== null && abs($iv) < 1e-9;
                    }
                    if ($isSchemeRow) {
                        foreach ([$taxableCol, $cgstPctCol, $cgstValCol, $sgstPctCol, $sgstValCol, $igstPctCol, $igstValCol, $invValCol] as $col) {
                            $sheet->setCellValueExplicit($col . $r, '0', DataType::TYPE_STRING);
                        }
                    }

                    $v = $sheet->getCell($srCol . $r)->getValue();
                    $isTotalRow = false;
                    if (is_numeric($v)) {
                        $isTotalRow = ((int) $v) === 0;
                    } else {
                        $isTotalRow = trim((string) $v) === '0';
                    }
                    if ($isTotalRow) {
                        $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                            'font' => ['bold' => true],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F1F5F9']],
                            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '94A3B8']]],
                        ]);
                    }
                }

                $sheet->getStyle("A{$footerPreparedRow}:{$lastCol}{$footerSystemRow}")->applyFromArray([
                    'font' => ['size' => 10],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);
                $sheet->mergeCells("A{$footerPreparedRow}:{$lastCol}{$footerPreparedRow}");
                $sheet->mergeCells("A{$footerSystemRow}:{$lastCol}{$footerSystemRow}");
            },
        ];
    }
}
