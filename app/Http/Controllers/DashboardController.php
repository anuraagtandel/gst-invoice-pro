<?php

namespace App\Http\Controllers;

use App\Helpers\TradePriceResolver;
use App\Models\Invoice;
use App\Models\Purchase;
use App\Models\Scheme;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private function normalizePreset(?string $preset): string
    {
        $p = strtolower(trim((string) $preset));
        return in_array($p, ['today', 'week', 'month', 'custom'], true) ? $p : 'today';
    }

    private function parseDate(?string $value): ?Carbon
    {
        $v = trim((string) $value);
        if ($v === '') {
            return null;
        }
        try {
            return Carbon::parse($v);
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveRange(Request $request): array
    {
        $preset = $this->normalizePreset($request->query('range'));

        $now = now();
        if ($preset === 'week') {
            $from = $now->copy()->startOfWeek()->startOfDay();
            $to = $now->copy()->endOfWeek()->endOfDay();
        } elseif ($preset === 'month') {
            $from = $now->copy()->startOfMonth()->startOfDay();
            $to = $now->copy()->endOfMonth()->endOfDay();
        } elseif ($preset === 'custom') {
            $from = $this->parseDate($request->query('from_date'))?->startOfDay() ?? $now->copy()->startOfDay();
            $to = $this->parseDate($request->query('to_date'))?->endOfDay() ?? $now->copy()->endOfDay();
        } else {
            $from = $now->copy()->startOfDay();
            $to = $now->copy()->endOfDay();
        }

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $days = $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;
        $prevTo = $from->copy()->subDay()->endOfDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1)->startOfDay();

        $label = match ($preset) {
            'today' => 'Today',
            'week' => 'This Week',
            'month' => 'This Month',
            default => 'Custom Range',
        };

        return [
            'preset' => $preset,
            'label' => $label,
            'from' => $from,
            'to' => $to,
            'prev_from' => $prevFrom,
            'prev_to' => $prevTo,
            'days' => $days,
        ];
    }

    private function pctChange(float $current, float $previous): ?float
    {
        if (abs($previous) < 0.0000001) {
            return null;
        }
        return (($current - $previous) / $previous) * 100;
    }

    private function fillSeries(array $map, Carbon $from, Carbon $to): array
    {
        $series = [];
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();
        while ($cursor->lte($end)) {
            $k = $cursor->toDateString();
            $series[] = (float) ($map[$k] ?? 0);
            $cursor->addDay();
        }
        return $series;
    }

    private function normalizeLimit(mixed $value, int $default = 20): int
    {
        $n = (int) $value;
        if ($n <= 0) {
            return $default;
        }
        return min(200, $n);
    }

    private function normalizeOffset(mixed $value): int
    {
        $n = (int) $value;
        return max(0, $n);
    }

    private function dashboardProductsQuery(Carbon $from, Carbon $to)
    {
        $productNameExpr = "TRIM(CONCAT(COALESCE(products.name,''), CASE WHEN COALESCE(products.volume,'') <> '' THEN CONCAT(' ', products.volume) ELSE '' END))";

        return DB::table('products')
            ->leftJoin('invoice_items', function ($join) {
                $join->on('products.id', '=', 'invoice_items.product_id')
                    ->where('invoice_items.is_free', false);
            })
            ->leftJoin('invoices', function ($join) use ($from, $to) {
                $join->on('invoice_items.invoice_id', '=', 'invoices.id')
                    ->where('invoices.is_deleted', false)
                    ->whereNull('invoices.deleted_at')
                    ->whereBetween('invoices.invoice_date', [$from, $to]);
            })
            ->where('products.is_active', true)
            ->whereNull('products.deleted_at')
            ->selectRaw("$productNameExpr as product_name")
            ->selectRaw('COALESCE(SUM(CASE WHEN invoices.id IS NULL THEN 0 ELSE invoice_items.qty_ct END), 0) as qty_ct')
            ->selectRaw('COALESCE(SUM(CASE WHEN invoices.id IS NULL THEN 0 ELSE invoice_items.qty_un END), 0) as qty_un')
            ->selectRaw('COALESCE(SUM(CASE WHEN invoices.id IS NULL THEN 0 ELSE invoice_items.line_total END), 0) as revenue')
            ->groupBy('products.id', 'products.name', 'products.volume')
            ->orderByDesc('revenue')
            ->orderBy('products.name')
            ->orderBy('products.id');
    }

    private function loadProductsChunk(Carbon $from, Carbon $to, int $offset, int $limit): array
    {
        $rows = $this->dashboardProductsQuery($from, $to)
            ->offset($offset)
            ->limit($limit + 1)
            ->get();

        $hasMore = $rows->count() > $limit;
        $slice = $hasMore ? $rows->slice(0, $limit)->values() : $rows->values();

        return [
            'items' => $slice,
            'has_more' => $hasMore,
            'next_offset' => $offset + $slice->count(),
        ];
    }

    private function buildPayload(Request $request): array
    {
        $range = $this->resolveRange($request);
        $from = $range['from'];
        $to = $range['to'];
        $prevFrom = $range['prev_from'];
        $prevTo = $range['prev_to'];

        $unitsPerCarton = (int) (DB::table('products')
            ->where('products.is_active', true)
            ->whereNull('products.deleted_at')
            ->where('products.pack_size', '>', 0)
            ->min('products.pack_size') ?? 1);
        if ($unitsPerCarton <= 0) {
            $unitsPerCarton = 1;
        }

        $invoiceBase = Invoice::active()->whereBetween('invoice_date', [$from, $to]);
        $purchaseBase = Purchase::query()->whereNull('deleted_at')->whereBetween('bill_date', [$from, $to]);

        $salesLineTotal = (float) DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.is_deleted', false)
            ->whereNull('invoices.deleted_at')
            ->whereBetween('invoices.invoice_date', [$from, $to])
            ->where('invoice_items.is_free', false)
            ->selectRaw('COALESCE(SUM(invoice_items.line_total), 0) as amt')
            ->value('amt');
        $salesRoundOff = (float) (clone $invoiceBase)->sum('round_off');
        $sales = $salesLineTotal + $salesRoundOff;
        $purchase = (float) (clone $purchaseBase)->sum('total_amount');
        $profit = $sales - $purchase;

        $prevSalesLineTotal = (float) DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.is_deleted', false)
            ->whereNull('invoices.deleted_at')
            ->whereBetween('invoices.invoice_date', [$prevFrom, $prevTo])
            ->where('invoice_items.is_free', false)
            ->selectRaw('COALESCE(SUM(invoice_items.line_total), 0) as amt')
            ->value('amt');
        $prevSalesRoundOff = (float) Invoice::active()->whereBetween('invoice_date', [$prevFrom, $prevTo])->sum('round_off');
        $prevSales = $prevSalesLineTotal + $prevSalesRoundOff;
        $prevPurchase = (float) Purchase::query()->whereNull('deleted_at')->whereBetween('bill_date', [$prevFrom, $prevTo])->sum('total_amount');
        $prevProfit = $prevSales - $prevPurchase;

        $totalInvoices = (int) (clone $invoiceBase)->count();

        $soldCt = (float) DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.is_deleted', false)
            ->whereNull('invoices.deleted_at')
            ->whereBetween('invoices.invoice_date', [$from, $to])
            ->where('invoice_items.is_free', false)
            ->selectRaw('COALESCE(SUM(invoice_items.qty_ct), 0) as qty')
            ->value('qty');
        $soldQty = [
            'ct' => $soldCt,
            'un' => 0,
        ];

        $avgOrderValue = $totalInvoices > 0 ? ($sales / $totalInvoices) : 0.0;

        $totalPurchaseOrders = (int) (clone $purchaseBase)->count();
        $purchaseQty = (float) DB::table('purchase_items')
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->whereNull('purchases.deleted_at')
            ->whereBetween('purchases.bill_date', [$from, $to])
            ->selectRaw('COALESCE(SUM(purchase_items.total_units), 0) as qty')
            ->value('qty');

        $outstanding = (float) Invoice::active()
            ->whereBetween('invoice_date', [$from, $to])
            ->where('pending_amount', '>', 0)
            ->sum('pending_amount');

        $overdue = (float) Invoice::active()
            ->whereBetween('invoice_date', [$from, $to])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->where('pending_amount', '>', 0)
            ->sum('pending_amount');

        $paymentsReceived = (float) DB::table('customer_payments')
            ->whereBetween('payment_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('COALESCE(SUM(amount), 0) as amt')
            ->value('amt');

        $prevOutstanding = (float) Invoice::active()
            ->whereNull('deleted_at')
            ->where('pending_amount', '>', 0)
            ->whereDate('invoice_date', '<=', $prevTo->toDateString())
            ->sum('pending_amount');

        $schemeStats = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->leftJoin('products', 'invoice_items.product_id', '=', 'products.id')
            ->where('invoices.is_deleted', false)
            ->whereNull('invoices.deleted_at')
            ->whereBetween('invoices.invoice_date', [$from, $to])
            ->where('invoice_items.is_free', true)
            ->selectRaw('COUNT(DISTINCT invoice_items.invoice_id) as invoices_with_free')
            ->selectRaw('COALESCE(SUM(invoice_items.qty_ct), 0) as free_qty_ct')
            ->selectRaw('COALESCE(SUM(invoice_items.qty_un), 0) as free_qty_un')
            ->selectRaw('COALESCE(SUM(invoice_items.total_units * COALESCE(products.trade_price, 0)), 0) as free_value_trade')
            ->first();

        $schemesApplied = (int) ($schemeStats->invoices_with_free ?? 0);
        $freeQty = [
            'ct' => (float) ($schemeStats->free_qty_ct ?? 0),
            'un' => (float) ($schemeStats->free_qty_un ?? 0),
        ];
        $freeValue = (float) ($schemeStats->free_value_trade ?? 0);

        $thresholdCartons = 5;
        $stockExpression = 'products.opening_stock_units + COALESCE(SUM(CASE
            WHEN stock_transactions.type = "purchase" THEN stock_transactions.quantity_in_units
            WHEN stock_transactions.type = "sale" THEN -stock_transactions.quantity_in_units
            WHEN stock_transactions.type = "adjustment" THEN stock_transactions.quantity_in_units
            ELSE 0 END), 0)';

        $stockPerProduct = DB::table('products')
            ->leftJoin('stock_transactions', 'products.id', '=', 'stock_transactions.product_id')
            ->where('products.is_active', true)
            ->whereNull('products.deleted_at')
            ->groupBy('products.id', 'products.base_price', 'products.opening_stock_units')
            ->selectRaw('products.id, COALESCE(products.base_price, 0) as base_price')
            ->selectRaw("($stockExpression) as current_units");

        $totalStockValue = (float) DB::query()
            ->fromSub($stockPerProduct, 't')
            ->selectRaw('COALESCE(SUM(t.current_units * t.base_price), 0) as total_value')
            ->value('total_value');

        $kpiSections = [
            [
                'title' => 'Sales & Business',
                'tone' => 'blue',
                'items' => [
                    ['title' => 'Total Sales (₹)', 'value' => $sales, 'kind' => 'currency', 'tone' => 'blue'],
                    ['title' => 'Total Invoices', 'value' => $totalInvoices, 'kind' => 'count', 'tone' => 'blue'],
                    ['title' => 'Total Quantity Sold', 'value' => $soldQty, 'kind' => 'ct_only', 'tone' => 'blue'],
                    ['title' => 'Average Order Value', 'value' => $avgOrderValue, 'kind' => 'currency', 'tone' => 'blue'],
                ],
            ],
            [
                'title' => 'Purchase',
                'tone' => 'purple',
                'items' => [
                    ['title' => 'Total Purchase (₹)', 'value' => $purchase, 'kind' => 'currency', 'tone' => 'purple'],
                    ['title' => 'Total Purchase Orders', 'value' => $totalPurchaseOrders, 'kind' => 'count', 'tone' => 'purple'],
                    ['title' => 'Purchase Quantity', 'value' => $purchaseQty, 'kind' => 'qty', 'tone' => 'purple'],
                ],
            ],
            [
                'title' => 'Receivables',
                'tone' => 'red',
                'items' => [
                    ['title' => 'Total Outstanding (₹)', 'value' => $outstanding, 'kind' => 'currency', 'tone' => 'red'],
                    ['title' => 'Overdue Amount (₹)', 'value' => $overdue, 'kind' => 'currency', 'tone' => 'red'],
                    ['title' => 'Payments Received (₹)', 'value' => $paymentsReceived, 'kind' => 'currency', 'tone' => 'red'],
                ],
            ],
            [
                'title' => 'Schemes',
                'tone' => 'orange',
                'items' => [
                    ['title' => 'Total Schemes Applied', 'value' => $schemesApplied, 'kind' => 'count', 'tone' => 'orange'],
                    ['title' => 'Total Free Items Given (Qty)', 'value' => $freeQty, 'kind' => 'ctun', 'tone' => 'orange'],
                    ['title' => 'Total Free Items Given (₹)', 'value' => $freeValue, 'kind' => 'currency', 'tone' => 'orange'],
                ],
            ],
            [
                'title' => 'Stock',
                'tone' => 'yellow',
                'items' => [
                    ['title' => 'Total Stock Value (₹)', 'value' => $totalStockValue, 'kind' => 'currency', 'tone' => 'yellow'],
                ],
            ],
        ];

        $initialProductLimit = 7;
        $productsChunk = $this->loadProductsChunk($from, $to, 0, $initialProductLimit);
        $topProducts = $productsChunk['items'];

        $schemeItems = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->leftJoin('products', 'invoice_items.product_id', '=', 'products.id')
            ->where('invoices.is_deleted', false)
            ->whereNull('invoices.deleted_at')
            ->whereBetween('invoices.invoice_date', [$from, $to])
            ->where('invoice_items.is_free', true)
            ->selectRaw('invoice_items.product_id as product_id')
            ->selectRaw('COALESCE(products.name, invoice_items.product_description) as product_name')
            ->selectRaw('COALESCE(products.volume, \'\') as volume')
            ->selectRaw('COALESCE(products.pack_size, 1) as pack_size')
            ->selectRaw('COALESCE(SUM(invoice_items.total_units), 0) as units')
            ->groupBy('invoice_items.product_id', 'product_name', 'volume', 'pack_size')
            ->orderByDesc('units')
            ->limit(50)
            ->get();

        $outOfStockProducts = DB::table('products')
            ->leftJoin('stock_transactions', 'products.id', '=', 'stock_transactions.product_id')
            ->where('products.is_active', true)
            ->whereNull('products.deleted_at')
            ->groupBy('products.id', 'products.name', 'products.volume', 'products.pack_size', 'products.opening_stock_units')
            ->havingRaw("($stockExpression) <= 0")
            ->select('products.id', 'products.name', 'products.volume', 'products.pack_size')
            ->selectRaw("($stockExpression) as current_units")
            ->orderBy('current_units')
            ->limit(8)
            ->get();

        $lowStockProducts = DB::table('products')
            ->leftJoin('stock_transactions', 'products.id', '=', 'stock_transactions.product_id')
            ->where('products.is_active', true)
            ->whereNull('products.deleted_at')
            ->groupBy('products.id', 'products.name', 'products.volume', 'products.pack_size', 'products.opening_stock_units')
            ->havingRaw("($stockExpression) > 0")
            ->havingRaw("($stockExpression) < (products.pack_size * ?)", [$thresholdCartons])
            ->select('products.id', 'products.name', 'products.volume', 'products.pack_size')
            ->selectRaw("($stockExpression) as current_units")
            ->orderBy('current_units')
            ->limit(8)
            ->get();

        $recentInvoices = Invoice::active()
            ->with('customer')
            ->whereBetween('invoice_date', [$from, $to])
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'invoice_no', 'invoice_date', 'customer_id', 'grand_total', 'payment_status', 'pending_amount'])
            ->map(function (Invoice $inv) {
                return [
                    'id' => $inv->id,
                    'invoice_no' => $inv->invoice_no,
                    'invoice_date' => $inv->invoice_date?->toDateString(),
                    'grand_total' => (float) ($inv->grand_total ?? 0),
                    'payment_status' => $inv->payment_status,
                    'pending_amount' => (float) ($inv->pending_amount ?? 0),
                    'customer' => [
                        'id' => $inv->customer?->id,
                        'name' => $inv->customer?->name,
                    ],
                ];
            })
            ->values();

        return [
            'range' => [
                'preset' => $range['preset'],
                'label' => $range['label'],
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'kpi_sections' => $kpiSections,
            'top_products' => $topProducts,
            'products_has_more' => (bool) $productsChunk['has_more'],
            'products_next_offset' => (int) $productsChunk['next_offset'],
            'scheme_items' => $schemeItems,
            'stock' => [
                'threshold_cartons' => $thresholdCartons,
                'total_stock_value' => $totalStockValue,
                'out_of_stock' => $outOfStockProducts,
                'low_stock' => $lowStockProducts,
            ],
            'recent_invoices' => $recentInvoices,
            'salesman_report' => $this->getSalesmanReport($from, $to),
        ];
    }

    public function products(Request $request)
    {
        $range = $this->resolveRange($request);
        $from = $range['from'];
        $to = $range['to'];

        $offset = $this->normalizeOffset($request->query('offset', 0));
        $limit = $this->normalizeLimit($request->query('limit', 50), 50);

        $chunk = $this->loadProductsChunk($from, $to, $offset, $limit);

        return response()->json([
            'items' => $chunk['items'],
            'has_more' => (bool) $chunk['has_more'],
            'next_offset' => (int) $chunk['next_offset'],
        ]);
    }

    private function getSalesmanReport($from, $to)
    {
        $schemes = Scheme::query()
            ->where('is_active', true)
            ->with('schemeSlabs')
            ->get();

        $schemesByProductId = [];
        foreach ($schemes as $s) {
            $pid = (int) ($s->product_id ?? 0);
            if ($pid <= 0) {
                continue;
            }
            if (!isset($schemesByProductId[$pid])) {
                $schemesByProductId[$pid] = [];
            }
            $schemesByProductId[$pid][] = $s;
        }

        $rows = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->leftJoin('products', 'invoice_items.product_id', '=', 'products.id')
            ->where('invoices.is_deleted', false)
            ->whereNull('invoices.deleted_at')
            ->whereBetween('invoices.invoice_date', [$from, $to])
            ->selectRaw('invoices.id as invoice_id')
            ->selectRaw('invoices.invoice_date as invoice_date')
            ->addSelect('invoices.salesman')
            ->addSelect('invoices.round_off')
            ->addSelect('invoice_items.product_id')
            ->addSelect('invoice_items.product_description')
            ->addSelect('invoice_items.is_free')
            ->addSelect('invoice_items.qty_ct')
            ->addSelect('invoice_items.qty_un')
            ->addSelect('invoice_items.total_units')
            ->addSelect('invoice_items.line_total')
            ->addSelect('invoice_items.base_price')
            ->addSelect('invoice_items.cgst_rate')
            ->addSelect('invoice_items.sgst_rate')
            ->addSelect('invoice_items.cess_rate')
            ->addSelect('products.name as product_name_db')
            ->addSelect('products.volume as product_volume')
            ->addSelect('products.pack_size as product_pack_size')
            ->addSelect('products.gst_rate as product_gst_rate')
            ->addSelect('products.trade_price as product_trade_price')
            ->orderBy('invoices.id')
            ->get();

        $salesmenData = [];
        $invoicesAgg = [];
        $roundOffByInvoice = [];

        foreach ($rows as $row) {
            $salesmanName = trim((string) $row->salesman) !== '' ? trim((string) $row->salesman) : 'Other';
            $invoiceId = (int) $row->invoice_id;
            $productId = $row->product_id === null ? null : (int) $row->product_id;
            if (!isset($roundOffByInvoice[$invoiceId])) {
                $roundOffByInvoice[$invoiceId] = (float) ($row->round_off ?? 0);
            }

            if (!isset($salesmenData[$salesmanName])) {
                $salesmenData[$salesmanName] = [
                    'name' => $salesmanName,
                    'total_sales' => 0,
                    'total_qty_ct' => 0,
                    'total_qty_un' => 0,
                    'total_invoices_map' => [],
                    'total_scheme_value' => 0,
                    'products_map' => [],
                ];
            }
            $salesmenData[$salesmanName]['total_invoices_map'][$invoiceId] = true;

            $productDisplayName = trim((string) ($row->product_name_db ?? ''));
            if ($productDisplayName !== '') {
                $productDisplayName = trim($productDisplayName . ' ' . trim((string) ($row->product_volume ?? '')));
            } else {
                $productDisplayName = trim((string) ($row->product_description ?? ''));
            }

            $productKey = $productId !== null ? (string) $productId : ('desc_' . md5($productDisplayName));
            if (!isset($salesmenData[$salesmanName]['products_map'][$productKey])) {
                $salesmenData[$salesmanName]['products_map'][$productKey] = [
                    'product_id' => $productId,
                    'name' => $productDisplayName !== '' ? $productDisplayName : '-',
                    'qty_ct' => 0,
                    'qty_un' => 0,
                    'total_amount' => 0,
                    'free_ct' => 0,
                    'free_un' => 0,
                    'schemes_map' => [],
                ];
            }

            if (!isset($invoicesAgg[$invoiceId])) {
                $invoicesAgg[$invoiceId] = [
                    'salesman' => $salesmanName,
                    'invoice_date' => (string) ($row->invoice_date ?? ''),
                    'sold' => [],
                    'free' => [],
                ];
            }

            $qtyCt = (float) ($row->qty_ct ?? 0);
            $qtyUn = (float) ($row->qty_un ?? 0);
            $totalUnits = (float) ($row->total_units ?? 0);
            $packSize = (int) ($row->product_pack_size ?? 1);
            if ($packSize <= 0) {
                $packSize = 1;
            }

            if ((bool) $row->is_free) {
                $tradePrice = TradePriceResolver::unit($row->product_trade_price ?? null);
                $value = $tradePrice * (float) $totalUnits;
                $salesmenData[$salesmanName]['total_scheme_value'] += $value;

                if ($productId !== null) {
                    if (!isset($invoicesAgg[$invoiceId]['free'][$productId])) {
                        $invoicesAgg[$invoiceId]['free'][$productId] = [
                            'units' => 0,
                            'value' => 0,
                            'pack_size' => $packSize,
                        ];
                    }
                    $invoicesAgg[$invoiceId]['free'][$productId]['units'] += $totalUnits;
                    $invoicesAgg[$invoiceId]['free'][$productId]['value'] += $value;
                }
                continue;
            }

            $salesmenData[$salesmanName]['products_map'][$productKey]['qty_ct'] += $qtyCt;
            $salesmenData[$salesmanName]['products_map'][$productKey]['qty_un'] += $qtyUn;
            $salesmenData[$salesmanName]['products_map'][$productKey]['total_amount'] += (float) ($row->line_total ?? 0);
            $salesmenData[$salesmanName]['total_sales'] += (float) ($row->line_total ?? 0);
            $salesmenData[$salesmanName]['total_qty_ct'] += $qtyCt;
            $salesmenData[$salesmanName]['total_qty_un'] += $qtyUn;

            if ($productId !== null) {
                if (!isset($invoicesAgg[$invoiceId]['sold'][$productId])) {
                    $invoicesAgg[$invoiceId]['sold'][$productId] = [
                        'qty_ct' => 0,
                        'qty_un' => 0,
                        'total_units' => 0,
                        'product_key' => $productKey,
                        'pack_size' => $packSize,
                    ];
                }
                $invoicesAgg[$invoiceId]['sold'][$productId]['qty_ct'] += $qtyCt;
                $invoicesAgg[$invoiceId]['sold'][$productId]['qty_un'] += $qtyUn;
                $invoicesAgg[$invoiceId]['sold'][$productId]['total_units'] += $totalUnits;
            }
        }

        foreach ($invoicesAgg as $inv) {
            $salesmanName = $inv['salesman'];
            $invDateStr = (string) ($inv['invoice_date'] ?? '');
            $invDate = null;
            try {
                $invDate = Carbon::parse($invDateStr);
            } catch (\Throwable) {
                $invDate = null;
            }

            $remainingFree = $inv['free'];

            foreach ($inv['sold'] as $pid => $sold) {
                $productId = (int) $pid;
                $productKey = (string) ($sold['product_key'] ?? $productId);
                if (!isset($salesmenData[$salesmanName]['products_map'][$productKey])) {
                    continue;
                }

                $qtyToCheckCt = (float) ($sold['qty_ct'] ?? 0);
                $qtyToCheckUnits = (float) ($sold['total_units'] ?? 0);

                $matchedAny = false;
                $candidates = $schemesByProductId[$productId] ?? [];

                foreach ($candidates as $scheme) {
                    if ($invDate) {
                        $vf = $scheme->valid_from;
                        $vt = $scheme->valid_to;
                        if ($vf && $invDate->lt(Carbon::parse($vf)->startOfDay())) {
                            continue;
                        }
                        if ($vt && $invDate->gt(Carbon::parse($vt)->endOfDay())) {
                            continue;
                        }
                    }

                    $qtyToCheck = strtoupper((string) ($scheme->slab_unit ?? '')) === 'CT' ? $qtyToCheckCt : $qtyToCheckUnits;

                    $slab = null;
                    foreach ($scheme->schemeSlabs as $ss) {
                        $min = (float) ($ss->min_qty ?? 0);
                        $max = $ss->max_qty === null ? null : (float) $ss->max_qty;
                        if ($qtyToCheck >= $min && ($max === null || $qtyToCheck <= $max)) {
                            $slab = $ss;
                            break;
                        }
                    }
                    if (!$slab) {
                        continue;
                    }

                    $freeQtyNeeded = (float) ($slab->free_qty ?? 0);
                    if ($freeQtyNeeded <= 0) {
                        continue;
                    }

                    $freePid = $slab->free_product_id ? (int) $slab->free_product_id : $productId;
                    if (!isset($remainingFree[$freePid]) || (float) $remainingFree[$freePid]['units'] <= 0) {
                        continue;
                    }

                    $freeUnitsAvail = (float) $remainingFree[$freePid]['units'];
                    if ($freeUnitsAvail + 0.000001 < $freeQtyNeeded) {
                        continue;
                    }

                    $freeValueAvail = (float) ($remainingFree[$freePid]['value'] ?? 0);
                    $perUnitValue = $freeUnitsAvail > 0 ? ($freeValueAvail / $freeUnitsAvail) : 0.0;
                    $allocUnits = min($freeQtyNeeded, $freeUnitsAvail);
                    $allocValue = $perUnitValue * $allocUnits;
                    $remainingFree[$freePid]['units'] = $freeUnitsAvail - $allocUnits;
                    $remainingFree[$freePid]['value'] = max(0, $freeValueAvail - $allocValue);

                    $packSize = (int) ($remainingFree[$freePid]['pack_size'] ?? ($sold['pack_size'] ?? 1));
                    if ($packSize <= 0) {
                        $packSize = 1;
                    }
                    $ct = (int) floor($allocUnits / $packSize);
                    $un = (int) round($allocUnits - ($ct * $packSize));
                    if ($un < 0) {
                        $un = 0;
                    }

                    $schemeName = (string) ($scheme->name ?? 'Scheme');
                    if (!isset($salesmenData[$salesmanName]['products_map'][$productKey]['schemes_map'][$schemeName])) {
                        $salesmenData[$salesmanName]['products_map'][$productKey]['schemes_map'][$schemeName] = [
                            'name' => $schemeName,
                            'free_ct' => 0,
                            'free_un' => 0,
                            'scheme_value' => 0,
                        ];
                    }
                    $salesmenData[$salesmanName]['products_map'][$productKey]['schemes_map'][$schemeName]['free_ct'] += $ct;
                    $salesmenData[$salesmanName]['products_map'][$productKey]['schemes_map'][$schemeName]['free_un'] += $un;
                    $salesmenData[$salesmanName]['products_map'][$productKey]['schemes_map'][$schemeName]['scheme_value'] += $allocValue;

                    $salesmenData[$salesmanName]['products_map'][$productKey]['free_ct'] += $ct;
                    $salesmenData[$salesmanName]['products_map'][$productKey]['free_un'] += $un;

                    $matchedAny = true;
                }

                if (!$matchedAny) {
                    if (isset($remainingFree[$productId]) && (float) $remainingFree[$productId]['units'] > 0) {
                        $freeUnitsAvail = (float) $remainingFree[$productId]['units'];
                        $freeValueAvail = (float) ($remainingFree[$productId]['value'] ?? 0);
                        $packSize = (int) ($remainingFree[$productId]['pack_size'] ?? ($sold['pack_size'] ?? 1));
                        if ($packSize <= 0) {
                            $packSize = 1;
                        }
                        $ct = (int) floor($freeUnitsAvail / $packSize);
                        $un = (int) round($freeUnitsAvail - ($ct * $packSize));
                        if ($un < 0) {
                            $un = 0;
                        }

                        $schemeName = 'Scheme Applied';
                        if (!isset($salesmenData[$salesmanName]['products_map'][$productKey]['schemes_map'][$schemeName])) {
                            $salesmenData[$salesmanName]['products_map'][$productKey]['schemes_map'][$schemeName] = [
                                'name' => $schemeName,
                                'free_ct' => 0,
                                'free_un' => 0,
                                'scheme_value' => 0,
                            ];
                        }
                        $salesmenData[$salesmanName]['products_map'][$productKey]['schemes_map'][$schemeName]['free_ct'] += $ct;
                        $salesmenData[$salesmanName]['products_map'][$productKey]['schemes_map'][$schemeName]['free_un'] += $un;
                        $salesmenData[$salesmanName]['products_map'][$productKey]['schemes_map'][$schemeName]['scheme_value'] += $freeValueAvail;

                        $salesmenData[$salesmanName]['products_map'][$productKey]['free_ct'] += $ct;
                        $salesmenData[$salesmanName]['products_map'][$productKey]['free_un'] += $un;

                        $remainingFree[$productId]['units'] = 0;
                        $remainingFree[$productId]['value'] = 0;
                    }
                }
            }
        }

        foreach ($roundOffByInvoice as $invoiceId => $roundOff) {
            $salesmanName = $invoicesAgg[$invoiceId]['salesman'] ?? null;
            if (!$salesmanName || !isset($salesmenData[$salesmanName])) {
                continue;
            }
            $salesmenData[$salesmanName]['total_sales'] += (float) $roundOff;
        }

        foreach ($salesmenData as &$salesman) {
            $salesman['total_invoices'] = count($salesman['total_invoices_map']);
            unset($salesman['total_invoices_map']);

            $products = [];
            foreach ($salesman['products_map'] as $p) {
                $schemesList = array_values($p['schemes_map']);
                usort($schemesList, function ($a, $b) {
                    return ($b['scheme_value'] ?? 0) <=> ($a['scheme_value'] ?? 0);
                });
                unset($p['schemes_map']);
                $p['schemes'] = $schemesList;
                $products[] = $p;
            }
            unset($salesman['products_map']);

            usort($products, function ($a, $b) {
                return ($b['total_amount'] ?? 0) <=> ($a['total_amount'] ?? 0);
            });
            $salesman['products'] = $products;
        }

        uasort($salesmenData, function ($a, $b) {
            return ($b['total_sales'] ?? 0) <=> ($a['total_sales'] ?? 0);
        });

        $ranked = array_values($salesmenData);
        foreach ($ranked as $i => &$s) {
            $s['rank'] = $i + 1;
            $s['is_top'] = ($i === 0 && (float) ($s['total_sales'] ?? 0) > 0);
        }

        return $ranked;
    }

    public function index(Request $request)
    {
        $payload = $this->buildPayload($request);
        return view('dashboard', ['payload' => $payload]);
    }

    public function data(Request $request)
    {
        return response()->json($this->buildPayload($request));
    }
}
