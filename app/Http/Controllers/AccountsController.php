<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AccountsController extends Controller
{
    public function customerLedger(Request $request)
    {
        return view('accounts.customer-ledger');
    }

    public function customerLedgerSearch(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'all'));
        $paymentStatus = trim((string) $request->query('payment_status', 'all'));
        $aging = trim((string) $request->query('aging', 'all'));

        $from = $request->filled('from_date') ? Carbon::parse($request->query('from_date'))->startOfDay() : null;
        $to = $request->filled('to_date') ? Carbon::parse($request->query('to_date'))->endOfDay() : null;
        if ($from && $to && $from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $perPage = (int) $request->query('per_page', 50);
        if ($perPage <= 0) {
            $perPage = 50;
        }
        if ($perPage > 200) {
            $perPage = 200;
        }
        $page = max(1, (int) $request->query('page', 1));

        $today = now()->toDateString();

        $invoiceSummarySub = Invoice::query()
            ->accountingActive()
            ->when($from, function ($qb) use ($from) {
                $qb->where('invoice_date', '>=', $from);
            })
            ->when($to, function ($qb) use ($to) {
                $qb->where('invoice_date', '<=', $to);
            })
            ->selectRaw('customer_id')
            ->selectRaw('COALESCE(SUM(pending_amount), 0) as outstanding_amount')
            ->selectRaw('COALESCE(SUM(CASE WHEN pending_amount > 0 AND COALESCE(due_date, invoice_date) < ? THEN pending_amount ELSE 0 END), 0) as overdue_amount', [$today])
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_amount')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as total_paid_amount')
            ->groupBy('customer_id');

        $lastPaymentSub = CustomerPayment::query()
            ->selectRaw('customer_id, MAX(payment_date) as last_payment_date')
            ->groupBy('customer_id');

        $query = Customer::query()
            ->where('customers.is_active', true)
            ->leftJoinSub($invoiceSummarySub, 'inv_sum', function ($join) {
                $join->on('customers.id', '=', 'inv_sum.customer_id');
            })
            ->leftJoinSub($lastPaymentSub, 'pay_max', function ($join) {
                $join->on('customers.id', '=', 'pay_max.customer_id');
            })
            ->select('customers.*')
            ->selectRaw('COALESCE(inv_sum.outstanding_amount, 0) as outstanding_amount')
            ->selectRaw('COALESCE(inv_sum.overdue_amount, 0) as overdue_amount')
            ->selectRaw('COALESCE(inv_sum.total_amount, 0) as total_invoice_amount')
            ->selectRaw('COALESCE(inv_sum.total_paid_amount, 0) as total_paid_amount')
            ->selectRaw('pay_max.last_payment_date as last_payment_date')
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($sq) use ($q) {
                    $sq->where('customers.name', 'like', '%' . $q . '%')
                        ->orWhere('customers.mobile', 'like', '%' . $q . '%')
                        ->orWhere('customers.code', 'like', '%' . $q . '%')
                        ->orWhereExists(function ($ex) use ($q) {
                            $ex->selectRaw('1')
                                ->from('invoices')
                                ->whereColumn('invoices.customer_id', 'customers.id')
                                ->where('invoices.is_deleted', false)
                                ->whereNull('invoices.deleted_at')
                                ->where('invoices.invoice_no', 'like', '%' . $q . '%');
                        });
                });
            })
            ->when($status !== '' && $status !== 'all', function ($qb) use ($status) {
                if ($status === 'paid') {
                    $qb->whereRaw('COALESCE(inv_sum.outstanding_amount, 0) <= 0');
                }
                if ($status === 'outstanding') {
                    $qb->whereRaw('COALESCE(inv_sum.outstanding_amount, 0) > 0');
                }
                if ($status === 'overdue') {
                    $qb->whereRaw('COALESCE(inv_sum.overdue_amount, 0) > 0');
                }
            })
            ->when($paymentStatus !== '' && $paymentStatus !== 'all', function ($qb) use ($paymentStatus) {
                if ($paymentStatus === 'paid') {
                    $qb->whereRaw('COALESCE(inv_sum.outstanding_amount, 0) <= 0');
                }
                if ($paymentStatus === 'unpaid') {
                    $qb->whereRaw('COALESCE(inv_sum.outstanding_amount, 0) > 0')
                        ->whereRaw('COALESCE(inv_sum.total_paid_amount, 0) <= 0');
                }
                if ($paymentStatus === 'partial') {
                    $qb->whereRaw('COALESCE(inv_sum.outstanding_amount, 0) > 0')
                        ->whereRaw('COALESCE(inv_sum.total_paid_amount, 0) > 0');
                }
            })
            ->when($aging !== '' && $aging !== 'all', function ($qb) use ($aging) {
                $qb->whereExists(function ($ex) use ($aging) {
                    $ex->selectRaw('1')
                        ->from('invoices as ai')
                        ->whereColumn('ai.customer_id', 'customers.id')
                        ->where('ai.is_deleted', false)
                        ->whereNull('ai.deleted_at')
                        ->where('ai.pending_amount', '>', 0);

                    if ($aging === '0_7') {
                        $ex->whereRaw('GREATEST(DATEDIFF(CURDATE(), COALESCE(ai.due_date, ai.invoice_date)), 0) BETWEEN 0 AND 7');
                    } elseif ($aging === '8_15') {
                        $ex->whereRaw('GREATEST(DATEDIFF(CURDATE(), COALESCE(ai.due_date, ai.invoice_date)), 0) BETWEEN 8 AND 15');
                    } elseif ($aging === '16_30') {
                        $ex->whereRaw('GREATEST(DATEDIFF(CURDATE(), COALESCE(ai.due_date, ai.invoice_date)), 0) BETWEEN 16 AND 30');
                    } elseif ($aging === '30_plus') {
                        $ex->whereRaw('GREATEST(DATEDIFF(CURDATE(), COALESCE(ai.due_date, ai.invoice_date)), 0) >= 31');
                    }
                });
            })
            ->orderByDesc('outstanding_amount')
            ->orderBy('customers.name')
            ->orderBy('customers.id');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $data = collect($paginator->items())->map(function (Customer $c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'code' => $c->code,
                'mobile' => $c->mobile,
                'credit_limit' => $c->credit_limit,
                'outstanding_amount' => $c->outstanding_amount ?? 0,
                'overdue_amount' => $c->overdue_amount ?? 0,
                'total_invoice_amount' => $c->total_invoice_amount ?? 0,
                'total_paid_amount' => $c->total_paid_amount ?? 0,
                'last_payment_date' => $c->last_payment_date,
            ];
        })->values();

        $monthStart = now()->copy()->startOfMonth()->toDateString();
        $monthEnd = now()->copy()->endOfMonth()->toDateString();

        $invoiceSummaryBase = Invoice::query()
            ->accountingActive()
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->where('customers.is_active', true)
            ->when($from, function ($qb) use ($from) {
                $qb->where('invoices.invoice_date', '>=', $from);
            })
            ->when($to, function ($qb) use ($to) {
                $qb->where('invoices.invoice_date', '<=', $to);
            })
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($sq) use ($q) {
                    $sq->where('invoices.invoice_no', 'like', '%' . $q . '%')
                        ->orWhere('customers.name', 'like', '%' . $q . '%')
                        ->orWhere('customers.mobile', 'like', '%' . $q . '%')
                        ->orWhere('customers.code', 'like', '%' . $q . '%');
                });
            })
            ->when($status !== '' && $status !== 'all', function ($qb) use ($status) {
                if ($status === 'paid') {
                    $qb->where('invoices.pending_amount', '<=', 0);
                }
                if ($status === 'outstanding') {
                    $qb->where('invoices.pending_amount', '>', 0);
                }
                if ($status === 'overdue') {
                    $qb->where('invoices.pending_amount', '>', 0)
                        ->whereRaw('COALESCE(invoices.due_date, invoices.invoice_date) < CURDATE()');
                }
            })
            ->when($paymentStatus !== '' && $paymentStatus !== 'all', function ($qb) use ($paymentStatus) {
                if ($paymentStatus === 'paid') {
                    $qb->where('invoices.payment_status', 'Paid');
                }
                if ($paymentStatus === 'unpaid') {
                    $qb->where('invoices.payment_status', 'Unpaid');
                }
                if ($paymentStatus === 'partial') {
                    $qb->where('invoices.payment_status', 'Partial');
                }
            })
            ->when($aging !== '' && $aging !== 'all', function ($qb) use ($aging) {
                $qb->where('invoices.pending_amount', '>', 0);
                if ($aging === '0_7') {
                    $qb->whereRaw('GREATEST(DATEDIFF(CURDATE(), COALESCE(invoices.due_date, invoices.invoice_date)), 0) BETWEEN 0 AND 7');
                } elseif ($aging === '8_15') {
                    $qb->whereRaw('GREATEST(DATEDIFF(CURDATE(), COALESCE(invoices.due_date, invoices.invoice_date)), 0) BETWEEN 8 AND 15');
                } elseif ($aging === '16_30') {
                    $qb->whereRaw('GREATEST(DATEDIFF(CURDATE(), COALESCE(invoices.due_date, invoices.invoice_date)), 0) BETWEEN 16 AND 30');
                } elseif ($aging === '30_plus') {
                    $qb->whereRaw('GREATEST(DATEDIFF(CURDATE(), COALESCE(invoices.due_date, invoices.invoice_date)), 0) >= 31');
                }
            });

        $summaryInvoiceOutstanding = (clone $invoiceSummaryBase)
            ->where('invoices.pending_amount', '>', 0)
            ->sum('invoices.pending_amount');

        $summaryInvoiceOverdue = (clone $invoiceSummaryBase)
            ->where('invoices.pending_amount', '>', 0)
            ->whereRaw('COALESCE(invoices.due_date, invoices.invoice_date) < CURDATE()')
            ->sum('invoices.pending_amount');

        $paymentFrom = $from ? $from->toDateString() : $monthStart;
        $paymentTo = $to ? $to->toDateString() : $monthEnd;

        $summaryPayments = CustomerPayment::query()
            ->join('customers', 'customers.id', '=', 'customer_payments.customer_id')
            ->where('customers.is_active', true)
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($sq) use ($q) {
                    $sq->where('customers.name', 'like', '%' . $q . '%')
                        ->orWhere('customers.mobile', 'like', '%' . $q . '%')
                        ->orWhere('customers.code', 'like', '%' . $q . '%');
                });
            })
            ->whereBetween('customer_payments.payment_date', [$paymentFrom, $paymentTo])
            ->sum('customer_payments.amount');

        $pendingFrom = $from ? $from->toDateString() : $monthStart;
        $pendingTo = $to ? $to->toDateString() : $monthEnd;

        $summaryMonthPending = (clone $invoiceSummaryBase)
            ->where('invoices.pending_amount', '>', 0)
            ->whereBetween('invoices.invoice_date', [$pendingFrom, $pendingTo])
            ->sum('invoices.pending_amount');

        $summary = [
            'total_outstanding' => (string) $summaryInvoiceOutstanding,
            'total_overdue' => (string) $summaryInvoiceOverdue,
            'collection_received' => (string) $summaryPayments,
            'this_month_pending' => (string) $summaryMonthPending,
        ];

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
                'next_page' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
                'summary' => $summary,
            ],
        ]);
    }

    public function customerLedgerInvoicesSearch(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'all'));
        $paymentStatus = trim((string) $request->query('payment_status', 'all'));
        $aging = trim((string) $request->query('aging', 'all'));

        $from = $request->filled('from_date') ? Carbon::parse($request->query('from_date'))->startOfDay() : null;
        $to = $request->filled('to_date') ? Carbon::parse($request->query('to_date'))->endOfDay() : null;
        if ($from && $to && $from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $perPage = (int) $request->query('per_page', 50);
        if ($perPage <= 0) {
            $perPage = 50;
        }
        if ($perPage > 200) {
            $perPage = 200;
        }
        $page = max(1, (int) $request->query('page', 1));

        $query = Invoice::query()
            ->accountingActive()
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->select([
                'invoices.id',
                'invoices.invoice_no',
                'invoices.invoice_date',
                'invoices.due_date',
                'invoices.total_amount',
                'invoices.paid_amount',
                'invoices.pending_amount',
                'invoices.payment_status',
                'customers.id as customer_id',
                'customers.name as customer_name',
            ])
            ->selectRaw('GREATEST(DATEDIFF(CURDATE(), COALESCE(invoices.due_date, invoices.invoice_date)), 0) as due_days')
            ->when($from, function ($qb) use ($from) {
                $qb->where('invoices.invoice_date', '>=', $from);
            })
            ->when($to, function ($qb) use ($to) {
                $qb->where('invoices.invoice_date', '<=', $to);
            })
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($sq) use ($q) {
                    $sq->where('invoices.invoice_no', 'like', '%' . $q . '%')
                        ->orWhere('customers.name', 'like', '%' . $q . '%')
                        ->orWhere('customers.mobile', 'like', '%' . $q . '%')
                        ->orWhere('customers.code', 'like', '%' . $q . '%');
                });
            })
            ->when($status !== '' && $status !== 'all', function ($qb) use ($status) {
                if ($status === 'paid') {
                    $qb->where('invoices.pending_amount', '<=', 0);
                }
                if ($status === 'outstanding') {
                    $qb->where('invoices.pending_amount', '>', 0);
                }
                if ($status === 'overdue') {
                    $qb->where('invoices.pending_amount', '>', 0)
                        ->whereRaw('COALESCE(invoices.due_date, invoices.invoice_date) < CURDATE()');
                }
            })
            ->when($paymentStatus !== '' && $paymentStatus !== 'all', function ($qb) use ($paymentStatus) {
                if ($paymentStatus === 'paid') {
                    $qb->where('invoices.payment_status', 'Paid');
                }
                if ($paymentStatus === 'unpaid') {
                    $qb->where('invoices.payment_status', 'Unpaid');
                }
                if ($paymentStatus === 'partial') {
                    $qb->where('invoices.payment_status', 'Partial');
                }
            })
            ->when($aging !== '' && $aging !== 'all', function ($qb) use ($aging) {
                $qb->where('invoices.pending_amount', '>', 0);
                if ($aging === '0_7') {
                    $qb->whereRaw('GREATEST(DATEDIFF(CURDATE(), COALESCE(invoices.due_date, invoices.invoice_date)), 0) BETWEEN 0 AND 7');
                } elseif ($aging === '8_15') {
                    $qb->whereRaw('GREATEST(DATEDIFF(CURDATE(), COALESCE(invoices.due_date, invoices.invoice_date)), 0) BETWEEN 8 AND 15');
                } elseif ($aging === '16_30') {
                    $qb->whereRaw('GREATEST(DATEDIFF(CURDATE(), COALESCE(invoices.due_date, invoices.invoice_date)), 0) BETWEEN 16 AND 30');
                } elseif ($aging === '30_plus') {
                    $qb->whereRaw('GREATEST(DATEDIFF(CURDATE(), COALESCE(invoices.due_date, invoices.invoice_date)), 0) >= 31');
                }
            })
            ->orderByDesc('invoices.pending_amount')
            ->orderByDesc('invoices.invoice_date')
            ->orderByDesc('invoices.id');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $data = collect($paginator->items())->map(function ($row) {
            return [
                'id' => $row->id,
                'invoice_no' => $row->invoice_no,
                'customer_id' => $row->customer_id,
                'customer_name' => $row->customer_name,
                'invoice_date' => $row->invoice_date ? Carbon::parse($row->invoice_date)->toDateString() : null,
                'due_date' => $row->due_date ? Carbon::parse($row->due_date)->toDateString() : null,
                'total_amount' => $row->total_amount,
                'paid_amount' => $row->paid_amount,
                'pending_amount' => $row->pending_amount,
                'payment_status' => $row->payment_status,
                'due_days' => (int) ($row->due_days ?? 0),
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

    public function customerLedgerAgingSearch(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'all'));
        $paymentStatus = trim((string) $request->query('payment_status', 'all'));
        $agingFilter = trim((string) $request->query('aging', 'all'));

        $from = $request->filled('from_date') ? Carbon::parse($request->query('from_date'))->startOfDay() : null;
        $to = $request->filled('to_date') ? Carbon::parse($request->query('to_date'))->endOfDay() : null;
        if ($from && $to && $from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $perPage = (int) $request->query('per_page', 50);
        if ($perPage <= 0) {
            $perPage = 50;
        }
        if ($perPage > 200) {
            $perPage = 200;
        }
        $page = max(1, (int) $request->query('page', 1));

        $agingSub = Invoice::query()
            ->accountingActive()
            ->where('pending_amount', '>', 0)
            ->when($from, function ($qb) use ($from) {
                $qb->where('invoice_date', '>=', $from);
            })
            ->when($to, function ($qb) use ($to) {
                $qb->where('invoice_date', '<=', $to);
            })
            ->selectRaw('customer_id')
            ->selectRaw('COALESCE(SUM(pending_amount), 0) as total_pending')
            ->selectRaw('COALESCE(SUM(CASE WHEN COALESCE(due_date, invoice_date) < CURDATE() THEN pending_amount ELSE 0 END), 0) as overdue_amount')
            ->selectRaw('COALESCE(SUM(CASE WHEN GREATEST(DATEDIFF(CURDATE(), COALESCE(due_date, invoice_date)), 0) BETWEEN 0 AND 7 THEN pending_amount ELSE 0 END), 0) as b_0_7')
            ->selectRaw('COALESCE(SUM(CASE WHEN GREATEST(DATEDIFF(CURDATE(), COALESCE(due_date, invoice_date)), 0) BETWEEN 8 AND 15 THEN pending_amount ELSE 0 END), 0) as b_8_15')
            ->selectRaw('COALESCE(SUM(CASE WHEN GREATEST(DATEDIFF(CURDATE(), COALESCE(due_date, invoice_date)), 0) BETWEEN 16 AND 30 THEN pending_amount ELSE 0 END), 0) as b_16_30')
            ->selectRaw('COALESCE(SUM(CASE WHEN GREATEST(DATEDIFF(CURDATE(), COALESCE(due_date, invoice_date)), 0) >= 31 THEN pending_amount ELSE 0 END), 0) as b_30_plus')
            ->groupBy('customer_id');

        $query = Customer::query()
            ->where('customers.is_active', true)
            ->joinSub($agingSub, 'age_sum', function ($join) {
                $join->on('customers.id', '=', 'age_sum.customer_id');
            })
            ->select([
                'customers.id',
                'customers.name',
                'customers.code',
                'customers.mobile',
                'customers.credit_limit',
            ])
            ->selectRaw('age_sum.total_pending')
            ->selectRaw('age_sum.overdue_amount')
            ->selectRaw('age_sum.b_0_7')
            ->selectRaw('age_sum.b_8_15')
            ->selectRaw('age_sum.b_16_30')
            ->selectRaw('age_sum.b_30_plus')
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($sq) use ($q) {
                    $sq->where('customers.name', 'like', '%' . $q . '%')
                        ->orWhere('customers.mobile', 'like', '%' . $q . '%')
                        ->orWhere('customers.code', 'like', '%' . $q . '%');
                });
            })
            ->when($status !== '' && $status !== 'all', function ($qb) use ($status) {
                if ($status === 'paid') {
                    $qb->whereRaw('age_sum.total_pending <= 0');
                }
                if ($status === 'outstanding') {
                    $qb->whereRaw('age_sum.total_pending > 0');
                }
                if ($status === 'overdue') {
                    $qb->whereRaw('age_sum.overdue_amount > 0');
                }
            })
            ->when($paymentStatus !== '' && $paymentStatus !== 'all', function ($qb) use ($paymentStatus) {
                if ($paymentStatus === 'paid') {
                    $qb->whereRaw('age_sum.total_pending <= 0');
                }
                if ($paymentStatus === 'unpaid') {
                    $qb->whereExists(function ($ex) {
                        $ex->selectRaw('1')
                            ->from('invoices as pi')
                            ->whereColumn('pi.customer_id', 'customers.id')
                            ->where('pi.is_deleted', false)
                            ->whereNull('pi.deleted_at')
                            ->where('pi.pending_amount', '>', 0)
                            ->where('pi.payment_status', 'Unpaid');
                    });
                }
                if ($paymentStatus === 'partial') {
                    $qb->whereExists(function ($ex) {
                        $ex->selectRaw('1')
                            ->from('invoices as pi')
                            ->whereColumn('pi.customer_id', 'customers.id')
                            ->where('pi.is_deleted', false)
                            ->whereNull('pi.deleted_at')
                            ->where('pi.pending_amount', '>', 0)
                            ->where('pi.payment_status', 'Partial');
                    });
                }
            })
            ->when($agingFilter !== '' && $agingFilter !== 'all', function ($qb) use ($agingFilter) {
                if ($agingFilter === '0_7') $qb->whereRaw('age_sum.b_0_7 > 0');
                if ($agingFilter === '8_15') $qb->whereRaw('age_sum.b_8_15 > 0');
                if ($agingFilter === '16_30') $qb->whereRaw('age_sum.b_16_30 > 0');
                if ($agingFilter === '30_plus') $qb->whereRaw('age_sum.b_30_plus > 0');
            })
            ->orderByDesc('age_sum.total_pending')
            ->orderBy('customers.name')
            ->orderBy('customers.id');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $data = collect($paginator->items())->map(function ($row) {
            return [
                'id' => $row->id,
                'name' => $row->name,
                'code' => $row->code,
                'mobile' => $row->mobile,
                'credit_limit' => $row->credit_limit,
                'total_pending' => $row->total_pending,
                'overdue_amount' => $row->overdue_amount ?? 0,
                'b_0_7' => $row->b_0_7,
                'b_8_15' => $row->b_8_15,
                'b_16_30' => $row->b_16_30,
                'b_30_plus' => $row->b_30_plus,
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

    public function customerLedgerShow(Request $request, $customerId)
    {
        $customer = Customer::withTrashed()->findOrFail($customerId);

        $from = $request->filled('from_date') ? Carbon::parse($request->query('from_date'))->startOfDay() : null;
        $to = $request->filled('to_date') ? Carbon::parse($request->query('to_date'))->endOfDay() : null;
        if ($from && $to && $from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $invoiceQuery = Invoice::query()
            ->accountingActive()
            ->where('customer_id', $customer->id)
            ->where(function ($q) {
                $q->where('payment_type', 'Credit')->orWhere('pending_amount', '>', 0);
            });

        if ($from) {
            $invoiceQuery->where('invoice_date', '>=', $from);
        }
        if ($to) {
            $invoiceQuery->where('invoice_date', '<=', $to);
        }

        $invoices = $invoiceQuery->get([
            'id',
            'invoice_no',
            'invoice_date',
            'total_amount',
            'paid_amount',
            'pending_amount',
            'updated_at',
        ]);

        $allocRows = DB::table('customer_payment_allocations as a')
            ->join('customer_payments as p', 'a.customer_payment_id', '=', 'p.id')
            ->join('invoices as i', 'a.invoice_id', '=', 'i.id')
            ->where('i.is_deleted', false)
            ->whereNull('i.deleted_at')
            ->where('i.customer_id', $customer->id)
            ->when($from, function ($q) use ($from) {
                $q->where('p.payment_date', '>=', $from->toDateString());
            })
            ->when($to, function ($q) use ($to) {
                $q->where('p.payment_date', '<=', $to->toDateString());
            })
            ->selectRaw('p.id as payment_id, p.payment_date, p.payment_mode, p.reference_no, COALESCE(SUM(a.amount), 0) as amount')
            ->groupBy('p.id', 'p.payment_date', 'p.payment_mode', 'p.reference_no')
            ->orderBy('p.payment_date')
            ->get();

        $allocByInvoice = DB::table('customer_payment_allocations as a')
            ->join('customer_payments as p', 'a.customer_payment_id', '=', 'p.id')
            ->join('invoices as i', 'a.invoice_id', '=', 'i.id')
            ->where('i.is_deleted', false)
            ->whereNull('i.deleted_at')
            ->where('i.customer_id', $customer->id)
            ->selectRaw('a.invoice_id, COALESCE(SUM(a.amount), 0) as amount')
            ->groupBy('a.invoice_id')
            ->pluck('amount', 'invoice_id')
            ->toArray();

        $entries = [];

        foreach ($invoices as $inv) {
            $total = round((float) ($inv->total_amount ?? 0), 6);
            if ($total <= 0) {
                continue;
            }
            $entries[] = [
                'date' => Carbon::parse($inv->invoice_date)->toDateString(),
                'type' => 'Invoice',
                'ref' => $inv->invoice_no,
                'debit' => $total,
                'credit' => 0.0,
                'sort' => 1,
            ];

            $paid = round((float) ($inv->paid_amount ?? 0), 6);
            $allocated = round((float) ($allocByInvoice[$inv->id] ?? 0), 6);
            $manual = round($paid - $allocated, 6);
            if ($manual > 0) {
                $entries[] = [
                    'date' => Carbon::parse($inv->updated_at)->toDateString(),
                    'type' => 'Payment',
                    'ref' => 'Manual (' . $inv->invoice_no . ')',
                    'debit' => 0.0,
                    'credit' => $manual,
                    'sort' => 2,
                ];
            }
        }

        foreach ($allocRows as $p) {
            $amount = round((float) ($p->amount ?? 0), 6);
            if ($amount <= 0) {
                continue;
            }
            $ref = 'Receipt #' . $p->payment_id;
            if (!empty($p->reference_no)) {
                $ref .= ' (' . $p->reference_no . ')';
            }
            $entries[] = [
                'date' => Carbon::parse($p->payment_date)->toDateString(),
                'type' => 'Payment',
                'ref' => $ref,
                'debit' => 0.0,
                'credit' => $amount,
                'sort' => 2,
            ];
        }

        usort($entries, function ($a, $b) {
            if ($a['date'] === $b['date']) {
                return ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0);
            }
            return strcmp($a['date'], $b['date']);
        });

        $balance = 0.0;
        foreach ($entries as &$e) {
            $balance = round($balance + (float) $e['debit'] - (float) $e['credit'], 6);
            $e['balance'] = $balance;
        }
        unset($e);

        $outstanding = (float) Invoice::query()
            ->accountingActive()
            ->where('customer_id', $customer->id)
            ->sum('pending_amount');

        return view('accounts.customer-ledger-show', [
            'customer' => $customer,
            'entries' => $entries,
            'outstanding' => $outstanding,
            'fromDate' => $from?->toDateString(),
            'toDate' => $to?->toDateString(),
        ]);
    }

    public function customerLedgerDrawerDetail(Request $request, $customerId)
    {
        $customer = Customer::withTrashed()->findOrFail($customerId);

        $from = $request->filled('from_date') ? Carbon::parse($request->query('from_date'))->startOfDay() : null;
        $to = $request->filled('to_date') ? Carbon::parse($request->query('to_date'))->endOfDay() : null;
        if ($from && $to && $from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $today = now()->toDateString();

        $outstanding = (float) Invoice::query()
            ->accountingActive()
            ->where('customer_id', $customer->id)
            ->sum('pending_amount');

        $overdue = (float) Invoice::query()
            ->accountingActive()
            ->where('customer_id', $customer->id)
            ->where('pending_amount', '>', 0)
            ->whereRaw('COALESCE(due_date, invoice_date) < ?', [$today])
            ->sum('pending_amount');

        $creditLimit = (float) ($customer->credit_limit ?? 0);
        $overLimit = $creditLimit > 0 && $outstanding > ($creditLimit + 0.000001);
        $availableCredit = $creditLimit > 0 ? $creditLimit - $outstanding : null;

        $lastPaymentDate = CustomerPayment::query()
            ->where('customer_id', $customer->id)
            ->max('payment_date');

        $monthStart = now()->copy()->startOfMonth()->toDateString();
        $monthEnd = now()->copy()->endOfMonth()->toDateString();

        $thisMonthSales = (float) Invoice::query()
            ->accountingActive()
            ->where('customer_id', $customer->id)
            ->whereBetween('invoice_date', [$monthStart, $monthEnd])
            ->sum('total_amount');

        $thisMonthCollection = (float) CustomerPayment::query()
            ->where('customer_id', $customer->id)
            ->whereBetween('payment_date', [$monthStart, $monthEnd])
            ->sum('amount');

        $aging = DB::table('invoices')
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->where('customer_id', $customer->id)
            ->where('pending_amount', '>', 0)
            ->selectRaw('COALESCE(SUM(CASE WHEN GREATEST(DATEDIFF(CURDATE(), COALESCE(due_date, invoice_date)), 0) BETWEEN 0 AND 7 THEN pending_amount ELSE 0 END), 0) as b_0_7')
            ->selectRaw('COALESCE(SUM(CASE WHEN GREATEST(DATEDIFF(CURDATE(), COALESCE(due_date, invoice_date)), 0) BETWEEN 8 AND 15 THEN pending_amount ELSE 0 END), 0) as b_8_15')
            ->selectRaw('COALESCE(SUM(CASE WHEN GREATEST(DATEDIFF(CURDATE(), COALESCE(due_date, invoice_date)), 0) BETWEEN 16 AND 30 THEN pending_amount ELSE 0 END), 0) as b_16_30')
            ->selectRaw('COALESCE(SUM(CASE WHEN GREATEST(DATEDIFF(CURDATE(), COALESCE(due_date, invoice_date)), 0) >= 31 THEN pending_amount ELSE 0 END), 0) as b_30_plus')
            ->first();

        $invoiceQuery = Invoice::query()
            ->accountingActive()
            ->where('customer_id', $customer->id)
            ->where(function ($q) {
                $q->where('payment_type', 'Credit')->orWhere('pending_amount', '>', 0);
            });

        if ($from) {
            $invoiceQuery->where('invoice_date', '>=', $from);
        }
        if ($to) {
            $invoiceQuery->where('invoice_date', '<=', $to);
        }

        $invoices = $invoiceQuery->get([
            'id',
            'invoice_no',
            'invoice_date',
            'total_amount',
            'paid_amount',
            'pending_amount',
            'payment_status',
            'updated_at',
        ]);

        $allocRows = DB::table('customer_payment_allocations as a')
            ->join('customer_payments as p', 'a.customer_payment_id', '=', 'p.id')
            ->join('invoices as i', 'a.invoice_id', '=', 'i.id')
            ->where('i.is_deleted', false)
            ->whereNull('i.deleted_at')
            ->where('i.customer_id', $customer->id)
            ->when($from, function ($q) use ($from) {
                $q->where('p.payment_date', '>=', $from->toDateString());
            })
            ->when($to, function ($q) use ($to) {
                $q->where('p.payment_date', '<=', $to->toDateString());
            })
            ->selectRaw('p.id as payment_id, p.payment_date, p.payment_mode, p.reference_no, COALESCE(SUM(a.amount), 0) as amount')
            ->groupBy('p.id', 'p.payment_date', 'p.payment_mode', 'p.reference_no')
            ->orderBy('p.payment_date')
            ->get();

        $allocByInvoice = DB::table('customer_payment_allocations as a')
            ->join('customer_payments as p', 'a.customer_payment_id', '=', 'p.id')
            ->join('invoices as i', 'a.invoice_id', '=', 'i.id')
            ->where('i.is_deleted', false)
            ->whereNull('i.deleted_at')
            ->where('i.customer_id', $customer->id)
            ->selectRaw('a.invoice_id, COALESCE(SUM(a.amount), 0) as amount')
            ->groupBy('a.invoice_id')
            ->pluck('amount', 'invoice_id')
            ->toArray();

        $entries = [];
        foreach ($invoices as $inv) {
            $total = round((float) ($inv->total_amount ?? 0), 6);
            if ($total <= 0) {
                continue;
            }
            $entries[] = [
                'date' => Carbon::parse($inv->invoice_date)->toDateString(),
                'type' => 'Invoice',
                'ref' => $inv->invoice_no,
                'debit' => $total,
                'credit' => 0.0,
                'sort' => 1,
                'invoice_id' => $inv->id,
                'invoice_status' => $inv->payment_status,
            ];

            $paid = round((float) ($inv->paid_amount ?? 0), 6);
            $allocated = round((float) ($allocByInvoice[$inv->id] ?? 0), 6);
            $manual = round($paid - $allocated, 6);
            if ($manual > 0) {
                $entries[] = [
                    'date' => Carbon::parse($inv->updated_at)->toDateString(),
                    'type' => 'Payment',
                    'ref' => 'Manual (' . $inv->invoice_no . ')',
                    'debit' => 0.0,
                    'credit' => $manual,
                    'sort' => 2,
                ];
            }
        }

        foreach ($allocRows as $p) {
            $amount = round((float) ($p->amount ?? 0), 6);
            if ($amount <= 0) {
                continue;
            }
            $ref = 'Receipt #' . $p->payment_id;
            if (!empty($p->reference_no)) {
                $ref .= ' (' . $p->reference_no . ')';
            }
            $entries[] = [
                'date' => Carbon::parse($p->payment_date)->toDateString(),
                'type' => 'Payment',
                'ref' => $ref,
                'debit' => 0.0,
                'credit' => $amount,
                'sort' => 2,
                'payment_id' => $p->payment_id,
                'payment_mode' => $p->payment_mode,
            ];
        }

        usort($entries, function ($a, $b) {
            if ($a['date'] === $b['date']) {
                return ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0);
            }
            return strcmp($a['date'], $b['date']);
        });

        $balance = 0.0;
        foreach ($entries as &$e) {
            $balance = round($balance + (float) $e['debit'] - (float) $e['credit'], 6);
            $e['balance'] = $balance;
        }
        unset($e);

        $totalEntries = count($entries);
        $limit = max(50, min(500, (int) $request->query('limit', 300)));
        $truncated = $totalEntries > $limit;
        if ($truncated) {
            $entries = array_slice($entries, -$limit);
        }

        $payload = [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'mobile' => $customer->mobile,
                'gstin' => $customer->gstin,
                'credit_limit' => (string) ($customer->credit_limit ?? '0'),
            ],
            'metrics' => [
                'outstanding' => $outstanding,
                'overdue' => $overdue,
                'available_credit' => $availableCredit,
                'over_limit' => $overLimit,
                'last_payment_date' => $lastPaymentDate ? Carbon::parse($lastPaymentDate)->toDateString() : null,
                'this_month_sales' => $thisMonthSales,
                'this_month_collection' => $thisMonthCollection,
                'aging' => [
                    'b_0_7' => (float) ($aging->b_0_7 ?? 0),
                    'b_8_15' => (float) ($aging->b_8_15 ?? 0),
                    'b_16_30' => (float) ($aging->b_16_30 ?? 0),
                    'b_30_plus' => (float) ($aging->b_30_plus ?? 0),
                ],
            ],
            'ledger' => [
                'entries' => array_values($entries),
                'total_entries' => $totalEntries,
                'limit' => $limit,
                'truncated' => $truncated,
            ],
        ];

        return response()->json($payload);
    }
}
