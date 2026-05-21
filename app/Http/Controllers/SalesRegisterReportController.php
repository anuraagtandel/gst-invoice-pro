<?php

namespace App\Http\Controllers;

use App\Exports\SalesRegisterReportExport;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class SalesRegisterReportController extends Controller
{
    private function resolveRange(Request $request): array
    {
        $preset = trim((string) $request->query('range', 'today'));
        $today = now()->toDateString();

        if (!in_array($preset, ['today', 'week', 'month', 'custom'], true)) {
            $preset = 'today';
        }

        if ($preset === 'custom') {
            $from = $request->query('from_date', $today);
            $to = $request->query('to_date', $today);
            $fromC = Carbon::parse($from)->startOfDay();
            $toC = Carbon::parse($to)->endOfDay();
            if ($fromC->gt($toC)) {
                [$fromC, $toC] = [$toC->copy()->startOfDay(), $fromC->copy()->endOfDay()];
            }
            return [$preset, $fromC, $toC, $fromC->toDateString(), $toC->toDateString()];
        }

        if ($preset === 'week') {
            $fromC = now()->startOfWeek()->startOfDay();
            $toC = now()->endOfWeek()->endOfDay();
            return [$preset, $fromC, $toC, $fromC->toDateString(), $toC->toDateString()];
        }

        if ($preset === 'month') {
            $fromC = now()->startOfMonth()->startOfDay();
            $toC = now()->endOfMonth()->endOfDay();
            return [$preset, $fromC, $toC, $fromC->toDateString(), $toC->toDateString()];
        }

        $fromC = Carbon::parse($today)->startOfDay();
        $toC = Carbon::parse($today)->endOfDay();

        return [$preset, $fromC, $toC, $today, $today];
    }

    private function normalizeStateCode($v): string
    {
        return preg_replace('/\D/', '', (string) ($v ?? ''));
    }

    private function supplierMeta(): array
    {
        $supplierName = (string) Setting::get('firm_name', 'SAGARDUTT PEPSI DISTRIBUTORS');
        $supplierGstin = (string) Setting::get('firm_gstin', '');
        $supplierStateCode = trim((string) Setting::get('firm_state_code', ''));
        if ($supplierStateCode === '') {
            $supplierStateCode = preg_replace('/\D/', '', substr(trim($supplierGstin), 0, 2)) ?: '';
        }
        $supplierState = (string) Setting::get('firm_state', '');

        return [
            'supplier_name' => $supplierName,
            'supplier_gstin' => $supplierGstin,
            'supplier_state_code' => $supplierStateCode,
            'supplier_state' => $supplierState,
            'supplier_state_code_norm' => $this->normalizeStateCode($supplierStateCode),
        ];
    }

    private function buildRows(Carbon $from, Carbon $to): array
    {
        $supplier = $this->supplierMeta();

        $rows = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->leftJoin('products', 'invoice_items.product_id', '=', 'products.id')
            ->where('invoices.is_deleted', false)
            ->whereNull('invoices.deleted_at')
            ->whereBetween('invoices.invoice_date', [$from, $to])
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
            ->orderBy('invoice_items.id')
            ->get();

        $tableRows = [];
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

        $pushTotalRow = function () use (&$tableRows, &$invTotals, $supplier) {
            if ($invTotals['meta'] === []) {
                return;
            }
            $m = $invTotals['meta'];
            $sameState = (bool) ($invTotals['same_state'] ?? true);

            $tableRows[] = [
                $supplier['supplier_name'] !== '' ? $supplier['supplier_name'] : '-',
                $supplier['supplier_gstin'] !== '' ? $supplier['supplier_gstin'] : '-',
                $supplier['supplier_state_code'] !== '' ? $supplier['supplier_state_code'] : '-',
                $supplier['supplier_state'] !== '' ? $supplier['supplier_state'] : '-',
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

        foreach ($rows as $r) {
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
                $pushTotalRow();
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

            $customerGstin = trim((string) ($r->customer_gstin ?? ''));
            $invoiceType = $customerGstin !== '' ? 'B2B' : 'B2C';

            $customerStateCode = $this->normalizeStateCode(($r->customer_pos_code ?: $r->customer_state_code) ?? '');
            $sameState = $supplier['supplier_state_code_norm'] !== '' && $customerStateCode !== '' && $customerStateCode === $supplier['supplier_state_code_norm'];
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
                'invoice_no' => (string) ($r->invoice_no ?? '-') ?: '-',
                'invoice_date' => $r->invoice_date ? Carbon::parse($r->invoice_date)->format('d-m-Y') : '-',
                'customer_code' => (string) ($r->customer_code ?? '') !== '' ? (string) $r->customer_code : '-',
                'customer_name' => (string) ($r->customer_name ?? '') !== '' ? (string) $r->customer_name : '-',
                'customer_gstin' => $customerGstin !== '' ? $customerGstin : '-',
                'customer_state_code' => $customerStateCode !== '' ? $customerStateCode : '-',
                'customer_state' => (string) ($r->customer_state ?? '') !== '' ? (string) $r->customer_state : '-',
                'invoice_type' => $invoiceType,
                'salesman' => trim((string) ($r->salesman ?? '')) !== '' ? (string) $r->salesman : '-',
            ];

            $tableRows[] = [
                $supplier['supplier_name'] !== '' ? $supplier['supplier_name'] : '-',
                $supplier['supplier_gstin'] !== '' ? $supplier['supplier_gstin'] : '-',
                $supplier['supplier_state_code'] !== '' ? $supplier['supplier_state_code'] : '-',
                $supplier['supplier_state'] !== '' ? $supplier['supplier_state'] : '-',
                $sr,
                (string) ($r->invoice_no ?? '') !== '' ? (string) $r->invoice_no : '-',
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
                $isFree ? 0.0 : $taxable,
                $isFree ? 0.0 : $cgstPct,
                $isFree ? 0.0 : $cgstVal,
                $isFree ? 0.0 : ($sgstPct ?? '-'),
                $isFree ? 0.0 : ($sgstVal ?? '-'),
                $isFree ? 0.0 : ($igstPct ?? '-'),
                $isFree ? 0.0 : ($igstVal ?? '-'),
                $isFree ? 0.0 : (float) ($r->line_total ?? 0),
            ];
        }

        $pushTotalRow();

        return [$supplier, $tableRows];
    }

    private function headings(): array
    {
        return [
            'Supplier Name',
            'GST No.',
            'State Code',
            'State',
            'Sr No.',
            'Invoice No.',
            'Invoice Date',
            'Customer Code',
            'Customer Name',
            'GSTIN No.',
            'Customer State Code',
            'Customer State',
            'Type of Invoice',
            'Salesman',
            'Product Code',
            'Product Description',
            'Category',
            'Brand',
            'Pack Type',
            'HSN Code',
            'Pack',
            'UNIT',
            'CT',
            'Total Qty',
            'MRP',
            'Total MRP',
            'Trade Price',
            'Taxable Amount',
            'CGST %',
            'CGST Value',
            'SGST / UTGST %',
            'SGST / UTGST Value',
            'IGST %',
            'IGST Value',
            'Invoice Value',
        ];
    }

    public function index(Request $request)
    {
        [$preset, $from, $to, $fromDate, $toDate] = $this->resolveRange($request);
        [, $tableRows] = $this->buildRows($from, $to);

        return view('reports.sales-register', [
            'preset' => $preset,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'headings' => $this->headings(),
            'rows' => $tableRows,
        ]);
    }

    public function export(Request $request)
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        [$preset, $from, $to, $fromDate, $toDate] = $this->resolveRange($request);
        $supplier = $this->supplierMeta();

        $user = Auth::user();
        $generatedBy = $user ? (trim((string) ($user->name ?? '')) !== '' ? (string) $user->name : (string) ($user->email ?? '')) : '';
        $now = now();
        $meta = [
            'company' => $supplier['supplier_name'] !== '' ? $supplier['supplier_name'] : 'SAGARDUTT PEPSI DISTRIBUTORS',
            'report' => 'Sales Register Report',
            'date_range' => 'Date Range: ' . $fromDate . ' to ' . $toDate,
            'generated_line' => 'Generated On Date: ' . $now->toDateString() . '   Generated Time: ' . $now->format('H:i:s') . '   Generated By: ' . $generatedBy,
        ];

        $srNoCol = 5;
        $productDescCol = 16;
        $qtyCols = [$srNoCol, 22, 23, 24];
        $currencyCols = [25, 26, 27, 28, 30, 32, 34, 35];
        $percentCols = [29, 31, 33];

        $file = 'sales-register-report_' . now()->format('Ymd_His') . '.xlsx';

        try {
            return Excel::download(
                new SalesRegisterReportExport($from, $to, $supplier, $this->headings(), $meta, $qtyCols, $currencyCols, $percentCols, $srNoCol, $productDescCol),
                $file
            );
        } catch (\Throwable $e) {
            Log::error('sales_register_export_failed', [
                'range' => $preset,
                'from' => $fromDate,
                'to' => $toDate,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }
}
