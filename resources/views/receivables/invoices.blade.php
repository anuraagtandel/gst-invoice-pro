@extends('layouts.app')

@section('title', 'Receivables - Invoice-wise')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-syne font-semibold text-lg text-slate-800">Receivables / Outstanding</h3>
            <p class="text-sm text-slate-500">Invoice-wise pending list</p>
        </div>
        <form action="{{ route('app.receivables.invoices') }}" method="GET" class="w-full lg:w-auto">
            <div class="flex flex-col lg:flex-row gap-3 w-full">
                <div class="w-full lg:w-72">
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Search</label>
                    <div class="relative">
                        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
                        <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Customer or Invoice No..." class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>
                <div class="w-full sm:w-64">
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Customer</label>
                    <select name="customer_id" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                        <option value="">All</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" @selected((string) $customerId === (string) $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full sm:w-48">
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                        <option value="outstanding" @selected(($status ?? 'outstanding') === 'outstanding')>Outstanding</option>
                        <option value="overdue" @selected(($status ?? '') === 'overdue')>Overdue</option>
                        <option value="partial" @selected(($status ?? '') === 'partial')>Partial</option>
                        <option value="unpaid" @selected(($status ?? '') === 'unpaid')>Unpaid</option>
                        <option value="paid" @selected(($status ?? '') === 'paid')>Paid</option>
                        <option value="all" @selected(($status ?? '') === 'all')>All</option>
                    </select>
                </div>
                <div class="w-full sm:w-40">
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Date Range</label>
                    <select name="range" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                        <option value="all" @selected(($range['preset'] ?? 'all') === 'all')>All</option>
                        <option value="today" @selected(($range['preset'] ?? '') === 'today')>Today</option>
                        <option value="month" @selected(($range['preset'] ?? '') === 'month')>Month</option>
                        <option value="year" @selected(($range['preset'] ?? '') === 'year')>Year</option>
                        <option value="custom" @selected(($range['preset'] ?? '') === 'custom')>Custom</option>
                    </select>
                </div>
                <div class="w-full sm:w-44">
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">From</label>
                    <input type="date" name="start_date" value="{{ $range['start_date'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div class="w-full sm:w-44">
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">To</label>
                    <input type="date" name="end_date" value="{{ $range['end_date'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-sm">Apply</button>
                    <a href="{{ route('app.receivables.invoices') }}" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <div class="flex items-center gap-2">
        <a href="{{ route('app.receivables.index') }}" class="px-4 py-2 rounded-lg text-sm font-semibold border transition-colors bg-white text-slate-700 border-slate-200 hover:bg-slate-50">
            Customer-wise
        </a>
        <a href="{{ route('app.receivables.invoices') }}" class="px-4 py-2 rounded-lg text-sm font-semibold border transition-colors {{ Request::is('app/receivables-invoices') ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' }}">
            Invoice-wise
        </a>
        <a href="{{ route('app.receivables.aging') }}" class="px-4 py-2 rounded-lg text-sm font-semibold border transition-colors bg-white text-slate-700 border-slate-200 hover:bg-slate-50">
            Aging
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($invoices->isEmpty())
            @include('partials._empty_state', ['icon' => 'ph-receipt', 'title' => 'No pending invoices', 'message' => 'No outstanding invoices match the selected filters.'])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Invoice Number</th>
                            <th class="px-6 py-3 font-semibold">Customer Name</th>
                            <th class="px-6 py-3 font-semibold">Invoice Date</th>
                            <th class="px-6 py-3 font-semibold">Due Date</th>
                            <th class="px-6 py-3 font-semibold text-right">Total Amount</th>
                            <th class="px-6 py-3 font-semibold text-right">Paid Amount</th>
                            <th class="px-6 py-3 font-semibold text-right">Pending Amount</th>
                            <th class="px-6 py-3 font-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($invoices as $inv)
                            @php
                                $total = (float) ($inv->total_amount ?? $inv->grand_total ?? 0);
                                $paid = (float) ($inv->paid_amount ?? 0);
                                $pending = (float) ($inv->pending_amount ?? 0);
                                $tDisp = rtrim(rtrim(number_format($total, 6, '.', ''), '0'), '.');
                                $pDisp = rtrim(rtrim(number_format($paid, 6, '.', ''), '0'), '.');
                                $pendDisp = rtrim(rtrim(number_format($pending, 6, '.', ''), '0'), '.');

                                $today = now()->startOfDay();
                                $due = $inv->due_date ? $inv->due_date->copy()->startOfDay() : null;
                                $isOverdue = $due ? $due->lt($today) : false;
                                $isNearDue = $due ? (!$isOverdue && $due->lte($today->copy()->addDays(3))) : false;
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition-colors {{ $isOverdue ? 'bg-red-50' : ($isNearDue ? 'bg-amber-50' : '') }}">
                                <td class="px-6 py-4 font-medium text-indigo-600">{{ $inv->invoice_no }}</td>
                                <td class="px-6 py-4 text-slate-800">{{ $inv->customer?->name ?? 'Unknown' }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ optional($inv->invoice_date)->format('d-m-Y') }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="text-slate-600">{{ optional($inv->due_date)->format('d-m-Y') ?: '-' }}</span>
                                        @if($isOverdue)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700 border border-red-200 uppercase tracking-wider">OVERDUE</span>
                                        @elseif($isNearDue)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 uppercase tracking-wider">DUE SOON</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right font-semibold text-slate-900">₹{{ $tDisp }}</td>
                                <td class="px-6 py-4 text-right text-slate-700">₹{{ $pDisp }}</td>
                                <td class="px-6 py-4 text-right font-bold {{ $isOverdue ? 'text-red-700' : ($isNearDue ? 'text-amber-800' : 'text-slate-900') }}">₹{{ $pendDisp }}</td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-3">
                                        <a href="{{ route('app.invoices.edit', $inv->id) }}" class="text-slate-600 hover:text-slate-800 font-semibold">Edit</a>
                                        <a href="{{ route('app.payments.receive', ['customer_id' => $inv->customer_id, 'invoice_id' => $inv->id]) }}" class="text-indigo-600 hover:text-indigo-700 font-semibold">Receive Payment</a>
                                        <a href="{{ route('app.accounts.customer_ledger.show', $inv->customer_id) }}" class="text-slate-700 hover:text-slate-900 font-semibold">Ledger</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-200">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
