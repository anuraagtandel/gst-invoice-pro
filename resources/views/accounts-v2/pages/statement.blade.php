@extends('layouts.app')

@section('title', 'Customer Statement - ' . ($customer->name ?? 'Customer'))

@section('content')
@push('styles')
    <style>
        @media print {
            @page { size: A4 portrait; margin: 12mm; }
            html, body { background: #fff !important; }
            body { height: auto !important; overflow: visible !important; }
            aside, main > header, .no-print { display: none !important; }
            main > div.flex-1 { padding: 0 !important; overflow: visible !important; }
            .shadow-sm { box-shadow: none !important; }
            .border { border-color: #e5e7eb !important; }
            table { font-size: 10.5px !important; }
            th, td { padding-top: 5px !important; padding-bottom: 5px !important; }
            tr { break-inside: avoid; page-break-inside: avoid; }
            thead { display: table-header-group; }
        }
    </style>
@endpush
@php
    $money = function ($value) {
        $n = is_numeric($value) ? (float) $value : 0.0;
        $s = number_format($n, 6, '.', '');
        $s = rtrim(rtrim($s, '0'), '.');
        return '₹' . ($s === '' ? '0' : $s);
    };
    $btn = 'px-3 py-2 text-xs font-semibold rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 inline-flex items-center';
    $btnPrimary = 'px-3 py-2 text-xs font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 inline-flex items-center';
    $summary = is_array($summary ?? null) ? $summary : ['outstanding' => 0, 'overdue' => 0, 'last_payment_date' => null];
    $aging = is_array($aging ?? null) ? $aging : ['current' => 0, 'b_0_7' => 0, 'b_8_15' => 0, 'b_16_30' => 0, 'b_30_plus' => 0];
    $filters = is_array($filters ?? null) ? $filters : ['from_date' => null, 'to_date' => null, 'tx_type' => 'all'];
    $ledgerTotals = is_array($ledgerTotals ?? null) ? $ledgerTotals : [];
    $ledgerDebitTotal = (float) ($ledgerTotals['debit_total'] ?? 0);
    $ledgerCreditTotal = (float) ($ledgerTotals['credit_total'] ?? 0);
    $ledgerFinalBalance = (float) ($ledgerTotals['final_balance'] ?? 0);
    $ledgerOutstanding = (float) ($ledgerTotals['outstanding'] ?? ($ledgerDebitTotal - $ledgerCreditTotal));
@endphp

<div class="space-y-4">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-4 py-3 no-print">
        <a href="{{ route('accounts-v2.index') }}" class="text-sm text-slate-600 hover:text-slate-900">← Back to Customer Accounts</a>
    </div>

    <form method="GET" action="{{ route('accounts-v2.customer.statement', $customer) }}" class="bg-white rounded-xl border border-slate-200 shadow-sm px-4 py-3 no-print">
        <div class="grid grid-cols-12 gap-3 items-end">
            <div class="col-span-6 md:col-span-3">
                <label class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wide">From Date</label>
                <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}"
                       class="mt-1 w-full py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
            </div>
            <div class="col-span-6 md:col-span-3">
                <label class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wide">To Date</label>
                <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}"
                       class="mt-1 w-full py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
            </div>
            <div class="col-span-12 md:col-span-3">
                <label class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wide">Transaction Type</label>
                @php
                    $tx = $filters['tx_type'] ?? 'all';
                @endphp
                <select name="tx_type" class="mt-1 w-full py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                    <option value="all" @selected($tx === 'all')>All</option>
                    <option value="invoices" @selected($tx === 'invoices')>Invoices</option>
                    <option value="payments" @selected($tx === 'payments')>Payments</option>
                </select>
            </div>
            <div class="col-span-12 md:col-span-3 flex gap-2 justify-end">
                <button type="submit" class="{{ $btnPrimary }}">Apply Filters</button>
                <a href="{{ route('accounts-v2.customer.statement', $customer) }}" class="{{ $btn }}">Reset</a>
            </div>
        </div>
    </form>

<div class="bg-white border border-slate-200 rounded-xl shadow-sm p-3">
    <div class="flex flex-col lg:flex-row gap-4 lg:items-start lg:justify-between">
        <div class="min-w-0">
            <div class="text-lg font-syne font-bold text-slate-900 leading-tight">{{ $customer->name }}</div>
            <div class="mt-1 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1 text-sm">
                <div class="text-slate-700"><span class="text-slate-500">Code:</span> {{ $customer->code ?? '-' }}</div>
                <div class="text-slate-700"><span class="text-slate-500">Phone:</span> {{ $customer->mobile ?? '-' }}</div>
                <div class="text-slate-700"><span class="text-slate-500">GSTIN:</span> {{ $customer->gstin ?? '-' }}</div>
                <div class="text-slate-700"><span class="text-slate-500">Credit Limit:</span> {{ (float)($customer->credit_limit ?? 0) > 0 ? $money($customer->credit_limit) : '—' }}</div>
            </div>
        </div>

        <div class="w-full lg:w-[420px]">
            <div class="grid grid-cols-3 gap-2">
                <div class="rounded-lg border border-slate-200 p-2">
                    <div class="text-[11px] font-semibold text-slate-600 uppercase tracking-wide">Outstanding</div>
                    <div class="mt-1 text-sm font-bold text-slate-900 tabular-nums">{{ $money($summary['outstanding'] ?? 0) }}</div>
                </div>
                <div class="rounded-lg border border-slate-200 p-2">
                    <div class="text-[11px] font-semibold text-slate-600 uppercase tracking-wide">Overdue</div>
                    <div class="mt-1 text-sm font-bold text-slate-900 tabular-nums">{{ $money($summary['overdue'] ?? 0) }}</div>
                </div>
                <div class="rounded-lg border border-slate-200 p-2">
                    <div class="text-[11px] font-semibold text-slate-600 uppercase tracking-wide">Last Payment</div>
                    <div class="mt-1 text-sm font-bold text-slate-900 tabular-nums">{{ $summary['last_payment_date'] ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3 flex flex-wrap items-center justify-end gap-2 no-print">
        <a href="{{ route('accounts-v2.customer.receive-payment', $customer) }}"
           class="{{ $btnPrimary }}">
            Receive Payment
        </a>
        <button type="button" onclick="window.print()"
                class="{{ $btn }}">
            Print Statement
        </button>
        <a href="{{ route('accounts-v2.customer.export.xlsx', [$customer, 'from_date' => $filters['from_date'] ?? null, 'to_date' => $filters['to_date'] ?? null, 'tx_type' => $filters['tx_type'] ?? 'all']) }}"
           class="{{ $btn }}">
            Export Excel
        </a>
        <a href="{{ route('accounts-v2.customer.export.csv', [$customer, 'from_date' => $filters['from_date'] ?? null, 'to_date' => $filters['to_date'] ?? null, 'tx_type' => $filters['tx_type'] ?? 'all']) }}"
           class="{{ $btn }}">
            Export CSV
        </a>
    </div>
</div>

<div class="mt-3 bg-white border border-slate-200 rounded-xl shadow-sm p-3">
    <div class="text-xs font-semibold text-slate-600 uppercase tracking-wide">Aging</div>
    <div class="mt-2 grid grid-cols-5 gap-2">
        <div class="rounded-lg border border-slate-200 p-2">
            <div class="text-[11px] text-slate-600">Current</div>
            <div class="text-sm font-semibold text-slate-900 tabular-nums">{{ $money($aging['current'] ?? 0) }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 p-2">
            <div class="text-[11px] text-slate-600">0–7</div>
            <div class="text-sm font-semibold text-slate-900 tabular-nums">{{ $money($aging['b_0_7'] ?? 0) }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 p-2">
            <div class="text-[11px] text-slate-600">8–15</div>
            <div class="text-sm font-semibold text-slate-900 tabular-nums">{{ $money($aging['b_8_15'] ?? 0) }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 p-2">
            <div class="text-[11px] text-slate-600">16–30</div>
            <div class="text-sm font-semibold text-slate-900 tabular-nums">{{ $money($aging['b_16_30'] ?? 0) }}</div>
        </div>
        <div class="rounded-lg border border-slate-200 p-2">
            <div class="text-[11px] text-slate-600">30+</div>
            <div class="text-sm font-semibold text-slate-900 tabular-nums">{{ $money($aging['b_30_plus'] ?? 0) }}</div>
        </div>
    </div>
</div>

<div class="mt-3 bg-white border border-slate-200 rounded-xl shadow-sm">
    <div class="p-3 border-b border-slate-200 flex items-center justify-between">
        <div>
            <div class="text-sm font-semibold text-slate-900">Unified Ledger</div>
            <div class="text-xs text-slate-600 mt-1">Oldest → newest. Debit increases balance (Invoice). Credit reduces balance (Payment).</div>
        </div>
        <div class="text-xs text-slate-600">
            Rows: {{ ($ledger ?? null) ? $ledger->total() : 0 }}
        </div>
    </div>

    <div class="overflow-auto max-h-[560px] relative">
        <table class="min-w-full text-sm tabular-nums">
            <thead class="sticky top-0 z-10 bg-white/95 backdrop-blur border-b border-slate-200">
                <tr class="text-left text-slate-600">
                    <th class="py-2 px-3 font-semibold">Date</th>
                    <th class="py-2 px-3 font-semibold">Type</th>
                    <th class="py-2 px-3 font-semibold">Reference</th>
                    <th class="py-2 px-3 font-semibold text-right">Debit</th>
                    <th class="py-2 px-3 font-semibold text-right">Credit</th>
                    <th class="py-2 px-3 font-semibold text-right">Running Balance</th>
                    <th class="py-2 px-3 font-semibold">Status</th>
                </tr>
            </thead>
            <tbody class="text-slate-800">
                @if(!($ledger ?? null) || $ledger->count() === 0)
                    <tr class="border-t border-slate-100">
                        <td colspan="7" class="py-6 px-3 text-sm text-slate-600">No ledger entries.</td>
                    </tr>
                @else
                    @foreach($ledger as $e)
                        @php
                            $type = (string)($e['type'] ?? '');
                            $status = (string)($e['status'] ?? '');
                            $chip = 'bg-slate-50 text-slate-700 border border-slate-200';
                            if ($type === 'Payment') {
                                $chip = 'bg-sky-50 text-sky-700 border border-sky-200';
                            } else {
                                $s = strtolower(trim($status));
                                if ($s === 'paid') $chip = 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                                if ($s === 'partial') $chip = 'bg-amber-50 text-amber-800 border border-amber-200';
                                if ($s === 'unpaid' || $s === 'outstanding') $chip = 'bg-orange-50 text-orange-800 border border-orange-200';
                                if ($s === 'overdue') $chip = 'bg-rose-50 text-rose-700 border border-rose-200';
                            }
                        @endphp
                        <tr class="border-t border-slate-100 {{ $loop->odd ? 'bg-white' : 'bg-slate-50/40' }} hover:bg-slate-50">
                            <td class="py-1.5 px-3 text-slate-700 whitespace-nowrap">{{ $e['date'] ?? '-' }}</td>
                            <td class="py-1.5 px-3 text-slate-900 font-semibold whitespace-nowrap">{{ $type }}</td>
                            <td class="py-1.5 px-3 text-slate-700 whitespace-nowrap">
                                @if($type === 'Invoice')
                                    <a href="{{ route('app.invoices.edit', $e['row_id'] ?? null) }}"
                                       class="text-slate-700 hover:text-slate-900 hover:underline font-semibold">
                                        {{ $e['ref'] ?? '-' }}
                                    </a>
                                @else
                                    {{ $e['ref'] ?? '-' }}
                                @endif
                            </td>
                            <td class="py-1.5 px-3 text-right whitespace-nowrap">{{ (float)($e['debit'] ?? 0) > 0 ? $money($e['debit']) : '—' }}</td>
                            <td class="py-1.5 px-3 text-right whitespace-nowrap">{{ (float)($e['credit'] ?? 0) > 0 ? $money($e['credit']) : '—' }}</td>
                            <td class="py-1.5 px-3 text-right whitespace-nowrap font-semibold">{{ $money($e['balance'] ?? 0) }}</td>
                            <td class="py-1.5 px-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $chip }}">
                                    {{ $status !== '' ? $status : '—' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
            <tfoot>
                <tr class="border-t border-slate-200 bg-slate-50">
                    <td class="py-2 px-3 text-sm font-semibold text-slate-700" colspan="3">Totals</td>
                    <td class="py-2 px-3 text-right whitespace-nowrap font-semibold text-slate-900">{{ $money($ledgerDebitTotal) }}</td>
                    <td class="py-2 px-3 text-right whitespace-nowrap font-semibold text-slate-900">{{ $money($ledgerCreditTotal) }}</td>
                    <td class="py-2 px-3 text-right whitespace-nowrap font-semibold text-slate-900">{{ $money($ledgerFinalBalance) }}</td>
                    <td class="py-2 px-3"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if(($ledger ?? null) && $ledger->hasPages())
        <div class="px-4 py-3 border-t border-slate-200">
            {{ $ledger->links() }}
        </div>
    @endif

    <div class="px-3 py-3 border-t border-slate-200 bg-white">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-2">
                <div class="text-[11px] font-semibold text-slate-600 uppercase tracking-wide">Total Invoiced</div>
                <div class="mt-1 text-sm font-bold text-slate-900 tabular-nums">{{ $money($ledgerDebitTotal) }}</div>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-2">
                <div class="text-[11px] font-semibold text-slate-600 uppercase tracking-wide">Total Payments Received</div>
                <div class="mt-1 text-sm font-bold text-slate-900 tabular-nums">{{ $money($ledgerCreditTotal) }}</div>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-2">
                <div class="text-[11px] font-semibold text-slate-600 uppercase tracking-wide">Current Outstanding Balance</div>
                <div class="mt-1 text-sm font-bold text-slate-900 tabular-nums">{{ $money($ledgerOutstanding) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="mt-4 grid grid-cols-1 xl:grid-cols-2 gap-4">
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm">
        <div class="px-4 py-3 border-b border-slate-200">
            <div class="text-sm font-semibold text-slate-900">Invoice History</div>
            <div class="text-xs text-slate-500 mt-1">Latest 20 invoices (for quick visibility).</div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm tabular-nums">
                <thead>
                    <tr class="text-left text-slate-600 border-b border-slate-200">
                        <th class="py-2 px-4 font-semibold">Date</th>
                        <th class="py-2 px-4 font-semibold">Invoice No</th>
                        <th class="py-2 px-4 font-semibold text-right">Total</th>
                        <th class="py-2 px-4 font-semibold text-right">Pending</th>
                        <th class="py-2 px-4 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="text-slate-800">
                    @php $invRows = $invoices_latest ?? collect(); @endphp
                    @if($invRows->count() === 0)
                        <tr class="border-t border-slate-100">
                            <td colspan="5" class="py-6 px-4 text-sm text-slate-600">No invoices.</td>
                        </tr>
                    @else
                        @foreach($invRows as $inv)
                            <tr class="border-t border-slate-100 hover:bg-slate-50">
                                <td class="py-2 px-4 text-slate-700 tabular-nums whitespace-nowrap">{{ $inv->invoice_date ? \Illuminate\Support\Carbon::parse($inv->invoice_date)->toDateString() : '-' }}</td>
                                <td class="py-2 px-4 whitespace-nowrap">
                                    <a href="{{ route('app.invoices.edit', $inv->id) }}"
                                       class="text-slate-900 hover:text-slate-900 hover:underline font-semibold">
                                        {{ $inv->invoice_no ?? ('INV#' . $inv->id) }}
                                    </a>
                                </td>
                                <td class="py-2 px-4 text-right tabular-nums whitespace-nowrap">{{ $money($inv->total_amount) }}</td>
                                <td class="py-2 px-4 text-right tabular-nums whitespace-nowrap">{{ $money($inv->pending_amount) }}</td>
                                <td class="py-2 px-4 text-slate-700 whitespace-nowrap">{{ $inv->payment_status ?? '-' }}</td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm">
        <div class="px-4 py-3 border-b border-slate-200">
            <div class="text-sm font-semibold text-slate-900">Payment History</div>
            <div class="text-xs text-slate-500 mt-1">Latest 20 payments (for quick visibility).</div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm tabular-nums">
                <thead>
                    <tr class="text-left text-slate-600 border-b border-slate-200">
                        <th class="py-2 px-4 font-semibold">Date</th>
                        <th class="py-2 px-4 font-semibold">Reference</th>
                        <th class="py-2 px-4 font-semibold">Mode</th>
                        <th class="py-2 px-4 font-semibold text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="text-slate-800">
                    @php $payRows = $payments_latest ?? collect(); @endphp
                    @if($payRows->count() === 0)
                        <tr class="border-t border-slate-100">
                            <td colspan="4" class="py-6 px-4 text-sm text-slate-600">No payments.</td>
                        </tr>
                    @else
                        @foreach($payRows as $p)
                            @php
                                $pref = 'PAY#' . $p->id;
                                if ($p->reference_no) $pref .= ' (' . $p->reference_no . ')';
                            @endphp
                            <tr class="border-t border-slate-100 hover:bg-slate-50">
                                <td class="py-2 px-4 text-slate-700 tabular-nums whitespace-nowrap">{{ $p->payment_date ? \Illuminate\Support\Carbon::parse($p->payment_date)->toDateString() : '-' }}</td>
                                <td class="py-2 px-4 text-slate-900 font-semibold whitespace-nowrap">{{ $pref }}</td>
                                <td class="py-2 px-4 text-slate-700 whitespace-nowrap">{{ $p->payment_mode ?? '-' }}</td>
                                <td class="py-2 px-4 text-right tabular-nums whitespace-nowrap">{{ $money($p->amount) }}</td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
@endsection
