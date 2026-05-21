<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Invoice;
use App\Services\InvoicePaymentSyncService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelWriter;

class AccountsV2Controller extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'all'));

        $from = $request->filled('from_date') ? Carbon::parse($request->query('from_date'))->startOfDay() : null;
        $to = $request->filled('to_date') ? Carbon::parse($request->query('to_date'))->endOfDay() : null;
        if ($from && $to && $from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $perPage = 50;
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
            ->orderByDesc('outstanding_amount')
            ->orderBy('customers.name')
            ->orderBy('customers.id');

        $customers = $query->paginate($perPage)->withQueryString();

        return view('accounts-v2.pages.index', [
            'pageTitle' => 'Accounts V2 — Customer Accounts',
            'customers' => $customers,
            'filters' => [
                'q' => $q,
                'status' => $status !== '' ? $status : 'all',
                'from_date' => $from?->toDateString(),
                'to_date' => $to?->toDateString(),
            ],
        ]);
    }

    public function statement(Request $request, Customer $customer)
    {
        $today = now()->toDateString();
        $filterFrom = $request->filled('from_date') ? Carbon::parse($request->query('from_date'))->startOfDay() : null;
        $filterTo = $request->filled('to_date') ? Carbon::parse($request->query('to_date'))->endOfDay() : null;
        if ($filterFrom && $filterTo && $filterFrom->greaterThan($filterTo)) {
            [$filterFrom, $filterTo] = [$filterTo->copy()->startOfDay(), $filterFrom->copy()->endOfDay()];
        }
        $txType = strtolower(trim((string) $request->query('tx_type', 'all')));
        if (!in_array($txType, ['all', 'invoices', 'payments'], true)) {
            $txType = 'all';
        }
        $toDateString = $filterTo ? $filterTo->toDateString() : null;
        $fromDateString = $filterFrom ? $filterFrom->toDateString() : null;

        $invoices = Invoice::query()
            ->accountingActive()
            ->where('customer_id', $customer->id)
            ->when($toDateString, function ($qb) use ($toDateString) {
                $qb->where('invoice_date', '<=', $toDateString);
            })
            ->orderBy('invoice_date')
            ->get([
                'id',
                'invoice_no',
                'invoice_date',
                'due_date',
                'total_amount',
                'paid_amount',
                'pending_amount',
                'payment_status',
            ]);

        $payments = CustomerPayment::query()
            ->where('customer_id', $customer->id)
            ->when($toDateString, function ($qb) use ($toDateString) {
                $qb->where('payment_date', '<=', $toDateString);
            })
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get([
                'id',
                'customer_id',
                'payment_date',
                'amount',
                'payment_mode',
                'reference_no',
            ]);

        $totalOutstanding = (float) Invoice::query()
            ->accountingActive()
            ->where('customer_id', $customer->id)
            ->sum('pending_amount');

        $totalOverdue = (float) Invoice::query()
            ->accountingActive()
            ->where('customer_id', $customer->id)
            ->where('pending_amount', '>', 0)
            ->whereRaw('COALESCE(due_date, invoice_date) < ?', [$today])
            ->sum('pending_amount');

        $lastPaymentDate = CustomerPayment::query()
            ->where('customer_id', $customer->id)
            ->max('payment_date');

        $agingRow = Invoice::query()
            ->accountingActive()
            ->where('customer_id', $customer->id)
            ->where('pending_amount', '>', 0)
            ->selectRaw('COALESCE(SUM(CASE WHEN COALESCE(due_date, invoice_date) >= CURDATE() THEN pending_amount ELSE 0 END), 0) as current_amt')
            ->selectRaw('COALESCE(SUM(CASE WHEN COALESCE(due_date, invoice_date) < CURDATE() AND DATEDIFF(CURDATE(), COALESCE(due_date, invoice_date)) BETWEEN 1 AND 7 THEN pending_amount ELSE 0 END), 0) as b_0_7')
            ->selectRaw('COALESCE(SUM(CASE WHEN COALESCE(due_date, invoice_date) < CURDATE() AND DATEDIFF(CURDATE(), COALESCE(due_date, invoice_date)) BETWEEN 8 AND 15 THEN pending_amount ELSE 0 END), 0) as b_8_15')
            ->selectRaw('COALESCE(SUM(CASE WHEN COALESCE(due_date, invoice_date) < CURDATE() AND DATEDIFF(CURDATE(), COALESCE(due_date, invoice_date)) BETWEEN 16 AND 30 THEN pending_amount ELSE 0 END), 0) as b_16_30')
            ->selectRaw('COALESCE(SUM(CASE WHEN COALESCE(due_date, invoice_date) < CURDATE() AND DATEDIFF(CURDATE(), COALESCE(due_date, invoice_date)) >= 31 THEN pending_amount ELSE 0 END), 0) as b_30_plus')
            ->first();

        $entries = [];
        foreach ($invoices as $inv) {
            $entries[] = [
                'date' => $inv->invoice_date ? Carbon::parse($inv->invoice_date)->toDateString() : null,
                'type' => 'Invoice',
                'ref' => (string) ($inv->invoice_no ?? ('INV#' . $inv->id)),
                'debit' => (float) ($inv->total_amount ?? 0),
                'credit' => 0.0,
                'status' => (string) ($inv->payment_status ?? ''),
                'sort' => 1,
                'row_id' => (int) $inv->id,
            ];
        }

        foreach ($payments as $p) {
            $ref = 'PAY#' . $p->id;
            if ($p->reference_no) {
                $ref .= ' (' . $p->reference_no . ')';
            }
            $entries[] = [
                'date' => $p->payment_date ? Carbon::parse($p->payment_date)->toDateString() : null,
                'type' => 'Payment',
                'ref' => $ref,
                'debit' => 0.0,
                'credit' => (float) ($p->amount ?? 0),
                'status' => 'Received',
                'sort' => 2,
                'row_id' => (int) $p->id,
            ];
        }

        usort($entries, function ($a, $b) {
            if (($a['date'] ?? '') === ($b['date'] ?? '')) {
                if (($a['sort'] ?? 0) === ($b['sort'] ?? 0)) {
                    return (int) ($a['row_id'] ?? 0) <=> (int) ($b['row_id'] ?? 0);
                }
                return (int) ($a['sort'] ?? 0) <=> (int) ($b['sort'] ?? 0);
            }
            return strcmp((string) ($a['date'] ?? ''), (string) ($b['date'] ?? ''));
        });

        $balance = 0.0;
        foreach ($entries as &$e) {
            $balance = round($balance + (float) ($e['debit'] ?? 0) - (float) ($e['credit'] ?? 0), 6);
            $e['balance'] = $balance;
        }
        unset($e);

        $filteredEntries = array_values(array_filter($entries, function (array $e) use ($fromDateString, $toDateString, $txType) {
            $d = (string) ($e['date'] ?? '');
            if ($fromDateString && $d !== '' && $d < $fromDateString) return false;
            if ($toDateString && $d !== '' && $d > $toDateString) return false;
            if ($txType === 'invoices') return (($e['type'] ?? '') === 'Invoice');
            if ($txType === 'payments') return (($e['type'] ?? '') === 'Payment');
            return true;
        }));

        $ledgerDebitTotal = 0.0;
        $ledgerCreditTotal = 0.0;
        $ledgerFinalBalance = 0.0;
        foreach ($filteredEntries as $row) {
            $ledgerDebitTotal = round($ledgerDebitTotal + (float) ($row['debit'] ?? 0), 6);
            $ledgerCreditTotal = round($ledgerCreditTotal + (float) ($row['credit'] ?? 0), 6);
            $ledgerFinalBalance = (float) ($row['balance'] ?? $ledgerFinalBalance);
        }
        $ledgerOutstanding = round($ledgerDebitTotal - $ledgerCreditTotal, 6);
        $ledgerFinalBalance = round((float) $ledgerFinalBalance, 6);

        $ledgerPerPage = 200;
        $ledgerPage = max(1, (int) $request->query('page', 1));
        $ledgerTotal = count($filteredEntries);
        $ledgerSlice = array_slice($filteredEntries, ($ledgerPage - 1) * $ledgerPerPage, $ledgerPerPage);
        $ledger = new LengthAwarePaginator($ledgerSlice, $ledgerTotal, $ledgerPerPage, $ledgerPage, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        $invoicesForSections = $invoices;
        if ($fromDateString) {
            $invoicesForSections = $invoicesForSections->filter(function ($inv) use ($fromDateString) {
                $d = $inv->invoice_date ? Carbon::parse($inv->invoice_date)->toDateString() : null;
                return $d ? ($d >= $fromDateString) : false;
            });
        }
        $paymentsForSections = $payments;
        if ($fromDateString) {
            $paymentsForSections = $paymentsForSections->filter(function ($p) use ($fromDateString) {
                $d = $p->payment_date ? Carbon::parse($p->payment_date)->toDateString() : null;
                return $d ? ($d >= $fromDateString) : false;
            });
        }

        return view('accounts-v2.pages.statement', [
            'pageTitle' => 'Accounts V2 — Customer Statement',
            'customer' => $customer,
            'summary' => [
                'outstanding' => $totalOutstanding,
                'overdue' => $totalOverdue,
                'last_payment_date' => $lastPaymentDate ? Carbon::parse($lastPaymentDate)->toDateString() : null,
            ],
            'aging' => [
                'current' => (float) ($agingRow?->current_amt ?? 0),
                'b_0_7' => (float) ($agingRow?->b_0_7 ?? 0),
                'b_8_15' => (float) ($agingRow?->b_8_15 ?? 0),
                'b_16_30' => (float) ($agingRow?->b_16_30 ?? 0),
                'b_30_plus' => (float) ($agingRow?->b_30_plus ?? 0),
            ],
            'ledger' => $ledger,
            'invoices_latest' => $invoicesForSections->sortByDesc('invoice_date')->take(20)->values(),
            'payments_latest' => $paymentsForSections->sortByDesc('payment_date')->take(20)->values(),
            'filters' => [
                'from_date' => $fromDateString,
                'to_date' => $toDateString,
                'tx_type' => $txType,
            ],
            'ledgerTotals' => [
                'debit_total' => $ledgerDebitTotal,
                'credit_total' => $ledgerCreditTotal,
                'final_balance' => $ledgerFinalBalance,
                'outstanding' => $ledgerOutstanding,
            ],
        ]);
    }

    private function statementExportData(Request $request, Customer $customer): array
    {
        $today = now()->toDateString();
        $filterFrom = $request->filled('from_date') ? Carbon::parse($request->query('from_date'))->startOfDay() : null;
        $filterTo = $request->filled('to_date') ? Carbon::parse($request->query('to_date'))->endOfDay() : null;
        if ($filterFrom && $filterTo && $filterFrom->greaterThan($filterTo)) {
            [$filterFrom, $filterTo] = [$filterTo->copy()->startOfDay(), $filterFrom->copy()->endOfDay()];
        }
        $txType = strtolower(trim((string) $request->query('tx_type', 'all')));
        if (!in_array($txType, ['all', 'invoices', 'payments'], true)) {
            $txType = 'all';
        }
        $toDateString = $filterTo ? $filterTo->toDateString() : null;
        $fromDateString = $filterFrom ? $filterFrom->toDateString() : null;

        $invoices = Invoice::query()
            ->accountingActive()
            ->where('customer_id', $customer->id)
            ->when($toDateString, function ($qb) use ($toDateString) {
                $qb->where('invoice_date', '<=', $toDateString);
            })
            ->orderBy('invoice_date')
            ->get([
                'id',
                'invoice_no',
                'invoice_date',
                'due_date',
                'total_amount',
                'paid_amount',
                'pending_amount',
                'payment_status',
            ]);

        $payments = CustomerPayment::query()
            ->where('customer_id', $customer->id)
            ->when($toDateString, function ($qb) use ($toDateString) {
                $qb->where('payment_date', '<=', $toDateString);
            })
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get([
                'id',
                'payment_date',
                'amount',
                'payment_mode',
                'reference_no',
            ]);

        $totalOutstanding = (float) Invoice::query()
            ->accountingActive()
            ->where('customer_id', $customer->id)
            ->sum('pending_amount');

        $totalOverdue = (float) Invoice::query()
            ->accountingActive()
            ->where('customer_id', $customer->id)
            ->where('pending_amount', '>', 0)
            ->whereRaw('COALESCE(due_date, invoice_date) < ?', [$today])
            ->sum('pending_amount');

        $lastPaymentDate = CustomerPayment::query()
            ->where('customer_id', $customer->id)
            ->max('payment_date');

        $entries = [];
        foreach ($invoices as $inv) {
            $entries[] = [
                'date' => $inv->invoice_date ? Carbon::parse($inv->invoice_date)->toDateString() : null,
                'type' => 'Invoice',
                'ref' => (string) ($inv->invoice_no ?? ('INV#' . $inv->id)),
                'debit' => (float) ($inv->total_amount ?? 0),
                'credit' => 0.0,
                'status' => (string) ($inv->payment_status ?? ''),
                'sort' => 1,
                'row_id' => (int) $inv->id,
            ];
        }

        foreach ($payments as $p) {
            $ref = 'PAY#' . $p->id;
            if ($p->reference_no) {
                $ref .= ' (' . $p->reference_no . ')';
            }
            $entries[] = [
                'date' => $p->payment_date ? Carbon::parse($p->payment_date)->toDateString() : null,
                'type' => 'Payment',
                'ref' => $ref,
                'debit' => 0.0,
                'credit' => (float) ($p->amount ?? 0),
                'status' => 'Received',
                'sort' => 2,
                'row_id' => (int) $p->id,
            ];
        }

        usort($entries, function ($a, $b) {
            if (($a['date'] ?? '') === ($b['date'] ?? '')) {
                if (($a['sort'] ?? 0) === ($b['sort'] ?? 0)) {
                    return (int) ($a['row_id'] ?? 0) <=> (int) ($b['row_id'] ?? 0);
                }
                return (int) ($a['sort'] ?? 0) <=> (int) ($b['sort'] ?? 0);
            }
            return strcmp((string) ($a['date'] ?? ''), (string) ($b['date'] ?? ''));
        });

        $balance = 0.0;
        foreach ($entries as &$e) {
            $balance = round($balance + (float) ($e['debit'] ?? 0) - (float) ($e['credit'] ?? 0), 6);
            $e['balance'] = $balance;
        }
        unset($e);

        $filteredEntries = array_values(array_filter($entries, function (array $e) use ($fromDateString, $toDateString, $txType) {
            $d = (string) ($e['date'] ?? '');
            if ($fromDateString && $d !== '' && $d < $fromDateString) return false;
            if ($toDateString && $d !== '' && $d > $toDateString) return false;
            if ($txType === 'invoices') return (($e['type'] ?? '') === 'Invoice');
            if ($txType === 'payments') return (($e['type'] ?? '') === 'Payment');
            return true;
        }));

        $debitTotal = 0.0;
        $creditTotal = 0.0;
        $finalBalance = 0.0;
        foreach ($filteredEntries as $row) {
            $debitTotal = round($debitTotal + (float) ($row['debit'] ?? 0), 6);
            $creditTotal = round($creditTotal + (float) ($row['credit'] ?? 0), 6);
            $finalBalance = (float) ($row['balance'] ?? $finalBalance);
        }
        $finalBalance = round((float) $finalBalance, 6);
        $outstanding = round($debitTotal - $creditTotal, 6);

        return [
            'filters' => [
                'from_date' => $fromDateString,
                'to_date' => $toDateString,
                'tx_type' => $txType,
            ],
            'customer' => $customer,
            'summary' => [
                'outstanding' => $totalOutstanding,
                'overdue' => $totalOverdue,
                'last_payment_date' => $lastPaymentDate ? Carbon::parse($lastPaymentDate)->toDateString() : null,
            ],
            'ledger' => $filteredEntries,
            'totals' => [
                'total_invoiced' => $debitTotal,
                'total_payments' => $creditTotal,
                'outstanding' => $outstanding,
                'final_balance' => $finalBalance,
            ],
        ];
    }

    private function exportFileBaseName(Customer $customer): string
    {
        $name = strtoupper(trim((string) ($customer->name ?? 'CUSTOMER')));
        $name = preg_replace('/[^A-Z0-9]+/', '_', $name) ?: 'CUSTOMER';
        $name = trim($name, '_');
        return $name . '_Statement_' . now()->toDateString();
    }

    public function exportStatementCsv(Request $request, Customer $customer)
    {
        $data = $this->statementExportData($request, $customer);
        $file = $this->exportFileBaseName($customer) . '.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['Customer Statement']);
            fputcsv($out, ['Customer', (string) ($data['customer']->name ?? '')]);
            fputcsv($out, ['Code', (string) ($data['customer']->code ?? '')]);
            fputcsv($out, ['Phone', (string) ($data['customer']->mobile ?? '')]);
            fputcsv($out, ['GSTIN', (string) ($data['customer']->gstin ?? '')]);
            fputcsv($out, ['Credit Limit', (string) ($data['customer']->credit_limit ?? '')]);
            fputcsv($out, []);
            fputcsv($out, ['Total Outstanding', (string) ($data['summary']['outstanding'] ?? '')]);
            fputcsv($out, ['Total Overdue', (string) ($data['summary']['overdue'] ?? '')]);
            fputcsv($out, ['Last Payment Date', (string) ($data['summary']['last_payment_date'] ?? '')]);
            fputcsv($out, []);

            fputcsv($out, ['Date', 'Type', 'Reference', 'Debit', 'Credit', 'Running Balance', 'Status']);
            foreach (($data['ledger'] ?? []) as $row) {
                fputcsv($out, [
                    (string) ($row['date'] ?? ''),
                    (string) ($row['type'] ?? ''),
                    (string) ($row['ref'] ?? ''),
                    (string) ($row['debit'] ?? 0),
                    (string) ($row['credit'] ?? 0),
                    (string) ($row['balance'] ?? 0),
                    (string) ($row['status'] ?? ''),
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['Total Invoiced', (string) ($data['totals']['total_invoiced'] ?? 0)]);
            fputcsv($out, ['Total Payments Received', (string) ($data['totals']['total_payments'] ?? 0)]);
            fputcsv($out, ['Outstanding Balance', (string) ($data['totals']['outstanding'] ?? 0)]);

            fclose($out);
        }, $file, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportStatementExcel(Request $request, Customer $customer)
    {
        $data = $this->statementExportData($request, $customer);
        $file = $this->exportFileBaseName($customer) . '.xlsx';

        $rows = [];
        $rows[] = ['Customer Statement', '', '', '', '', '', ''];
        $rows[] = ['Customer', (string) ($data['customer']->name ?? ''), '', '', '', '', ''];
        $rows[] = ['Code', (string) ($data['customer']->code ?? ''), '', '', '', '', ''];
        $rows[] = ['Phone', (string) ($data['customer']->mobile ?? ''), '', '', '', '', ''];
        $rows[] = ['GSTIN', (string) ($data['customer']->gstin ?? ''), '', '', '', '', ''];
        $rows[] = ['Credit Limit', (string) ($data['customer']->credit_limit ?? ''), '', '', '', '', ''];
        $rows[] = ['', '', '', '', '', '', ''];
        $rows[] = ['Total Outstanding', (string) ($data['summary']['outstanding'] ?? ''), '', '', '', '', ''];
        $rows[] = ['Total Overdue', (string) ($data['summary']['overdue'] ?? ''), '', '', '', '', ''];
        $rows[] = ['Last Payment Date', (string) ($data['summary']['last_payment_date'] ?? ''), '', '', '', '', ''];
        $rows[] = ['', '', '', '', '', '', ''];
        $rows[] = ['Date', 'Type', 'Reference', 'Debit', 'Credit', 'Running Balance', 'Status'];
        foreach (($data['ledger'] ?? []) as $row) {
            $rows[] = [
                (string) ($row['date'] ?? ''),
                (string) ($row['type'] ?? ''),
                (string) ($row['ref'] ?? ''),
                (float) ($row['debit'] ?? 0),
                (float) ($row['credit'] ?? 0),
                (float) ($row['balance'] ?? 0),
                (string) ($row['status'] ?? ''),
            ];
        }
        $rows[] = ['', '', '', '', '', '', ''];
        $rows[] = ['Total Invoiced', '', '', (float) ($data['totals']['total_invoiced'] ?? 0), '', '', ''];
        $rows[] = ['Total Payments Received', '', '', '', (float) ($data['totals']['total_payments'] ?? 0), '', ''];
        $rows[] = ['Outstanding Balance', '', '', '', '', (float) ($data['totals']['outstanding'] ?? 0), ''];

        $export = new class($rows) implements FromArray {
            public function __construct(private array $rows) {}
            public function array(): array { return $this->rows; }
        };

        return Excel::download($export, $file, ExcelWriter::XLSX);
    }

    public function receivePayment(Request $request, Customer $customer)
    {
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

        $invoices = Invoice::query()
            ->accountingActive()
            ->where('customer_id', $customer->id)
            ->where('pending_amount', '>', 0)
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get([
                'id',
                'invoice_no',
                'invoice_date',
                'due_date',
                'total_amount',
                'paid_amount',
                'pending_amount',
                'payment_status',
            ]);

        return view('accounts-v2.pages.receive-payment', [
            'pageTitle' => 'Accounts V2 — Receive Payment',
            'customer' => $customer,
            'summary' => [
                'outstanding' => $outstanding,
                'overdue' => $overdue,
            ],
            'invoices' => $invoices,
            'defaults' => [
                'payment_date' => $today,
                'payment_mode' => 'Cash',
                'reference_no' => '',
                'notes' => '',
                'amount' => '',
            ],
            'prefillAllocations' => [],
        ]);
    }

    public function storeReceivePayment(Request $request, Customer $customer)
    {
        $intent = (string) $request->input('intent', 'save');

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'payment_mode' => 'required|in:Cash,UPI,Cheque,Bank,Other',
            'reference_no' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0.000001|decimal:0,6',
            'allocations' => 'nullable|array',
            'allocations.*' => 'nullable|numeric|min:0|decimal:0,6',
        ]);

        $customerId = (int) $customer->id;
        $paymentAmount = round((float) $validated['amount'], 6);
        $paymentDate = Carbon::parse($validated['payment_date'])->toDateString();

        $rawAllocations = $validated['allocations'] ?? [];
        $allocMap = [];
        foreach ($rawAllocations as $invoiceId => $amt) {
            $invId = (int) $invoiceId;
            $use = round((float) ($amt ?? 0), 6);
            if ($invId > 0 && $use > 0) {
                $allocMap[$invId] = ($allocMap[$invId] ?? 0) + $use;
            }
        }

        $totalAllocated = round(array_sum($allocMap), 6);
        if ($totalAllocated > $paymentAmount + 0.0000001) {
            return back()->withInput()->withErrors([
                'amount' => 'Allocated amount cannot exceed payment amount.',
            ]);
        }

        if (!empty($allocMap)) {
            $ids = array_keys($allocMap);
            $validInvoices = Invoice::query()
                ->accountingActive()
                ->where('customer_id', $customerId)
                ->where('payment_type', 'Credit')
                ->whereIn('id', $ids)
                ->get(['id', 'pending_amount'])
                ->keyBy('id');

            $validIds = array_map('intval', array_keys($validInvoices->all()));
            sort($ids);
            sort($validIds);
            if ($ids !== $validIds) {
                return back()->withInput()->withErrors([
                    'allocations' => 'Some selected invoices are invalid for this customer.',
                ]);
            }

            foreach ($allocMap as $invoiceId => $alloc) {
                $inv = $validInvoices->get((int) $invoiceId);
                $pending = round((float) ($inv?->pending_amount ?? 0), 6);
                if (round((float) $alloc, 6) > $pending + 0.0000001) {
                    return back()->withInput()->withErrors([
                        'allocations' => 'Allocation cannot exceed invoice pending.',
                    ]);
                }
            }
        }

        if ($intent === 'preview') {
            $today = now()->toDateString();
            $outstanding = (float) Invoice::query()
                ->accountingActive()
                ->where('customer_id', $customerId)
                ->sum('pending_amount');

            $overdue = (float) Invoice::query()
                ->accountingActive()
                ->where('customer_id', $customerId)
                ->where('pending_amount', '>', 0)
                ->whereRaw('COALESCE(due_date, invoice_date) < ?', [$today])
                ->sum('pending_amount');

            $invoices = Invoice::query()
                ->accountingActive()
                ->where('customer_id', $customerId)
                ->where('pending_amount', '>', 0)
                ->orderBy('invoice_date')
                ->orderBy('id')
                ->get([
                    'id',
                    'invoice_no',
                    'invoice_date',
                    'due_date',
                    'total_amount',
                    'paid_amount',
                    'pending_amount',
                    'payment_status',
                ]);

            $prefill = [];
            $remaining = $paymentAmount;
            foreach ($invoices as $inv) {
                if ($remaining <= 0) break;
                $pending = round((float) ($inv->pending_amount ?? 0), 6);
                if ($pending <= 0) continue;
                $use = round((float) min($pending, $remaining), 6);
                if ($use <= 0) continue;
                $prefill[(int) $inv->id] = $use;
                $remaining = round($remaining - $use, 6);
            }

            return view('accounts-v2.pages.receive-payment', [
                'pageTitle' => 'Accounts V2 — Receive Payment',
                'customer' => $customer,
                'summary' => [
                    'outstanding' => $outstanding,
                    'overdue' => $overdue,
                ],
                'invoices' => $invoices,
                'defaults' => [
                    'payment_date' => $paymentDate,
                    'payment_mode' => (string) $validated['payment_mode'],
                    'reference_no' => (string) ($validated['reference_no'] ?? ''),
                    'notes' => (string) ($validated['notes'] ?? ''),
                    'amount' => (string) $paymentAmount,
                ],
                'prefillAllocations' => $prefill,
            ]);
        }

        DB::transaction(function () use ($validated, $customerId, $paymentAmount, $paymentDate, $allocMap) {
            $payment = CustomerPayment::create([
                'customer_id' => $customerId,
                'payment_date' => $paymentDate,
                'amount' => $paymentAmount,
                'payment_mode' => $validated['payment_mode'],
                'reference_no' => $validated['reference_no'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $invoices = Invoice::query()
                ->accountingActive()
                ->where('customer_id', $customerId)
                ->where('payment_type', 'Credit')
                ->where('pending_amount', '>', 0)
                ->orderBy('invoice_date')
                ->lockForUpdate()
                ->get();

            $remaining = $paymentAmount;
            $touchedInvoiceIds = [];
            $invoiceById = $invoices->keyBy('id');
            $pendingById = [];
            foreach ($invoices as $inv) {
                $pendingById[(int) $inv->id] = round((float) ($inv->pending_amount ?? 0), 6);
            }

            if (!empty($allocMap)) {
                foreach ($allocMap as $invoiceId => $alloc) {
                    if ($remaining <= 0) break;
                    $invId = (int) $invoiceId;
                    $inv = $invoiceById->get($invId);
                    if (!$inv) continue;
                    $pending = round((float) ($pendingById[$invId] ?? 0), 6);
                    if ($pending <= 0) continue;

                    $use = min($alloc, $remaining, $pending);
                    $use = round((float) $use, 6);
                    if ($use <= 0) continue;

                    $payment->allocations()->create([
                        'invoice_id' => $invId,
                        'amount' => $use,
                    ]);

                    $pendingById[$invId] = round($pending - $use, 6);
                    $remaining = round($remaining - $use, 6);
                    $touchedInvoiceIds[$invId] = true;
                }
            }

            foreach ($invoices as $inv) {
                if ($remaining <= 0) break;
                $invId = (int) $inv->id;
                if (!empty($allocMap) && array_key_exists($invId, $allocMap)) {
                    continue;
                }
                $pending = round((float) ($pendingById[$invId] ?? 0), 6);
                if ($pending <= 0) continue;

                $use = min($pending, $remaining);
                $use = round((float) $use, 6);
                if ($use <= 0) continue;

                $payment->allocations()->create([
                    'invoice_id' => $invId,
                    'amount' => $use,
                ]);

                $pendingById[$invId] = round($pending - $use, 6);
                $remaining = round($remaining - $use, 6);
                $touchedInvoiceIds[$invId] = true;
            }

            $invoiceIds = array_keys($touchedInvoiceIds);
            if (!empty($invoiceIds)) {
                app(InvoicePaymentSyncService::class)->syncInvoices($invoiceIds, $customerId);
            }
        });

        return redirect()
            ->route('accounts-v2.customer.statement', $customer)
            ->with('success', 'Payment received and allocated successfully.');
    }
}
