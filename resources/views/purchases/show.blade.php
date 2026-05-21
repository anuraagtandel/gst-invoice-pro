@extends('layouts.app')

@section('title', 'View Purchase')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div class="w-full sm:flex-1">
            <h3 class="font-syne font-semibold text-lg text-slate-800">View Purchase</h3>
        </div>
        <a href="{{ route('app.purchases.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
            <i class="ph ph-arrow-left"></i> Back
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 lg:col-span-2">
            <h4 class="font-syne font-semibold text-slate-800 mb-4">Supplier</h4>
            @if($purchase->supplier_ref)
                <div class="space-y-1">
                    <div class="text-slate-800 font-semibold">{{ $purchase->supplier_ref->name }}</div>
                    @if($purchase->supplier_ref->gstin)
                        <div class="text-sm text-slate-600">GSTIN: {{ strtoupper($purchase->supplier_ref->gstin) }}</div>
                    @endif
                    @if($purchase->supplier_ref->mobile)
                        <div class="text-sm text-slate-600">Mobile: {{ $purchase->supplier_ref->mobile }}</div>
                    @endif
                    @if($purchase->supplier_ref->address)
                        <div class="text-sm text-slate-600">{{ $purchase->supplier_ref->address }}</div>
                    @endif
                </div>
            @else
                <div class="text-slate-800 font-semibold">{{ $purchase->supplier ?? '-' }}</div>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
            <h4 class="font-syne font-semibold text-slate-800 mb-4">Summary</h4>
            <div class="space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-500">Vehicle Number</span>
                    <span class="font-semibold text-slate-800">{{ $purchase->vehicle_number ?: '-' }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-500">Items</span>
                    <span class="font-semibold text-slate-800">{{ $purchase->purchaseItems->count() }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-500">Total Amount</span>
                    <span class="font-semibold text-slate-800">₹{{ number_format((float) $purchase->purchaseItems->sum('item_total'), 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <h4 class="font-syne font-semibold text-slate-800 mb-4">Purchase Details</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Bill No</div>
                <div class="mt-1 text-sm sm:text-base font-semibold text-slate-800">{{ $purchase->bill_no }}</div>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Bill Date</div>
                <div class="mt-1 text-sm sm:text-base font-semibold text-slate-800">{{ optional($purchase->bill_date)->format('d-m-Y') }}</div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200">
            <h4 class="font-syne font-semibold text-slate-800">Items</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3 font-semibold">#</th>
                        <th class="px-6 py-3 font-semibold">Product</th>
                        <th class="px-6 py-3 font-semibold text-right">Pack Size</th>
                        <th class="px-6 py-3 font-semibold text-right">Qty (CT)</th>
                        <th class="px-6 py-3 font-semibold text-right">Units</th>
                        <th class="px-6 py-3 font-semibold text-right">Rate (CT)</th>
                        <th class="px-6 py-3 font-semibold text-right">Line Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @php $computedTotal = 0; @endphp
                    @foreach($purchase->purchaseItems as $idx => $item)
                        @php
                            $packSize = (int) ($item->product?->pack_size ?? 0);
                            $qtyCt = (float) ($item->qty_ct ?? 0);
                            $units = (float) ($item->total_units ?? 0);
                            $rate = (float) ($item->purchase_rate ?? 0);
                            $lineTotal = (float) ($item->item_total ?? 0);
                            $computedTotal += $lineTotal;
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-3 text-slate-500">{{ $idx + 1 }}</td>
                            <td class="px-6 py-3 font-medium text-slate-800">{{ $item->product?->name }} {{ $item->product?->volume }}</td>
                            <td class="px-6 py-3 text-right text-slate-600">{{ $packSize }}</td>
                            <td class="px-6 py-3 text-right text-slate-600">{{ number_format($qtyCt, 2) }}</td>
                            <td class="px-6 py-3 text-right text-slate-600">{{ number_format((float) $units, 2) }}</td>
                            <td class="px-6 py-3 text-right text-slate-600">₹{{ number_format($rate, 2) }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-slate-800">₹{{ number_format($lineTotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50 border-t border-slate-200">
                    <tr>
                        <td colspan="6" class="px-6 py-3 text-right font-semibold text-slate-700">Total</td>
                        <td class="px-6 py-3 text-right font-bold text-slate-800">₹{{ number_format($computedTotal, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
