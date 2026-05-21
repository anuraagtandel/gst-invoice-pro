@extends('layouts.app')

@section('title', 'Receivables - Aging Report')

@section('content')
@php
    $fmt = function ($n) {
        $v6 = number_format((float) $n, 6, '.', '');
        return '₹' . rtrim(rtrim($v6, '0'), '.');
    };
@endphp

<div class="space-y-6">
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-syne font-semibold text-lg text-slate-800">Receivables Aging Report</h3>
            <p class="text-sm text-slate-500">Pending invoices grouped by age (based on due date, fallback invoice date).</p>
        </div>
        <form action="{{ route('app.receivables.aging') }}" method="GET" class="w-full lg:w-auto">
            <div class="flex flex-col sm:flex-row gap-3 w-full">
                <div class="w-full sm:w-80">
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
                    <a href="{{ route('app.receivables.aging') }}" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <div class="flex items-center gap-2">
        <a href="{{ route('app.receivables.index') }}" class="px-4 py-2 rounded-lg text-sm font-semibold border transition-colors bg-white text-slate-700 border-slate-200 hover:bg-slate-50">
            Customer-wise
        </a>
        <a href="{{ route('app.receivables.invoices') }}" class="px-4 py-2 rounded-lg text-sm font-semibold border transition-colors bg-white text-slate-700 border-slate-200 hover:bg-slate-50">
            Invoice-wise
        </a>
        <a href="{{ route('app.receivables.aging') }}" class="px-4 py-2 rounded-lg text-sm font-semibold border transition-colors {{ Request::is('app/receivables-aging') ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' }}">
            Aging
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">0–7 days</p>
            <p class="font-syne font-bold text-2xl text-slate-900">{{ $fmt($buckets['0_7'] ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">8–15 days</p>
            <p class="font-syne font-bold text-2xl text-slate-900">{{ $fmt($buckets['8_15'] ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">16–30 days</p>
            <p class="font-syne font-bold text-2xl text-slate-900">{{ $fmt($buckets['16_30'] ?? 0) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-red-200 shadow-sm p-5 bg-red-50/40">
            <p class="text-xs font-semibold text-red-700 uppercase tracking-wider mb-2">30+ days</p>
            <p class="font-syne font-bold text-2xl text-red-800">{{ $fmt($buckets['30_plus'] ?? 0) }}</p>
        </div>
    </div>
</div>
@endsection
