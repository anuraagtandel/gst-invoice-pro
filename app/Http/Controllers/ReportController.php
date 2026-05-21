<?php

namespace App\Http\Controllers;

use App\Exports\PurchaseReportExport;
use App\Exports\SalesReportExport;
use App\Models\Invoice;
use App\Models\Purchase;
use App\Models\Salesman;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $baseQuery = Invoice::active()->with('customer');

        if ($request->filled('from_date')) {
            $baseQuery->whereDate('invoice_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $baseQuery->whereDate('invoice_date', '<=', $request->to_date);
        }

        $salesmanFilter = trim((string) $request->query('salesman', ''));
        if ($salesmanFilter !== '') {
            if ($salesmanFilter === '__NONE__') {
                $baseQuery->where(function ($q) {
                    $q->whereNull('salesman')->orWhere('salesman', '');
                });
            } else {
                $baseQuery->where('salesman', $salesmanFilter);
            }
        }

        if ($request->has('export')) {
            $data = (clone $baseQuery)->orderBy('invoice_date', 'desc')->get();

            return Excel::download(new SalesReportExport($data), 'sales-report.xlsx');
        }

        $invoices = (clone $baseQuery)->orderBy('invoice_date', 'desc')->paginate(50);

        $summaryRow = (clone $baseQuery)
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COALESCE(SUM(taxable_amount), 0) as taxable')
            ->selectRaw('COALESCE(SUM(total_cgst), 0) as cgst')
            ->selectRaw('COALESCE(SUM(total_sgst), 0) as sgst')
            ->selectRaw('COALESCE(SUM(grand_total), 0) as grand_total')
            ->first();

        $summary = [
            'count' => (int) ($summaryRow->count ?? 0),
            'taxable' => (float) ($summaryRow->taxable ?? 0),
            'cgst' => (float) ($summaryRow->cgst ?? 0),
            'sgst' => (float) ($summaryRow->sgst ?? 0),
            'grand_total' => (float) ($summaryRow->grand_total ?? 0),
        ];

        $salesBySalesman = (clone $baseQuery)
            ->reorder()
            ->selectRaw("COALESCE(NULLIF(salesman,''), 'Unassigned') as salesman_name")
            ->selectRaw('COUNT(*) as invoice_count')
            ->selectRaw('COALESCE(SUM(grand_total), 0) as total_sales')
            ->groupBy(DB::raw("COALESCE(NULLIF(salesman,''), 'Unassigned')"))
            ->orderByDesc('total_sales')
            ->get();

        $salesmen = Salesman::where('is_active', true)->orderBy('name')->get();

        return view('reports.sales', compact('invoices', 'summary', 'salesmen', 'salesmanFilter', 'salesBySalesman'));
    }

    public function purchase(Request $request)
    {
        $purchaseBase = Purchase::query();
        $itemsQuery = DB::table('purchase_items')
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->leftJoin('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
            ->leftJoin('products', 'purchase_items.product_id', '=', 'products.id')
            ->whereNull('purchases.deleted_at')
            ->where(function ($q) {
                $q->where('purchase_items.total_units', '>', 0)
                    ->orWhere('purchase_items.qty_ct', '>', 0)
                    ->orWhere('purchase_items.qty_un', '>', 0)
                    ->orWhere('purchase_items.item_total', '>', 0);
            });

        if ($request->filled('from_date')) {
            $purchaseBase->whereDate('bill_date', '>=', $request->from_date);
            $itemsQuery->whereDate('purchases.bill_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $purchaseBase->whereDate('bill_date', '<=', $request->to_date);
            $itemsQuery->whereDate('purchases.bill_date', '<=', $request->to_date);
        }

        $firmStateCode = trim((string) Setting::get('firm_state_code', ''));
        if ($firmStateCode === '') {
            $gstin = trim((string) Setting::get('firm_gstin', ''));
            $firmStateCode = preg_replace('/\D/', '', substr($gstin, 0, 2)) ?: '';
        }

        $transformRows = function ($rows) use ($firmStateCode) {
            return collect($rows)->map(function ($r) use ($firmStateCode) {
                $supplierStateCode = trim((string) ($r->supplier_state_code ?? ''));
                $supplierStateCode = preg_replace('/\D/', '', $supplierStateCode);
                if ($supplierStateCode === '') {
                    $supplierGstin = trim((string) ($r->supplier_gstin ?? ''));
                    if ($supplierGstin !== '') {
                        $supplierStateCode = preg_replace('/\D/', '', substr($supplierGstin, 0, 2)) ?: '';
                    }
                }
                $sameState = $supplierStateCode !== '' && $firmStateCode !== '' && $supplierStateCode === $firmStateCode;

                $gstRate = (float) ($r->product_gst_rate ?? 0);
                $taxable = (float) ($r->item_total ?? 0);
                $totalGstAmt = $taxable * ($gstRate / 100);
                $halfRate = $gstRate / 2;
                $halfGstAmt = $totalGstAmt / 2;

                if ($sameState) {
                    $cgstPct = $halfRate;
                    $cgstAmt = $halfGstAmt;
                    $sgstPct = $halfRate;
                    $sgstAmt = $halfGstAmt;
                    $igstPct = 0.0;
                    $igstAmt = 0.0;
                } else {
                    $cgstPct = 0.0;
                    $cgstAmt = 0.0;
                    $sgstPct = 0.0;
                    $sgstAmt = 0.0;
                    $igstPct = $gstRate;
                    $igstAmt = $totalGstAmt;
                }
                $netTotal = $taxable + $totalGstAmt;

                return [
                    'supplier_name' => trim((string) ($r->supplier_name ?? '')) !== '' ? (string) $r->supplier_name : ((string) ($r->supplier_text ?? '-') ?: '-'),
                    'supplier_gst' => (string) ($r->supplier_gstin ?? ''),
                    'supplier_state_code' => (string) ($r->supplier_state_code ?? ''),
                    'supplier_state' => (string) ($r->supplier_state ?? ''),
                    'supplier_pan' => (string) ($r->supplier_pan ?? ''),
                    'invoice_no' => (string) ($r->bill_no ?? ''),
                    'invoice_date' => $r->bill_date ? \Carbon\Carbon::parse($r->bill_date)->format('d-m-Y') : '-',
                    'hsn_code' => (string) ($r->hsn_code ?? ''),
                    'product_code' => (string) ($r->product_code ?? ''),
                    'product_description' => (string) ($r->product_description ?? ''),
                    'un' => (int) round((float) ($r->qty_un ?? 0)),
                    'ct' => (int) round((float) ($r->qty_ct ?? 0)),
                    'purchase_rate' => (float) ($r->purchase_rate ?? 0),
                    'cgst_pct' => $cgstPct,
                    'cgst_amt' => $cgstAmt,
                    'sgst_pct' => $sgstPct,
                    'sgst_amt' => $sgstAmt,
                    'igst_pct' => $igstPct,
                    'igst_amt' => $igstAmt,
                    'net_total' => $netTotal,
                ];
            });
        };

        $selectCols = [
            'purchase_items.id as purchase_item_id',
            'purchases.bill_no',
            'purchases.bill_date',
            'purchases.supplier as supplier_text',
            'suppliers.name as supplier_name',
            'suppliers.gstin as supplier_gstin',
            'suppliers.state_code as supplier_state_code',
            'suppliers.state as supplier_state',
            'suppliers.pan as supplier_pan',
            'products.hsn_code as hsn_code',
            'products.product_code as product_code',
            'products.name as product_description',
            'products.gst_rate as product_gst_rate',
            'purchase_items.qty_un',
            'purchase_items.qty_ct',
            'purchase_items.purchase_rate',
            'purchase_items.item_total',
        ];

        $reportRowsPaginated = (clone $itemsQuery)
            ->select($selectCols)
            ->orderBy('purchases.bill_date', 'desc')
            ->orderBy('purchases.id', 'desc')
            ->orderBy('purchase_items.id')
            ->paginate(100)
            ->withQueryString();

        $reportRowsPaginated->setCollection($transformRows($reportRowsPaginated->items()));

        $summary = [
            'purchase_count' => (int) (clone $itemsQuery)->distinct()->count('purchases.id'),
            'total_purchase_amount' => (float) (clone $purchaseBase)->sum('total_amount'),
        ];

        $totalsRow = $transformRows((clone $itemsQuery)->select($selectCols)->get());
        $totals = [
            'total_ct' => (int) round((float) $totalsRow->sum('ct')),
            'total_net_purchase_amount' => (float) $totalsRow->sum('net_total'),
        ];

        if ($request->has('export')) {
            $exportRows = (clone $itemsQuery)
                ->select($selectCols)
                ->orderBy('purchases.bill_date', 'desc')
                ->orderBy('purchases.id', 'desc')
                ->orderBy('purchase_items.id')
                ->get();
            $mapped = $transformRows($exportRows)->values();

            $tableRows = [];
            foreach ($mapped as $i => $r) {
                $tableRows[] = [
                    $i + 1,
                    $r['supplier_name'] !== '' ? $r['supplier_name'] : '-',
                    $r['supplier_gst'] !== '' ? $r['supplier_gst'] : '-',
                    $r['supplier_state_code'] !== '' ? $r['supplier_state_code'] : '-',
                    $r['supplier_state'] !== '' ? $r['supplier_state'] : '-',
                    $r['supplier_pan'] !== '' ? $r['supplier_pan'] : '-',
                    $r['invoice_no'] !== '' ? $r['invoice_no'] : '-',
                    $r['invoice_date'] !== '' ? $r['invoice_date'] : '-',
                    $r['hsn_code'] !== '' ? $r['hsn_code'] : '-',
                    $r['product_code'] !== '' ? $r['product_code'] : '-',
                    $r['product_description'] !== '' ? $r['product_description'] : '-',
                    $r['ct'],
                    $r['purchase_rate'],
                    $r['cgst_pct'],
                    $r['cgst_amt'],
                    $r['sgst_pct'] ?? '-',
                    $r['sgst_amt'] ?? '-',
                    $r['igst_pct'] ?? '-',
                    $r['igst_amt'] ?? '-',
                    $r['net_total'],
                ];
            }

            $tableRows[] = ['', '', '', '', '', '', '', '', '', '', 'Total', $totals['total_ct'], '', '', '', '', '', '', '', $totals['total_net_purchase_amount']];

            $headings = [
                'Sr No.',
                'Supplier Name',
                'GST No.',
                'Supplier State Code',
                'Supplier State',
                'Supplier PAN No.',
                'Invoice No.',
                'Invoice Date',
                'HSN Code',
                'Product Code',
                'Product Description',
                'CT',
                'Purchase Rate ₹',
                'CGST %',
                'CGST ₹',
                'SGST/UTGST %',
                'SGST/UTGST ₹',
                'IGST %',
                'IGST ₹',
                'Net Total Incl. Tax ₹',
            ];

            $user = \Illuminate\Support\Facades\Auth::user();
            $generatedBy = $user ? (trim((string) ($user->name ?? '')) !== '' ? (string) $user->name : (string) ($user->email ?? '')) : '';
            $now = now();
            $meta = [
                'company' => 'SAGARDUTT PEPSI DISTRIBUTORS',
                'report' => 'Purchase Report',
                'date_range' => 'Date Range: ' . ($request->query('from_date', now()->toDateString())) . ' to ' . ($request->query('to_date', now()->toDateString())),
                'generated_line' => 'Generated On Date: ' . $now->toDateString() . '   Generated Time: ' . $now->format('H:i:s') . '   Generated By: ' . $generatedBy,
            ];

            $qtyCols = [1, 12];
            $currencyCols = [13, 15, 17, 19, 20];
            $productDescCol = 11;
            $file = 'purchase-report_' . now()->format('Ymd_His') . '.xlsx';

            return Excel::download(new PurchaseReportExport($tableRows, $headings, $meta, $qtyCols, $currencyCols, $productDescCol), $file);
        }

        return view('reports.purchase', [
            'purchases' => $reportRowsPaginated,
            'summary' => $summary,
            'totals' => $totals,
        ]);
    }

    public function gst(Request $request)
    {
        $baseQuery = Invoice::active()
            ->with('customer')
            ->whereHas('customer', function ($q) {
                $q->whereNotNull('gstin')->where('gstin', '!=', '');
            });

        if ($request->filled('from_date')) {
            $baseQuery->whereDate('invoice_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $baseQuery->whereDate('invoice_date', '<=', $request->to_date);
        }
        if ($request->filled('customer_name')) {
            $baseQuery->whereHas('customer', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->customer_name . '%');
            });
        }
        if ($request->filled('gstin')) {
            $baseQuery->whereHas('customer', function ($q) use ($request) {
                $q->where('gstin', 'like', '%' . $request->gstin . '%');
            });
        }
        if ($request->filled('invoice_no')) {
            $baseQuery->where('invoice_no', 'like', '%' . $request->invoice_no . '%');
        }
        if ($request->filled('json_status')) {
            $baseQuery->where('einvoice_status', $request->json_status);
        }

        if ($request->has('export')) {
            $data = (clone $baseQuery)->orderBy('invoice_date', 'desc')->get();
            $firmStateCode = trim((string) Setting::get('firm_state_code', ''));
            if ($firmStateCode === '') {
                $gstin = trim((string) Setting::get('firm_gstin', ''));
                $firmStateCode = preg_replace('/\D/', '', substr($gstin, 0, 2)) ?: '';
            }

            return Excel::download(new \App\Exports\GstReportExport($data, $firmStateCode, $request), 'gst-report_' . now()->format('Ymd_His') . '.xlsx');
        }

        $invoices = (clone $baseQuery)->orderBy('invoice_date', 'desc')->paginate(50)->withQueryString();

        $summaryRow = (clone $baseQuery)
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COALESCE(SUM(taxable_amount), 0) as taxable')
            ->selectRaw('COALESCE(SUM(total_cgst), 0) as cgst')
            ->selectRaw('COALESCE(SUM(total_sgst), 0) as sgst')
            ->selectRaw('COALESCE(SUM(total_cess), 0) as cess')
            ->selectRaw('COALESCE(SUM(grand_total), 0) as grand_total')
            ->first();

        $summary = [
            'count' => (int) ($summaryRow->count ?? 0),
            'taxable' => (float) ($summaryRow->taxable ?? 0),
            'cgst' => (float) ($summaryRow->cgst ?? 0),
            'sgst' => (float) ($summaryRow->sgst ?? 0),
            'cess' => (float) ($summaryRow->cess ?? 0),
            'grand_total' => (float) ($summaryRow->grand_total ?? 0),
        ];

        $firmStateCode = trim((string) Setting::get('firm_state_code', ''));
        if ($firmStateCode === '') {
            $gstin = trim((string) Setting::get('firm_gstin', ''));
            $firmStateCode = preg_replace('/\D/', '', substr($gstin, 0, 2)) ?: '';
        }

        return view('reports.gst', compact('invoices', 'summary', 'firmStateCode'));
    }

    public function bulkJson(Request $request)
    {
        $idsStr = $request->input('invoice_ids', '[]');
        $ids = json_decode($idsStr, true);
        if (!is_array($ids) || empty($ids)) {
            return back()->with('error', 'No invoices selected.');
        }

        $invoices = Invoice::whereIn('id', $ids)->with(['customer', 'invoiceItems.product'])->get();
        if ($invoices->isEmpty()) {
            return back()->with('error', 'No invoices found.');
        }

        $zipFile = storage_path('app/public/einvoices_' . time() . '.zip');
        $zip = new \ZipArchive();
        if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $invoiceController = new InvoiceController();
            
            foreach ($invoices as $invoice) {
                $response = $invoiceController->eInvoiceJson($request, $invoice->id);
                if ($response->getStatusCode() === 200) {
                    $content = $response->getContent();
                    $filename = 'EInvoice_' . $invoice->invoice_no . '.json';
                    $zip->addFromString($filename, $content);
                    
                    $invoice->update(['einvoice_status' => 'Downloaded']);
                }
            }
            $zip->close();

            return response()->download($zipFile)->deleteFileAfterSend(true);
        }

        return back()->with('error', 'Failed to create zip file.');
    }
}
