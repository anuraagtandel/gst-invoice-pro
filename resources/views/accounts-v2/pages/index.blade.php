@extends('layouts.app')

@section('title', 'Customer Accounts')

@section('content')
@php
    $filters = is_array($filters ?? null) ? $filters : [];
    $money = function ($value) {
        $n = is_numeric($value) ? (float) $value : 0.0;
        $s = number_format($n, 6, '.', '');
        $s = rtrim(rtrim($s, '0'), '.');
        return '₹' . ($s === '' ? '0' : $s);
    };
@endphp

<div class="space-y-4">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-4 py-3">
        <div class="flex items-center justify-between gap-3">
            <div>
                <div class="text-lg font-syne font-semibold text-slate-900">Customer Accounts</div>
                <div class="text-xs text-slate-500">Accounts V2 (primary). Customer-wise outstanding and statement access.</div>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('accounts-v2.index') }}" class="bg-white rounded-xl border border-slate-200 shadow-sm px-4 py-3">
        <div class="grid grid-cols-12 gap-2 items-end">
            <div class="col-span-12 md:col-span-4">
                <label class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wide">Customer Search</label>
                <div class="relative mt-1">
                    <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-base"></i>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name / Phone / Code / Invoice No"
                           class="w-full pl-10 pr-3 py-2 text-sm border border-slate-200 rounded-lg leading-5 focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
            </div>

            <div class="col-span-12 md:col-span-2">
                <label class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wide">Status</label>
                <select name="status" class="mt-1 w-full py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                    @php $st = $filters['status'] ?? 'all'; @endphp
                    <option value="all" @selected($st === 'all')>All</option>
                    <option value="outstanding" @selected($st === 'outstanding')>Outstanding</option>
                    <option value="paid" @selected($st === 'paid')>Paid</option>
                    <option value="overdue" @selected($st === 'overdue')>Overdue</option>
                </select>
            </div>

            <div class="col-span-6 md:col-span-2">
                <label class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wide">From</label>
                <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}"
                       class="mt-1 w-full py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
            </div>

            <div class="col-span-6 md:col-span-2">
                <label class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wide">To</label>
                <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}"
                       class="mt-1 w-full py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
            </div>

            <div class="col-span-12 md:col-span-2 flex gap-2 justify-end">
                <button type="submit" class="px-3 py-2 text-xs font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
                    Apply
                </button>
                <a href="{{ route('accounts-v2.index') }}" class="px-3 py-2 text-xs font-semibold rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 inline-flex items-center">
                    Reset
                </a>
            </div>
        </div>
    </form>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm tabular-nums">
            <thead>
                <tr class="text-left text-slate-600 border-b border-slate-200">
                    <th class="py-2 px-4 font-semibold">Customer Name</th>
                    <th class="py-2 px-4 font-semibold">Phone</th>
                    <th class="py-2 px-4 font-semibold text-right">Outstanding</th>
                    <th class="py-2 px-4 font-semibold text-right">Overdue</th>
                    <th class="py-2 px-4 font-semibold text-right">Credit Limit</th>
                    <th class="py-2 px-4 font-semibold">Last Payment</th>
                    <th class="py-2 px-4 font-semibold">Status</th>
                    <th class="py-2 px-4 font-semibold text-right">Action</th>
                </tr>
            </thead>
            <tbody class="text-slate-800">
                @php
                    $rows = ($customers ?? null) ? $customers : null;
                @endphp

                @if(!$rows || $rows->count() === 0)
                    <tr class="border-t border-slate-100">
                        <td colspan="8" class="py-6 px-4 text-sm text-slate-600">No customers found.</td>
                    </tr>
                @else
                    @foreach($rows as $c)
                        @php
                            $outstanding = (float) ($c->outstanding_amount ?? 0);
                            $overdue = (float) ($c->overdue_amount ?? 0);
                            $creditLimit = (float) ($c->credit_limit ?? 0);
                            $overLimit = $creditLimit > 0 && $outstanding > ($creditLimit + 0.000001);
                            if ($outstanding <= 0) {
                                $statusText = 'Paid';
                                $statusClass = 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                            } elseif ($overLimit) {
                                $statusText = 'Over Limit';
                                $statusClass = 'bg-red-50 text-red-700 border border-red-200';
                            } elseif ($overdue > 0) {
                                $statusText = 'Overdue';
                                $statusClass = 'bg-rose-50 text-rose-700 border border-rose-200';
                            } else {
                                $statusText = 'Outstanding';
                                $statusClass = 'bg-amber-50 text-amber-800 border border-amber-200';
                            }
                            $lastPay = $c->last_payment_date ? \Illuminate\Support\Carbon::parse($c->last_payment_date)->toDateString() : '-';
                        @endphp
                        <tr class="border-t border-slate-100 hover:bg-slate-50">
                            <td class="py-2 px-4">
                                <div class="font-semibold text-slate-900 leading-tight">{{ $c->name }}</div>
                                <div class="text-xs text-slate-500 leading-tight">{{ $c->code ?? '' }}</div>
                            </td>
                            <td class="py-2 px-4 text-slate-700 whitespace-nowrap">{{ $c->mobile ?? '-' }}</td>
                            <td class="py-2 px-4 text-right tabular-nums whitespace-nowrap">{{ $money($outstanding) }}</td>
                            <td class="py-2 px-4 text-right tabular-nums whitespace-nowrap">{{ $money($overdue) }}</td>
                            <td class="py-2 px-4 text-right tabular-nums whitespace-nowrap">{{ $creditLimit > 0 ? $money($creditLimit) : '—' }}</td>
                            <td class="py-2 px-4 text-slate-700 whitespace-nowrap">{{ $lastPay }}</td>
                            <td class="py-2 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $statusClass }}">
                                    {{ $statusText }}
                                </span>
                            </td>
                            <td class="py-2 px-4 text-right whitespace-nowrap">
                                @php($customer = $c)
                                <a href="{{ route('accounts-v2.customer.statement', $customer) }}"
                                   class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                                    View Statement
                                </a>
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    @if(($customers ?? null) && $customers->hasPages())
        <div class="px-4 py-3 border-t border-slate-200">
            {{ $customers->links() }}
        </div>
    @endif
</div>
</div>
@endsection
