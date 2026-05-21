<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReceivablesController extends Controller
{
    private function normalizeRangePreset(?string $preset): string
    {
        $p = strtolower(trim((string) $preset));
        return in_array($p, ['all', 'today', 'month', 'year', 'custom'], true) ? $p : 'all';
    }

    private function normalizeStatus(?string $status): string
    {
        $s = strtolower(trim((string) $status));
        return in_array($s, ['all', 'outstanding', 'paid', 'partial', 'unpaid', 'overdue'], true) ? $s : 'outstanding';
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

    private function resolveDateRange(Request $request): array
    {
        $preset = $this->normalizeRangePreset($request->query('range'));
        $now = now();

        $from = null;
        $to = null;

        if ($preset === 'today') {
            $from = $now->copy()->startOfDay();
            $to = $now->copy()->endOfDay();
        } elseif ($preset === 'month') {
            $from = $now->copy()->startOfMonth()->startOfDay();
            $to = $now->copy()->endOfMonth()->endOfDay();
        } elseif ($preset === 'year') {
            $from = $now->copy()->startOfYear()->startOfDay();
            $to = $now->copy()->endOfYear()->endOfDay();
        } elseif ($preset === 'custom') {
            $from = $this->parseDate($request->query('start_date'))?->startOfDay();
            $to = $this->parseDate($request->query('end_date'))?->endOfDay();
        }

        if ($from && $to && $from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [
            'preset' => $preset,
            'from' => $from,
            'to' => $to,
            'start_date' => trim((string) $request->query('start_date')),
            'end_date' => trim((string) $request->query('end_date')),
        ];
    }

    private function applyStatusFilterToInvoiceQuery($query, string $status, Carbon $today): void
    {
        if ($status === 'all') {
            return;
        }

        if ($status === 'paid') {
            $query->where('pending_amount', '<=', 0);
            return;
        }

        if ($status === 'partial') {
            $query->where('paid_amount', '>', 0)->where('pending_amount', '>', 0);
            return;
        }

        if ($status === 'unpaid') {
            $query->where('paid_amount', '<=', 0)->where('pending_amount', '>', 0);
            return;
        }

        if ($status === 'overdue') {
            $query->where('pending_amount', '>', 0)->whereRaw('COALESCE(due_date, invoice_date) < ?', [$today->toDateString()]);
            return;
        }

        $query->where('pending_amount', '>', 0);
    }

    private function applyDateRangeToInvoiceQuery($query, ?Carbon $from, ?Carbon $to): void
    {
        if ($from && $to) {
            $query->whereBetween('invoice_date', [$from, $to]);
            return;
        }
        if ($from) {
            $query->where('invoice_date', '>=', $from);
            return;
        }
        if ($to) {
            $query->where('invoice_date', '<=', $to);
        }
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));
        $customerId = $request->query('customer_id');
        $status = $this->normalizeStatus($request->query('status'));
        $range = $this->resolveDateRange($request);

        $today = now()->startOfDay();
        $monthStart = now()->startOfMonth()->startOfDay();
        $monthEnd = now()->endOfMonth()->endOfDay();

        $totalOutstanding = (float) Invoice::query()
            ->accountingActive()
            ->where('pending_amount', '>', 0)
            ->sum('pending_amount');

        $totalOverdue = (float) Invoice::query()
            ->accountingActive()
            ->where('pending_amount', '>', 0)
            ->whereRaw('COALESCE(due_date, invoice_date) < ?', [$today->toDateString()])
            ->sum('pending_amount');

        $thisMonthPending = (float) Invoice::query()
            ->accountingActive()
            ->where('pending_amount', '>', 0)
            ->whereBetween('invoice_date', [$monthStart, $monthEnd])
            ->sum('pending_amount');

        $thisMonthCollections = (float) CustomerPayment::query()
            ->whereBetween('payment_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->sum('amount');

        $months = [];
        $cursor = now()->subMonths(11)->startOfMonth();
        $endCursor = now()->startOfMonth();
        while ($cursor->lte($endCursor)) {
            $months[] = $cursor->copy();
            $cursor->addMonth();
        }

        $firstMonthStart = $months[0]->copy()->startOfMonth()->startOfDay();
        $lastMonthEnd = $months[count($months) - 1]->copy()->endOfMonth()->endOfDay();

        $baselineInvoiced = (float) Invoice::query()
            ->accountingActive()
            ->where('invoice_date', '<', $firstMonthStart)
            ->sum('total_amount');

        $baselinePaid = (float) DB::table('customer_payment_allocations as a')
            ->join('customer_payments as p', 'a.customer_payment_id', '=', 'p.id')
            ->join('invoices as i', 'a.invoice_id', '=', 'i.id')
            ->where('i.is_deleted', false)
            ->whereNull('i.deleted_at')
            ->where('p.payment_date', '<', $firstMonthStart->toDateString())
            ->sum('a.amount');

        $invoicedRows = Invoice::query()
            ->accountingActive()
            ->whereBetween('invoice_date', [$firstMonthStart, $lastMonthEnd])
            ->selectRaw("DATE_FORMAT(invoice_date, '%Y-%m') as ym, COALESCE(SUM(total_amount), 0) as total")
            ->groupBy('ym')
            ->get();

        $invoicedByMonth = [];
        foreach ($invoicedRows as $r) {
            $invoicedByMonth[$r->ym] = (float) $r->total;
        }

        $paidRows = DB::table('customer_payment_allocations as a')
            ->join('customer_payments as p', 'a.customer_payment_id', '=', 'p.id')
            ->join('invoices as i', 'a.invoice_id', '=', 'i.id')
            ->where('i.is_deleted', false)
            ->whereNull('i.deleted_at')
            ->whereBetween('p.payment_date', [$firstMonthStart->toDateString(), $lastMonthEnd->toDateString()])
            ->selectRaw("DATE_FORMAT(p.payment_date, '%Y-%m') as ym, COALESCE(SUM(a.amount), 0) as total")
            ->groupBy('ym')
            ->get();

        $paidByMonth = [];
        foreach ($paidRows as $r) {
            $paidByMonth[$r->ym] = (float) $r->total;
        }

        $trendLabels = [];
        $trendValues = [];
        $cumInv = 0.0;
        $cumPaid = 0.0;
        foreach ($months as $m) {
            $ym = $m->format('Y-m');
            $trendLabels[] = $m->format('M Y');
            $cumInv += (float) ($invoicedByMonth[$ym] ?? 0);
            $cumPaid += (float) ($paidByMonth[$ym] ?? 0);
            $out = ($baselineInvoiced + $cumInv) - ($baselinePaid + $cumPaid);
            $trendValues[] = max(0, round($out, 6));
        }

        $query = DB::table('invoices')
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->where('invoices.is_deleted', false)
            ->whereNull('invoices.deleted_at')
            ->selectRaw('customers.id as customer_id')
            ->selectRaw('customers.name as customer_name')
            ->selectRaw('COALESCE(SUM(invoices.total_amount), 0) as total_invoice_amount')
            ->selectRaw('COALESCE(SUM(invoices.paid_amount), 0) as total_paid')
            ->selectRaw('COALESCE(SUM(invoices.pending_amount), 0) as total_pending')
            ->selectRaw('MAX(CASE WHEN COALESCE(invoices.paid_amount, 0) > 0 THEN invoices.updated_at ELSE NULL END) as last_payment_date')
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('total_pending');

        if (!empty($customerId)) {
            $query->where('invoices.customer_id', $customerId);
        }

        $this->applyStatusFilterToInvoiceQuery($query, $status, $today);
        $this->applyDateRangeToInvoiceQuery($query, $range['from'], $range['to']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('customers.name', 'like', '%' . $search . '%')
                    ->orWhere('invoices.invoice_no', 'like', '%' . $search . '%');
            });
        }

        $rows = $query->get();
        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        return view('receivables.index', [
            'rows' => $rows,
            'search' => $search,
            'customers' => $customers,
            'customerId' => $customerId,
            'status' => $status,
            'range' => $range,
            'summary' => [
                'total_outstanding' => $totalOutstanding,
                'total_overdue' => $totalOverdue,
                'this_month_pending' => $thisMonthPending,
                'this_month_collections' => $thisMonthCollections,
            ],
            'trend' => [
                'labels' => $trendLabels,
                'values' => $trendValues,
            ],
        ]);
    }

    public function show($customerId)
    {
        $customer = Customer::withTrashed()->findOrFail($customerId);

        $invoices = Invoice::active()
            ->whereNull('deleted_at')
            ->where('customer_id', $customer->id)
            ->orderByDesc('invoice_date')
            ->paginate(20);

        $summary = Invoice::active()
            ->whereNull('deleted_at')
            ->where('customer_id', $customer->id)
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_invoice_amount')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as total_paid')
            ->selectRaw('COALESCE(SUM(pending_amount), 0) as total_pending')
            ->selectRaw('MAX(CASE WHEN COALESCE(paid_amount, 0) > 0 THEN updated_at ELSE NULL END) as last_payment_date')
            ->first();

        return view('receivables.show', compact('customer', 'invoices', 'summary'));
    }

    public function invoices(Request $request)
    {
        $customerId = $request->query('customer_id');
        $status = $this->normalizeStatus($request->query('status'));
        $search = trim((string) $request->query('search'));
        $range = $this->resolveDateRange($request);

        $invoicesQuery = Invoice::query()
            ->active()
            ->with('customer')
            ->whereNull('deleted_at');

        if (!empty($customerId)) {
            $invoicesQuery->where('customer_id', $customerId);
        }

        $this->applyStatusFilterToInvoiceQuery($invoicesQuery, $status, now()->startOfDay());
        $this->applyDateRangeToInvoiceQuery($invoicesQuery, $range['from'], $range['to']);

        if ($search !== '') {
            $invoicesQuery->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', '%' . $search . '%')
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($status === 'paid') {
            $invoicesQuery->orderByDesc('invoice_date');
        } else {
            $invoicesQuery->orderByDesc('pending_amount')->orderByDesc('invoice_date');
        }

        $invoices = $invoicesQuery->paginate(20)->withQueryString();
        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        return view('receivables.invoices', [
            'invoices' => $invoices,
            'customers' => $customers,
            'customerId' => $customerId,
            'status' => $status,
            'search' => $search,
            'range' => $range,
        ]);
    }

    public function aging(Request $request)
    {
        $customerId = $request->query('customer_id');
        $status = $this->normalizeStatus($request->query('status'));
        $range = $this->resolveDateRange($request);

        $base = Invoice::query()
            ->active()
            ->whereNull('deleted_at');

        if (!empty($customerId)) {
            $base->where('customer_id', $customerId);
        }

        $today = now()->startOfDay();
        $this->applyStatusFilterToInvoiceQuery($base, $status, $today);
        $this->applyDateRangeToInvoiceQuery($base, $range['from'], $range['to']);

        $invoices = (clone $base)
            ->with('customer')
            ->orderBy('invoice_date')
            ->get([
                'id',
                'invoice_no',
                'customer_id',
                'invoice_date',
                'due_date',
                'total_amount',
                'paid_amount',
                'pending_amount',
            ]);

        $buckets = [
            '0_7' => 0.0,
            '8_15' => 0.0,
            '16_30' => 0.0,
            '30_plus' => 0.0,
        ];

        foreach ($invoices as $inv) {
            $due = $inv->due_date ?? $inv->invoice_date;
            $ageDays = $due ? Carbon::parse($due)->startOfDay()->diffInDays($today, false) : 0;
            if ($ageDays < 0) {
                $ageDays = 0;
            }

            $pending = round((float) ($inv->pending_amount ?? 0), 6);
            if ($pending <= 0) {
                continue;
            }

            if ($ageDays <= 7) {
                $buckets['0_7'] += $pending;
            } elseif ($ageDays <= 15) {
                $buckets['8_15'] += $pending;
            } elseif ($ageDays <= 30) {
                $buckets['16_30'] += $pending;
            } else {
                $buckets['30_plus'] += $pending;
            }
        }

        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        return view('receivables.aging', [
            'customers' => $customers,
            'customerId' => $customerId,
            'status' => $status,
            'range' => $range,
            'buckets' => $buckets,
        ]);
    }
}
