@extends('layouts.app')

@section('title', 'Receivables / Outstanding')

@section('content')
@php
    $fmt = function ($n) {
        $v6 = number_format((float) $n, 6, '.', '');
        return '₹' . rtrim(rtrim($v6, '0'), '.');
    };
    $trendValues = $trend['values'] ?? [];
    $w = 520;
    $h = 140;
    $pad = 10;
    $min = null;
    $max = null;
    foreach ($trendValues as $v) {
        $n = (float) $v;
        $min = $min === null ? $n : min($min, $n);
        $max = $max === null ? $n : max($max, $n);
    }
    if ($min === null) {
        $min = 0;
        $max = 0;
    }
    $range = max(0.0000001, $max - $min);
    $count = max(1, count($trendValues));
    $step = $count === 1 ? 0 : ($w - ($pad * 2)) / ($count - 1);
    $points = [];
    $pointsArea = [];
    for ($i = 0; $i < $count; $i++) {
        $x = $pad + ($i * $step);
        $y = $h - $pad - (((float) $trendValues[$i] - $min) / $range) * ($h - ($pad * 2));
        $points[] = number_format($x, 2, '.', '') . ',' . number_format($y, 2, '.', '');
    }
    if (!empty($points)) {
        $firstX = $pad;
        $lastX = $pad + (($count - 1) * $step);
        $pointsArea = $points;
        $pointsArea[] = number_format($lastX, 2, '.', '') . ',' . number_format($h - $pad, 2, '.', '');
        $pointsArea[] = number_format($firstX, 2, '.', '') . ',' . number_format($h - $pad, 2, '.', '');
    }
@endphp
<div class="space-y-6">

    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-syne font-semibold text-lg text-slate-800">Receivables / Outstanding</h3>
            <p class="text-sm text-slate-500">Customer-wise pending amounts. Sorted by highest pending first.</p>
        </div>
        <form action="{{ route('app.receivables.index') }}" method="GET" class="w-full lg:w-[680px]" x-data="{ rangePreset: '{{ $range['preset'] ?? 'all' }}', openPicker(el) { if (!el) return; if (typeof el.showPicker === 'function') { try { el.showPicker(); } catch (e) { } } } }">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Search</label>
                    <div class="relative">
                        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Customer or Invoice No..." class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Customer</label>
                    <select name="customer_id" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                        <option value="">All</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" @selected((string) $customerId === (string) $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
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

                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Date Range</label>
                    <select name="range" x-model="rangePreset" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                        <option value="all" @selected(($range['preset'] ?? 'all') === 'all')>All</option>
                        <option value="today" @selected(($range['preset'] ?? '') === 'today')>Today</option>
                        <option value="month" @selected(($range['preset'] ?? '') === 'month')>Month</option>
                        <option value="year" @selected(($range['preset'] ?? '') === 'year')>Year</option>
                        <option value="custom" @selected(($range['preset'] ?? '') === 'custom')>Custom</option>
                    </select>
                </div>

                <div x-show="rangePreset === 'custom'">
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">From</label>
                    <input type="date" name="start_date" value="{{ $range['start_date'] ?? '' }}" @click="openPicker($event.target)" @focus="openPicker($event.target)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none cursor-pointer">
                </div>

                <div x-show="rangePreset === 'custom'">
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">To</label>
                    <input type="date" name="end_date" value="{{ $range['end_date'] ?? '' }}" @click="openPicker($event.target)" @focus="openPicker($event.target)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none cursor-pointer">
                </div>

                <div class="md:col-span-2 flex flex-col sm:flex-row sm:justify-end gap-2 pt-1">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-sm w-full sm:w-auto">Apply</button>
                    <a href="{{ route('app.receivables.index') }}" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold w-full sm:w-auto text-center">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
        <div class="relative overflow-hidden rounded-2xl border border-slate-200/60 bg-white/70 shadow-[0_10px_30px_-18px_rgba(15,23,42,0.35)] backdrop-blur p-5">
            <div class="absolute inset-0 opacity-10 bg-gradient-to-br from-indigo-500 to-fuchsia-500"></div>
            <div class="relative">
                <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Total Outstanding</div>
                <div class="mt-2 font-syne font-bold text-2xl text-slate-900">{{ $fmt($summary['total_outstanding'] ?? 0) }}</div>
            </div>
        </div>
        <div class="relative overflow-hidden rounded-2xl border border-red-200 bg-red-50/50 shadow-[0_10px_30px_-18px_rgba(15,23,42,0.35)] backdrop-blur p-5">
            <div class="absolute inset-0 opacity-10 bg-gradient-to-br from-red-500 to-rose-500"></div>
            <div class="relative">
                <div class="text-xs font-semibold uppercase tracking-widest text-red-700">Total Overdue</div>
                <div class="mt-2 font-syne font-bold text-2xl text-red-800">{{ $fmt($summary['total_overdue'] ?? 0) }}</div>
            </div>
        </div>
        <div class="relative overflow-hidden rounded-2xl border border-slate-200/60 bg-white/70 shadow-[0_10px_30px_-18px_rgba(15,23,42,0.35)] backdrop-blur p-5">
            <div class="absolute inset-0 opacity-10 bg-gradient-to-br from-amber-500 to-orange-500"></div>
            <div class="relative">
                <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">This Month Pending</div>
                <div class="mt-2 font-syne font-bold text-2xl text-slate-900">{{ $fmt($summary['this_month_pending'] ?? 0) }}</div>
            </div>
        </div>
        <div class="relative overflow-hidden rounded-2xl border border-slate-200/60 bg-white/70 shadow-[0_10px_30px_-18px_rgba(15,23,42,0.35)] backdrop-blur p-5">
            <div class="absolute inset-0 opacity-10 bg-gradient-to-br from-emerald-500 to-cyan-500"></div>
            <div class="relative">
                <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Collection Received (This Month)</div>
                <div class="mt-2 font-syne font-bold text-2xl text-slate-900">{{ $fmt($summary['this_month_collections'] ?? 0) }}</div>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2">
        <a href="{{ route('app.receivables.index') }}" class="px-4 py-2 rounded-lg text-sm font-semibold border transition-colors {{ Request::is('app/receivables') ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' }}">
            Customer-wise
        </a>
        <a href="{{ route('app.receivables.invoices') }}" class="px-4 py-2 rounded-lg text-sm font-semibold border transition-colors bg-white text-slate-700 border-slate-200 hover:bg-slate-50">
            Invoice-wise
        </a>
        <a href="{{ route('app.receivables.aging') }}" class="px-4 py-2 rounded-lg text-sm font-semibold border transition-colors bg-white text-slate-700 border-slate-200 hover:bg-slate-50">
            Aging
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($rows->isEmpty())
            @include('partials._empty_state', ['icon' => 'ph-wallet', 'title' => 'No outstanding found', 'message' => 'No pending invoices for the selected filter.'])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Customer</th>
                            <th class="px-6 py-3 font-semibold text-right">Total Invoice Amount</th>
                            <th class="px-6 py-3 font-semibold text-right">Total Paid</th>
                            <th class="px-6 py-3 font-semibold text-right">Total Pending</th>
                            <th class="px-6 py-3 font-semibold">Last Payment Date</th>
                            <th class="px-6 py-3 font-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($rows as $r)
                            @php
                                $totalInvoice = (float) $r->total_invoice_amount;
                                $totalPaid = (float) $r->total_paid;
                                $totalPending = (float) $r->total_pending;

                                $ti6 = number_format($totalInvoice, 6, '.', '');
                                $tp6 = number_format($totalPaid, 6, '.', '');
                                $tpend6 = number_format($totalPending, 6, '.', '');

                                $tiDisp = rtrim(rtrim($ti6, '0'), '.');
                                $tpDisp = rtrim(rtrim($tp6, '0'), '.');
                                $tpendDisp = rtrim(rtrim($tpend6, '0'), '.');
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 font-medium text-slate-800">{{ $r->customer_name }}</td>
                                <td class="px-6 py-4 text-right text-slate-700">₹{{ $tiDisp }}</td>
                                <td class="px-6 py-4 text-right text-slate-700">₹{{ $tpDisp }}</td>
                                <td class="px-6 py-4 text-right font-bold {{ $totalPending > 0 ? 'text-amber-800' : 'text-slate-800' }}">₹{{ $tpendDisp }}</td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ $r->last_payment_date ? \Carbon\Carbon::parse($r->last_payment_date)->format('d-m-Y') : '-' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-3">
                                        <a href="{{ route('app.receivables.show', $r->customer_id) }}" class="text-indigo-600 hover:text-indigo-700 font-semibold">View</a>
                                        <a href="{{ route('app.payments.receive', ['customer_id' => $r->customer_id]) }}" class="text-slate-700 hover:text-slate-900 font-semibold">Receive Payment</a>
                                        <a href="{{ route('app.accounts.customer_ledger.show', $r->customer_id) }}" class="text-slate-700 hover:text-slate-900 font-semibold">Ledger</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
