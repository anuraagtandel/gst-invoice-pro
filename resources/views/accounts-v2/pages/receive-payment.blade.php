@extends('layouts.app')

@section('title', 'Receive Payment')

@section('content')
@php
    $money = function ($value) {
        $n = is_numeric($value) ? (float) $value : 0.0;
        $s = number_format($n, 6, '.', '');
        $s = rtrim(rtrim($s, '0'), '.');
        return '₹' . ($s === '' ? '0' : $s);
    };
    $btnPrimary = 'px-3 py-2 text-xs font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700';
    $btnSecondary = 'px-3 py-2 text-xs font-semibold rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 inline-flex items-center';
    $summary = is_array($summary ?? null) ? $summary : ['outstanding' => 0, 'overdue' => 0];
    $defaults = is_array($defaults ?? null) ? $defaults : [];
    $prefillAllocations = is_array($prefillAllocations ?? null) ? $prefillAllocations : [];
    $invoices = $invoices ?? collect();
@endphp

<div class="space-y-4">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-4 py-3">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <div class="text-lg font-syne font-semibold text-slate-900 leading-tight">Receive Payment</div>
                <div class="text-xs text-slate-500 mt-0.5 truncate">{{ $customer->name ?? 'Customer' }}</div>
            </div>
            <a href="{{ route('accounts-v2.customer.statement', $customer) }}" class="{{ $btnSecondary }}">Back to Statement</a>
        </div>
        <div class="mt-2 text-xs text-slate-600">
            <span class="mr-4">Outstanding: <span class="font-semibold text-slate-900 tabular-nums">{{ $money($summary['outstanding'] ?? 0) }}</span></span>
            <span>Overdue: <span class="font-semibold text-slate-900 tabular-nums">{{ $money($summary['overdue'] ?? 0) }}</span></span>
        </div>
    </div>

@if($errors->any())
    <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-xl px-4 py-3 text-sm">
        <div class="font-semibold">Fix the highlighted errors and try again.</div>
        <div class="mt-1 text-xs">{{ $errors->first() }}</div>
    </div>
@endif

<form method="POST" action="{{ route('accounts-v2.customer.receive-payment.store', $customer) }}" class="bg-white rounded-xl border border-slate-200 shadow-sm">
    @csrf

    <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
        <div>
            <div class="text-sm font-semibold text-slate-900">Receive Payment</div>
            <div class="text-xs text-slate-500 mt-1">Server-rendered. Predictable. Allocation is oldest-first.</div>
        </div>
        <div class="flex items-center gap-2">
            <button type="submit" name="intent" value="preview" class="{{ $btnSecondary }}">
                Auto Allocate
            </button>
            <button type="submit" name="intent" value="save" class="{{ $btnPrimary }}">
                Save Payment
            </button>
        </div>
    </div>

    <div class="px-4 py-3">
        <div class="text-sm font-semibold text-slate-900">Payment Details</div>

        <div class="mt-3 grid grid-cols-12 gap-3 items-end">
        <div class="col-span-12 md:col-span-2">
            <label class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wide">Payment Date</label>
            <input type="date" name="payment_date" value="{{ old('payment_date', $defaults['payment_date'] ?? '') }}"
                   class="mt-1 w-full py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
        </div>

        <div class="col-span-12 md:col-span-2">
            <label class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wide">Mode</label>
            @php
                $mode = old('payment_mode', $defaults['payment_mode'] ?? 'Cash');
            @endphp
            <select name="payment_mode" class="mt-1 w-full py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                <option value="Cash" @selected($mode === 'Cash')>Cash</option>
                <option value="UPI" @selected($mode === 'UPI')>UPI</option>
                <option value="Cheque" @selected($mode === 'Cheque')>Cheque</option>
                <option value="Bank" @selected($mode === 'Bank')>Bank</option>
                <option value="Other" @selected($mode === 'Other')>Other</option>
            </select>
        </div>

        <div class="col-span-12 md:col-span-2">
            <label class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wide">Reference No</label>
            <input type="text" name="reference_no" value="{{ old('reference_no', $defaults['reference_no'] ?? '') }}"
                   class="mt-1 w-full py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white px-3">
        </div>

        <div class="col-span-12 md:col-span-4">
            <label class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wide">Notes</label>
            <input type="text" name="notes" value="{{ old('notes', $defaults['notes'] ?? '') }}"
                   class="mt-1 w-full py-2 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white px-3">
        </div>

        <div class="col-span-12 md:col-span-2">
            <label class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wide">Payment Amount</label>
            <input type="number" step="0.000001" min="0" name="amount" value="{{ old('amount', $defaults['amount'] ?? '') }}"
                   class="mt-1 w-full py-2 text-sm border border-slate-200 rounded-lg text-right tabular-nums focus:ring-2 focus:ring-indigo-500 outline-none bg-white px-3">
        </div>
    </div>

    <div class="mt-2 text-xs text-slate-500">
        Tip: Click Auto Allocate to prefill allocations oldest-first. You can still edit allocations before saving.
    </div>
    </div>

    <div class="px-4 py-3 border-t border-slate-200 flex items-center justify-between">
        <div>
            <div class="text-sm font-semibold text-slate-900">Open Invoices</div>
            <div class="text-xs text-slate-500 mt-1">Accounting-safe invoices only. Pending &gt; 0.</div>
        </div>
        <div class="text-xs text-slate-500">
            Rows: {{ $invoices->count() }}
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm tabular-nums">
            <thead>
                <tr class="text-left text-slate-600 border-b border-slate-200">
                    <th class="py-2 px-4 font-semibold">Invoice No</th>
                    <th class="py-2 px-4 font-semibold">Invoice Date</th>
                    <th class="py-2 px-4 font-semibold">Due Date</th>
                    <th class="py-2 px-4 font-semibold text-right">Invoice Total</th>
                    <th class="py-2 px-4 font-semibold text-right">Pending</th>
                    <th class="py-2 px-4 font-semibold text-right">Allocate Amount</th>
                </tr>
            </thead>
            <tbody class="text-slate-800">
                @if($invoices->count() === 0)
                    <tr class="border-t border-slate-100">
                        <td colspan="6" class="py-6 px-4 text-sm text-slate-600">No pending invoices.</td>
                    </tr>
                @else
                    @foreach($invoices as $inv)
                        @php
                            $invId = (int) $inv->id;
                            $pending = (float) ($inv->pending_amount ?? 0);
                            $prefill = old('allocations.' . $invId);
                            if ($prefill === null && array_key_exists($invId, $prefillAllocations)) {
                                $prefill = (string) $prefillAllocations[$invId];
                            }
                            $rowError = $errors->has('allocations') ? 'border-rose-300' : 'border-slate-200';
                        @endphp
                        <tr class="border-t border-slate-100 hover:bg-slate-50">
                            <td class="py-2 px-4 text-slate-900 font-semibold whitespace-nowrap">{{ $inv->invoice_no ?? ('INV#' . $invId) }}</td>
                            <td class="py-2 px-4 text-slate-700 tabular-nums whitespace-nowrap">{{ $inv->invoice_date ? \Illuminate\Support\Carbon::parse($inv->invoice_date)->toDateString() : '-' }}</td>
                            <td class="py-2 px-4 text-slate-700 tabular-nums whitespace-nowrap">{{ $inv->due_date ? \Illuminate\Support\Carbon::parse($inv->due_date)->toDateString() : '-' }}</td>
                            <td class="py-2 px-4 text-right tabular-nums whitespace-nowrap">{{ $money($inv->total_amount) }}</td>
                            <td class="py-2 px-4 text-right tabular-nums whitespace-nowrap">{{ $money($pending) }}</td>
                            <td class="py-2 px-4 text-right whitespace-nowrap">
                                <input type="number" step="0.000001" min="0" name="allocations[{{ $invId }}]" value="{{ $prefill }}"
                                       class="w-40 py-2 rounded-lg border {{ $rowError }} px-3 text-sm text-right tabular-nums focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    <div class="px-4 py-3 border-t border-slate-200 text-xs text-slate-500">
        Validation: allocations cannot exceed invoice pending; total allocated cannot exceed payment amount; invoices must belong to customer.
    </div>

    <div class="px-4 py-3 border-t border-slate-200 bg-white flex items-center justify-end gap-2">
        <a href="{{ route('accounts-v2.customer.statement', $customer) }}" class="{{ $btnSecondary }}">Back</a>
        <button type="submit" name="intent" value="save" class="{{ $btnPrimary }}">Save Payment</button>
    </div>
</form>
</div>
@endsection
