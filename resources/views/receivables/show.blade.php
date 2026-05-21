@extends('layouts.app')

@section('title', 'Receivables Details')

@section('content')
@php
    $totalInvoice = (float) ($summary->total_invoice_amount ?? 0);
    $totalPaid = (float) ($summary->total_paid ?? 0);
    $totalPending = (float) ($summary->total_pending ?? 0);

    $ti6 = number_format($totalInvoice, 6, '.', '');
    $tp6 = number_format($totalPaid, 6, '.', '');
    $tpend6 = number_format($totalPending, 6, '.', '');

    $tiDisp = rtrim(rtrim($ti6, '0'), '.');
    $tpDisp = rtrim(rtrim($tp6, '0'), '.');
    $tpendDisp = rtrim(rtrim($tpend6, '0'), '.');
@endphp

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-syne font-semibold text-lg text-slate-800">{{ $customer->name }}</h3>
            <p class="text-sm text-slate-500">Receivables details</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
            <a href="{{ route('app.payments.receive', ['customer_id' => $customer->id]) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors flex items-center gap-2 shadow-sm">
                <i class="ph ph-hand-coins text-lg"></i> Receive Payment
            </a>
            <a href="{{ route('app.receivables.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                <i class="ph ph-arrow-left text-lg"></i> Back
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Total Invoice Amount</p>
            <p class="font-syne font-bold text-2xl text-slate-900">₹{{ $tiDisp }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Total Paid</p>
            <p class="font-syne font-bold text-2xl text-emerald-700">₹{{ $tpDisp }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Total Pending</p>
            <p class="font-syne font-bold text-2xl {{ $totalPending > 0 ? 'text-amber-800' : 'text-slate-900' }}">₹{{ $tpendDisp }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($invoices->isEmpty())
            @include('partials._empty_state', ['icon' => 'ph-receipt', 'title' => 'No invoices found', 'message' => 'No invoices available for this customer.'])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Invoice No</th>
                            <th class="px-6 py-3 font-semibold">Date</th>
                            <th class="px-6 py-3 font-semibold">Status</th>
                            <th class="px-6 py-3 font-semibold">Due Date</th>
                            <th class="px-6 py-3 font-semibold text-right">Total</th>
                            <th class="px-6 py-3 font-semibold text-right">Paid</th>
                            <th class="px-6 py-3 font-semibold text-right">Pending</th>
                            <th class="px-6 py-3 font-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($invoices as $inv)
                            @php
                                $status = $inv->payment_status ?? 'Unpaid';
                                $pending = (float) ($inv->pending_amount ?? 0);

                                $today = now()->startOfDay();
                                $due = $inv->due_date ? $inv->due_date->copy()->startOfDay() : null;
                                $isOverdue = $due ? $due->lt($today) : false;
                                $isNearDue = $due ? (!$isOverdue && $due->lte($today->copy()->addDays(3))) : false;
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition-colors {{ $isOverdue ? 'bg-red-50' : ($isNearDue ? 'bg-amber-50' : '') }}">
                                <td class="px-6 py-4 font-medium text-indigo-600">{{ $inv->invoice_no }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ optional($inv->invoice_date)->format('d-m-Y') }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-[10px] font-bold rounded uppercase tracking-wider {{ $status === 'Paid' ? 'bg-emerald-50 text-emerald-700' : ($status === 'Partial' ? 'bg-indigo-50 text-indigo-700' : 'bg-amber-50 text-amber-800') }}">
                                        {{ $status }}
                                    </span>
                                </td>
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
                                <td class="px-6 py-4 text-right font-semibold text-slate-900">₹{{ rtrim(rtrim(number_format((float) ($inv->total_amount ?? $inv->grand_total ?? 0), 6, '.', ''), '0'), '.') }}</td>
                                <td class="px-6 py-4 text-right text-slate-700">₹{{ rtrim(rtrim(number_format((float) ($inv->paid_amount ?? 0), 6, '.', ''), '0'), '.') }}</td>
                                <td class="px-6 py-4 text-right font-bold {{ $isOverdue ? 'text-red-700' : ($isNearDue ? 'text-amber-800' : ($pending > 0 ? 'text-slate-900' : 'text-slate-900')) }}">₹{{ rtrim(rtrim(number_format($pending, 6, '.', ''), '0'), '.') }}</td>
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('app.invoices.edit', $inv->id) }}" class="text-slate-600 hover:text-slate-800 font-semibold">Edit</a>
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
