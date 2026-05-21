@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-syne font-semibold text-lg text-slate-800">{{ $product->name }}</h3>
            <p class="text-sm text-slate-500">Inventory Details</p>
        </div>
        <a href="{{ route('app.inventory.index') }}" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-2 shadow-sm">
            <i class="ph ph-arrow-left text-lg"></i> Back to Inventory
        </a>
    </div>

    <!-- Product Details -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="md:col-span-1 space-y-4">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <h4 class="font-semibold text-slate-800 mb-2">Product Info</h4>
                <p><span class="font-medium">Pack Size:</span> {{ $product->pack_size }} units/ct</p>
                <p><span class="font-medium">Opening Stock:</span> 
                    @php
                        $openingInUnits = $product->opening_stock_units;
                        $packSize = $product->pack_size > 0 ? $product->pack_size : 1;
                        $cartons = floor($openingInUnits / $packSize);
                        $units = $openingInUnits % $packSize;
                    @endphp
                    {{ $cartons }} CT & {{ $units }} UN
                </p>
            </div>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <h4 class="font-semibold text-slate-800 mb-2">Current Stock</h4>
                <p class="text-2xl font-bold text-indigo-600">
                    @php
                        $stockInUnits = $product->current_stock_units;
                        $packSize = $product->pack_size > 0 ? $product->pack_size : 1;
                        $cartons = floor($stockInUnits / $packSize);
                        $units = $stockInUnits % $packSize;
                    @endphp
                    {{ $cartons }} CT & {{ $units }} UN
                </p>
            </div>
        </div>
        <div class="md:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200">
            <div class="p-5 border-b border-slate-200">
                <h4 class="font-semibold text-slate-800">Transaction History</h4>
            </div>
            <div class="p-0">
                @if($product->stockTransactions->isEmpty())
                    @include('partials._empty_state', [
                        'icon' => 'ph-arrows-counter-clockwise',
                        'title' => 'No transactions yet',
                        'message' => 'Stock movements will appear here.'
                    ])
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                                <tr>
                                    <th class="px-6 py-3 font-semibold">Date</th>
                                    <th class="px-6 py-3 font-semibold">Type</th>
                                    <th class="px-6 py-3 font-semibold">Quantity</th>
                                    <th class="px-6 py-3 font-semibold">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($product->stockTransactions()->latest('transaction_date')->get() as $transaction)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 text-slate-600">{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d M, Y') }}</td>
                                    <td class="px-6 py-4 font-medium">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold capitalize"
                                            @class([
                                                'bg-emerald-100 text-emerald-700' => $transaction->type === 'purchase',
                                                'bg-red-100 text-red-700' => $transaction->type === 'sale',
                                                'bg-amber-100 text-amber-700' => $transaction->type === 'adjustment',
                                            ])>
                                            {{ $transaction->type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-medium text-slate-800">
                                        @php
                                            $transUnits = $transaction->quantity_in_units;
                                            $packSize = $product->pack_size > 0 ? $product->pack_size : 1;
                                            $cartons = floor($transUnits / $packSize);
                                            $units = $transUnits % $packSize;
                                        @endphp
                                        {{ $cartons }} CT & {{ $units }} UN
                                    </td>
                                    <td class="px-6 py-4 text-slate-600">{{ $transaction->notes }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection
