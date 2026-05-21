<?php

namespace App\Http\Controllers;

use App\Exports\DailySalesPivotExport;
use App\Exports\DailySchemePivotExport;
use App\Helpers\TradePriceResolver;
use App\Models\Invoice;
use App\Models\Salesman;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class DailyReportController extends Controller
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

    private function normalizeSalesmanName(?string $name): string
    {
        $n = trim((string) $name);
        return $n === '' ? 'Unassigned' : $n;
    }

    private function normalizeSalesmanFilter(Request $request): array
    {
        $raw = $request->query('salesman', []);
        $values = [];
        if (is_string($raw) && $raw !== '') {
            $values = [$raw];
        } elseif (is_array($raw)) {
            $values = $raw;
        }

        $values = array_values(array_filter(array_map(function ($v) {
            $s = trim((string) $v);
            return $s;
        }, $values), fn ($s) => $s !== ''));

        $includeUnassigned = in_array('__NONE__', $values, true);
        $names = array_values(array_filter($values, fn ($s) => $s !== '__NONE__'));

        return [$names, $includeUnassigned, array_values($values)];
    }

    private function buildSalesPivot(Carbon $from, Carbon $to, array $salesmanNames, bool $includeUnassigned): array
    {
        $rowsQuery = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->leftJoin('products', 'invoice_items.product_id', '=', 'products.id')
            ->where('invoices.is_deleted', false)
            ->whereNull('invoices.deleted_at')
            ->whereBetween('invoices.invoice_date', [$from, $to])
            ->where('invoice_items.is_free', false)
            ->where('invoice_items.qty_ct', '>', 0)
            ->selectRaw('invoice_items.product_id as product_id')
            ->selectRaw("TRIM(CONCAT(COALESCE(products.name, invoice_items.product_description), CASE WHEN COALESCE(products.volume,'') <> '' THEN CONCAT(' ', products.volume) ELSE '' END)) as product_name")
            ->addSelect('invoices.salesman')
            ->selectRaw('COALESCE(SUM(invoice_items.qty_ct), 0) as qty_ct')
            ->addSelect('products.pack_size')
            ->addSelect('products.trade_price')
            ->groupBy('invoice_items.product_id', 'product_name', 'invoices.salesman', 'products.pack_size', 'products.trade_price');

        if ($salesmanNames !== [] || $includeUnassigned) {
            $rowsQuery->where(function ($q) use ($salesmanNames, $includeUnassigned) {
                if ($salesmanNames !== []) {
                    $q->whereIn('invoices.salesman', $salesmanNames);
                }
                if ($includeUnassigned) {
                    $q->orWhereNull('invoices.salesman')->orWhere('invoices.salesman', '');
                }
            });
        }

        $rows = $rowsQuery->orderBy('product_name')->get();

        $salesmen = collect($rows)
            ->map(fn ($r) => $this->normalizeSalesmanName($r->salesman))
            ->unique()
            ->values()
            ->all();

        $byProduct = [];
        foreach ($rows as $r) {
            $pid = (string) ($r->product_id ?? '0');
            $salesman = $this->normalizeSalesmanName($r->salesman);
            if (!isset($byProduct[$pid])) {
                $pack = (int) ($r->pack_size ?? 0);
                $tradeUnit = $r->trade_price !== null ? (float) $r->trade_price : 0.0;
                $ratePerCt = $pack > 0 ? ($tradeUnit * $pack) : 0.0;
                $byProduct[$pid] = [
                    'product_name' => (string) ($r->product_name ?? ''),
                    'rate_per_ct' => $ratePerCt,
                    'salesmen' => [],
                ];
            }
            $byProduct[$pid]['salesmen'][$salesman] = ($byProduct[$pid]['salesmen'][$salesman] ?? 0) + (float) $r->qty_ct;
        }

        $tableRows = [];
        $totalCtAll = 0.0;
        $totalAmtAll = 0.0;

        $productsOrdered = collect($byProduct)
            ->sortBy(fn ($v) => strtolower((string) ($v['product_name'] ?? '')))
            ->values();

        $sr = 1;
        foreach ($productsOrdered as $p) {
            $totalCt = 0.0;
            $row = [
                'sr' => $sr++,
                'product_name' => $p['product_name'],
                'salesmen' => [],
                'total_ct' => 0.0,
                'rate_per_ct' => (float) $p['rate_per_ct'],
                'total_amount' => 0.0,
            ];
            foreach ($salesmen as $s) {
                $ct = (float) (($p['salesmen'][$s] ?? 0));
                $row['salesmen'][$s] = $ct;
                $totalCt += $ct;
            }
            $row['total_ct'] = $totalCt;
            $row['total_amount'] = $totalCt * (float) $row['rate_per_ct'];
            $tableRows[] = $row;
            $totalCtAll += $totalCt;
            $totalAmtAll += $row['total_amount'];
        }

        return [
            'salesmen' => $salesmen,
            'rows' => $tableRows,
            'footer' => [
                'total_ct' => $totalCtAll,
                'total_amount' => $totalAmtAll,
            ],
        ];
    }

    private function buildSchemePivot(Carbon $from, Carbon $to, array $salesmanNames, bool $includeUnassigned): array
    {
        $rowsQuery = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->leftJoin('products', 'invoice_items.product_id', '=', 'products.id')
            ->where('invoices.is_deleted', false)
            ->whereNull('invoices.deleted_at')
            ->whereBetween('invoices.invoice_date', [$from, $to])
            ->where('invoice_items.is_free', true)
            ->where('invoice_items.total_units', '>', 0)
            ->selectRaw('invoice_items.product_id as product_id')
            ->selectRaw("TRIM(CONCAT(COALESCE(products.name, invoice_items.product_description), CASE WHEN COALESCE(products.volume,'') <> '' THEN CONCAT(' ', products.volume) ELSE '' END)) as product_name")
            ->addSelect('invoices.salesman')
            ->selectRaw('COALESCE(SUM(invoice_items.total_units), 0) as total_un')
            ->addSelect('products.trade_price')
            ->groupBy('invoice_items.product_id', 'product_name', 'invoices.salesman', 'products.trade_price');

        if ($salesmanNames !== [] || $includeUnassigned) {
            $rowsQuery->where(function ($q) use ($salesmanNames, $includeUnassigned) {
                if ($salesmanNames !== []) {
                    $q->whereIn('invoices.salesman', $salesmanNames);
                }
                if ($includeUnassigned) {
                    $q->orWhereNull('invoices.salesman')->orWhere('invoices.salesman', '');
                }
            });
        }

        $rows = $rowsQuery->orderBy('product_name')->get();

        $salesmen = collect($rows)
            ->map(fn ($r) => $this->normalizeSalesmanName($r->salesman))
            ->unique()
            ->values()
            ->all();

        $byProduct = [];
        foreach ($rows as $r) {
            $pid = (string) ($r->product_id ?? '0');
            $salesman = $this->normalizeSalesmanName($r->salesman);
            if (!isset($byProduct[$pid])) {
                $tradeUnit = TradePriceResolver::unit($r->trade_price ?? null);
                $byProduct[$pid] = [
                    'product_name' => (string) ($r->product_name ?? ''),
                    'rate_per_un' => (float) $tradeUnit,
                    'salesmen' => [],
                ];
            }
            $byProduct[$pid]['salesmen'][$salesman] = ($byProduct[$pid]['salesmen'][$salesman] ?? 0) + (float) $r->total_un;
        }

        $tableRows = [];
        $totalUnAll = 0.0;
        $totalAmtAll = 0.0;

        $productsOrdered = collect($byProduct)
            ->sortBy(fn ($v) => strtolower((string) ($v['product_name'] ?? '')))
            ->values();

        $sr = 1;
        foreach ($productsOrdered as $p) {
            $totalUn = 0.0;
            $row = [
                'sr' => $sr++,
                'product_name' => $p['product_name'],
                'salesmen' => [],
                'total_un' => 0.0,
                'rate_per_un' => (float) $p['rate_per_un'],
                'total_value' => 0.0,
            ];
            foreach ($salesmen as $s) {
                $un = (float) (($p['salesmen'][$s] ?? 0));
                $row['salesmen'][$s] = $un;
                $totalUn += $un;
            }
            $row['total_un'] = $totalUn;
            $row['total_value'] = $totalUn * (float) $row['rate_per_un'];
            $tableRows[] = $row;
            $totalUnAll += $totalUn;
            $totalAmtAll += $row['total_value'];
        }

        return [
            'salesmen' => $salesmen,
            'rows' => $tableRows,
            'footer' => [
                'total_un' => $totalUnAll,
                'total_value' => $totalAmtAll,
            ],
        ];
    }

    private function buildCashCollectionBySalesman(Carbon $from, Carbon $to, array $salesmanNames, bool $includeUnassigned): array
    {
        $hasBusinessName = Schema::hasColumn('customers', 'business_name');

        $q = Invoice::query()
            ->where('invoices.is_deleted', false)
            ->whereNull('invoices.deleted_at')
            ->whereBetween('invoices.invoice_date', [$from, $to])
            ->where(function ($x) {
                $x->where('invoices.payment_type', 'Cash')
                    ->orWhere(function ($y) {
                        $y->where(function ($z) {
                            $z->whereNull('invoices.payment_type')->orWhere('invoices.payment_type', '');
                        })->where('invoices.payment_mode', 'Cash');
                    });
            })
            ->leftJoin('customers', 'invoices.customer_id', '=', 'customers.id')
            ->select([
                'invoices.id',
                'invoices.invoice_no',
                'invoices.invoice_date',
                'invoices.salesman',
                'invoices.paid_amount',
                'customers.name as customer_name',
            ])
            ->selectRaw(($hasBusinessName ? 'customers.business_name' : 'customers.name') . ' as business_name');

        if ($salesmanNames !== [] || $includeUnassigned) {
            $q->where(function ($w) use ($salesmanNames, $includeUnassigned) {
                if ($salesmanNames !== []) {
                    $w->whereIn('invoices.salesman', $salesmanNames);
                }
                if ($includeUnassigned) {
                    $w->orWhereNull('invoices.salesman')->orWhere('invoices.salesman', '');
                }
            });
        }

        $rows = $q->orderBy('invoices.invoice_date', 'desc')->orderBy('invoices.invoice_no', 'desc')->get();

        $groups = [];
        foreach ($rows as $r) {
            $salesmanName = $this->normalizeSalesmanName($r->salesman ?? null);
            if (!isset($groups[$salesmanName])) {
                $groups[$salesmanName] = [
                    'salesman' => $salesmanName,
                    'invoice_count' => 0,
                    'cash_collected' => 0.0,
                    'invoices' => [],
                ];
            }

            $paid = (float) ($r->paid_amount ?? 0);
            $groups[$salesmanName]['invoice_count'] += 1;
            $groups[$salesmanName]['cash_collected'] += $paid;

            $cust = trim((string) ($r->business_name ?? ''));
            if ($cust === '') {
                $cust = trim((string) ($r->customer_name ?? ''));
            }
            if ($cust === '') {
                $cust = '-';
            }

            $date = '';
            if ($r->invoice_date) {
                try {
                    $date = Carbon::parse($r->invoice_date)->format('d-m-Y');
                } catch (\Throwable $e) {
                    $date = (string) $r->invoice_date;
                }
            }

            $groups[$salesmanName]['invoices'][] = [
                'id' => (int) ($r->id ?? 0),
                'invoice_no' => (string) ($r->invoice_no ?? ''),
                'invoice_date' => $date,
                'customer_name' => $cust,
                'amount' => $paid,
            ];
        }

        $list = array_values($groups);
        usort($list, function ($a, $b) {
            return (float) ($b['cash_collected'] ?? 0) <=> (float) ($a['cash_collected'] ?? 0);
        });

        return $list;
    }

    public function index(Request $request)
    {
        [$preset, $from, $to, $fromDate, $toDate] = $this->resolveRange($request);
        $tab = trim((string) $request->query('tab', 'sales'));
        if (!in_array($tab, ['sales', 'scheme'], true)) {
            $tab = 'sales';
        }

        [$salesmanNames, $includeUnassigned, $selectedSalesmen] = $this->normalizeSalesmanFilter($request);
        $activeSalesmen = Salesman::where('is_active', true)->orderBy('name')->pluck('name')->toArray();

        $sales = $this->buildSalesPivot($from, $to, $salesmanNames, $includeUnassigned);
        $scheme = $this->buildSchemePivot($from, $to, $salesmanNames, $includeUnassigned);
        $cashCollection = $this->buildCashCollectionBySalesman($from, $to, $salesmanNames, $includeUnassigned);

        return view('reports.daily', compact('preset', 'fromDate', 'toDate', 'tab', 'sales', 'scheme', 'cashCollection', 'activeSalesmen', 'selectedSalesmen'));
    }

    public function exportSales(Request $request)
    {
        [, $from, $to, $fromDate, $toDate] = $this->resolveRange($request);
        [$salesmanNames, $includeUnassigned] = $this->normalizeSalesmanFilter($request);
        $data = $this->buildSalesPivot($from, $to, $salesmanNames, $includeUnassigned);

        $headings = array_merge(
            ['Sr No.', 'Product Name'],
            $data['salesmen'],
            ['Total CT', 'Rate/CT', 'Total Amount (₹)']
        );

        $rows = [];
        foreach ($data['rows'] as $r) {
            $row = [$r['sr'], $r['product_name']];
            foreach ($data['salesmen'] as $s) {
                $row[] = $r['salesmen'][$s] ?? 0;
            }
            $row[] = $r['total_ct'];
            $row[] = $r['rate_per_ct'];
            $row[] = $r['total_amount'];
            $rows[] = $row;
        }
        $rows[] = array_merge(['', 'Total'], array_fill(0, count($data['salesmen']), ''), [$data['footer']['total_ct'], '', $data['footer']['total_amount']]);

        $salesmenCount = count($data['salesmen']);
        $qtyCols = array_merge(range(3, 2 + $salesmenCount), [3 + $salesmenCount]);
        $currencyCols = [4 + $salesmenCount, 5 + $salesmenCount];

        $user = Auth::user();
        $generatedBy = $user ? (trim((string) ($user->name ?? '')) !== '' ? (string) $user->name : (string) ($user->email ?? '')) : '';
        $now = now();
        $meta = [
            'company' => 'SAGARDUTT PEPSI DISTRIBUTORS',
            'report' => 'Daily Sales Report',
            'date_range' => 'Date Range: ' . $fromDate . ' to ' . $toDate,
            'generated_line' => 'Generated On Date: ' . $now->toDateString() . '   Generated Time: ' . $now->format('H:i:s') . '   Generated By: ' . $generatedBy,
        ];

        $file = 'daily-sales-report_' . $fromDate . '_to_' . $toDate . '.xlsx';

        return Excel::download(new DailySalesPivotExport($rows, $headings, $meta, $qtyCols, $currencyCols), $file);
    }

    public function exportScheme(Request $request)
    {
        [, $from, $to, $fromDate, $toDate] = $this->resolveRange($request);
        [$salesmanNames, $includeUnassigned] = $this->normalizeSalesmanFilter($request);
        $data = $this->buildSchemePivot($from, $to, $salesmanNames, $includeUnassigned);

        $headings = array_merge(
            ['Sr No.', 'Free Product Name'],
            $data['salesmen'],
            ['Total UN', 'Rate/UN', 'Total Value (₹)']
        );

        $rows = [];
        foreach ($data['rows'] as $r) {
            $row = [$r['sr'], $r['product_name']];
            foreach ($data['salesmen'] as $s) {
                $row[] = $r['salesmen'][$s] ?? 0;
            }
            $row[] = $r['total_un'];
            $row[] = $r['rate_per_un'];
            $row[] = $r['total_value'];
            $rows[] = $row;
        }
        $rows[] = array_merge(['', 'Total'], array_fill(0, count($data['salesmen']), ''), [$data['footer']['total_un'], '', $data['footer']['total_value']]);

        $salesmenCount = count($data['salesmen']);
        $qtyCols = array_merge(range(3, 2 + $salesmenCount), [3 + $salesmenCount]);
        $currencyCols = [4 + $salesmenCount, 5 + $salesmenCount];

        $user = Auth::user();
        $generatedBy = $user ? (trim((string) ($user->name ?? '')) !== '' ? (string) $user->name : (string) ($user->email ?? '')) : '';
        $now = now();
        $meta = [
            'company' => 'SAGARDUTT PEPSI DISTRIBUTORS',
            'report' => 'Daily Scheme Report',
            'date_range' => 'Date Range: ' . $fromDate . ' to ' . $toDate,
            'generated_line' => 'Generated On Date: ' . $now->toDateString() . '   Generated Time: ' . $now->format('H:i:s') . '   Generated By: ' . $generatedBy,
        ];

        $file = 'daily-scheme-report_' . $fromDate . '_to_' . $toDate . '.xlsx';

        return Excel::download(new DailySchemePivotExport($rows, $headings, $meta, $qtyCols, $currencyCols), $file);
    }
}
