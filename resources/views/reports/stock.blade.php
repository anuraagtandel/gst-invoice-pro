@extends('layouts.app')

@section('title', 'Stock Report')

@section('content')
@php
    $formatIndian = function ($value, int $decimals = 0): string {
        $n = (float) $value;
        $neg = $n < 0 ? '-' : '';
        $n = abs($n);
        $base = number_format($n, $decimals, '.', '');
        [$intPart, $decPart] = array_pad(explode('.', $base, 2), 2, null);
        if (strlen($intPart) > 3) {
            $last3 = substr($intPart, -3);
            $rest = substr($intPart, 0, -3);
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $intPart = $rest . ',' . $last3;
        }
        return $neg . $intPart . ($decimals > 0 ? '.' . $decPart : '');
    };
    $totalUnitsAll = 0;
    $totalAmountAll = 0.0;
@endphp
<div class="space-y-6">

    <div class="flex justify-between items-center bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-syne font-semibold text-lg text-slate-800">Current Stock Report</h3>
            <p class="text-sm text-slate-500">Live inventory levels computed from opening stock, purchases, and sales.</p>
        </div>
        <a href="{{ route('app.stock-report.export') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
            <i class="ph ph-file-xls text-lg"></i> Export to Excel
        </a>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($products->isEmpty())
            @include('partials._empty_state', ['icon' => 'ph-warehouse', 'title' => 'No active products', 'message' => 'Add products to view stock report.'])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Sr No.</th>
                            <th class="px-6 py-3 font-semibold">Product Name</th>
                            <th class="px-6 py-3 font-semibold bg-slate-100/50">Product Code</th>
                            <th class="px-6 py-3 font-semibold text-right text-indigo-600 bg-indigo-50/30">Current Stock (CT/UN)</th>
                            <th class="px-6 py-3 font-semibold text-right">Total Units</th>
                            <th class="px-6 py-3 font-semibold text-right">Total Amount ₹</th>
                            <th class="px-6 py-3 font-semibold text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($products as $product)
                        @php
                            $totalUnits = $product->current_stock_units;
                            $stockCt = floor($totalUnits / $product->pack_size);
                            $stockUn = $totalUnits % $product->pack_size;
                            $lineAmount = (float) $totalUnits * (float) ($product->trade_price ?? 0);
                            $totalUnitsAll += (int) $totalUnits;
                            $totalAmountAll += $lineAmount;
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 text-slate-500">{{ $loop->iteration }}</td>
                            <td class="px-6 py-4 font-medium text-slate-800">{{ $product->name }} {{ $product->volume }}</td>
                            <td class="px-6 py-4 text-slate-600 bg-slate-50/30">{{ $product->product_code ?? '-' }}</td>
                            <td class="px-6 py-4 text-right font-bold text-indigo-700 bg-indigo-50/10">{{ $stockCt }} / {{ $stockUn }}</td>
                            <td class="px-6 py-4 text-right text-slate-600 font-medium">{{ $formatIndian($totalUnits, 0) }}</td>
                            <td class="px-6 py-4 text-right text-slate-900 font-semibold">₹{{ $formatIndian($lineAmount, 0) }}</td>
                            <td class="px-6 py-4 text-center">
                                @if($totalUnits <= 0)
                                    <span class="px-2 py-1 bg-red-100 text-red-700 text-[10px] font-bold rounded uppercase tracking-wider">Out of Stock</span>
                                @elseif($stockCt < 5)
                                    <span class="px-2 py-1 bg-amber-100 text-amber-700 text-[10px] font-bold rounded uppercase tracking-wider">Low Stock</span>
                                @else
                                    <span class="px-2 py-1 bg-emerald-100 text-emerald-700 text-[10px] font-bold rounded uppercase tracking-wider">In Stock</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 border-t border-slate-200">
                        <tr>
                            <td class="px-6 py-3"></td>
                            <td class="px-6 py-3 font-bold text-slate-900">Total</td>
                            <td class="px-6 py-3"></td>
                            <td class="px-6 py-3"></td>
                            <td class="px-6 py-3 text-right font-bold text-slate-900">{{ $formatIndian($totalUnitsAll, 0) }}</td>
                            <td class="px-6 py-3 text-right font-bold text-slate-900">₹{{ $formatIndian($totalAmountAll, 0) }}</td>
                            <td class="px-6 py-3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection
