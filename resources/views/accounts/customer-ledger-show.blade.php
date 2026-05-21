@extends('layouts.app')

@section('title', 'Customer Ledger')

@section('content')
@php
    $fmt = function ($n) {
        $v6 = number_format((float) $n, 6, '.', '');
        return rtrim(rtrim($v6, '0'), '.');
    };
@endphp

<div class="space-y-6">
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-syne font-semibold text-lg text-slate-800">{{ $customer->name }}</h3>
            <p class="text-sm text-slate-500">Customer ledger (receivables)</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
            <a href="{{ route('app.payments.receive', ['customer_id' => $customer->id]) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors flex items-center gap-2 shadow-sm">
                <i class="ph ph-hand-coins text-lg"></i> Receive Payment
            </a>
            <a href="{{ route('app.receivables.show', $customer->id) }}" class="bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 px-4 py-2 rounded-lg text-sm font-semibold transition-colors flex items-center gap-2">
                <i class="ph ph-wallet text-lg"></i> Receivables
            </a>
            <a href="{{ route('app.accounts.customer_ledger') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-lg text-sm font-semibold transition-colors flex items-center gap-2">
                <i class="ph ph-arrow-left text-lg"></i> Back
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Outstanding</p>
            <p class="font-syne font-bold text-2xl text-amber-800">₹{{ $fmt($outstanding) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Credit Limit</p>
            <p class="font-syne font-bold text-2xl text-slate-900">
                @if($customer->credit_limit !== null && (float) $customer->credit_limit > 0)
                    ₹{{ $fmt($customer->credit_limit) }}
                @else
                    -
                @endif
            </p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Balance Status</p>
            @php
                $limit = $customer->credit_limit === null ? null : (float) $customer->credit_limit;
                $isExceeded = $limit !== null && $limit > 0 && (float) $outstanding > ($limit + 0.0000001);
            @endphp
            <p class="font-syne font-bold text-2xl {{ $isExceeded ? 'text-red-700' : 'text-emerald-700' }}">
                {{ $isExceeded ? 'Exceeded' : 'OK' }}
            </p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <form method="GET" action="{{ route('app.accounts.customer_ledger.show', $customer->id) }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">From</label>
                <input type="date" name="from_date" value="{{ $fromDate }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">To</label>
                <input type="date" name="to_date" value="{{ $toDate }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-sm">Apply</button>
                <a href="{{ route('app.accounts.customer_ledger.show', $customer->id) }}" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold">Reset</a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if(empty($entries))
            @include('partials._empty_state', ['icon' => 'ph-book-open-text', 'title' => 'No ledger entries', 'message' => 'No credit sales or collections in selected period.'])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Date</th>
                            <th class="px-6 py-3 font-semibold">Type</th>
                            <th class="px-6 py-3 font-semibold">Reference</th>
                            <th class="px-6 py-3 font-semibold text-right">Debit</th>
                            <th class="px-6 py-3 font-semibold text-right">Credit</th>
                            <th class="px-6 py-3 font-semibold text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($entries as $e)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 text-slate-600">{{ \Carbon\Carbon::parse($e['date'])->format('d-m-Y') }}</td>
                                <td class="px-6 py-4 font-semibold text-slate-800">{{ $e['type'] }}</td>
                                <td class="px-6 py-4 text-slate-700">{{ $e['ref'] }}</td>
                                <td class="px-6 py-4 text-right text-slate-700">{{ $e['debit'] > 0 ? ('₹' . $fmt($e['debit'])) : '-' }}</td>
                                <td class="px-6 py-4 text-right text-slate-700">{{ $e['credit'] > 0 ? ('₹' . $fmt($e['credit'])) : '-' }}</td>
                                <td class="px-6 py-4 text-right font-bold text-slate-900">₹{{ $fmt($e['balance']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

