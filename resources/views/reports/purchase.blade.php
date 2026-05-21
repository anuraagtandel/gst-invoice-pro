@extends('layouts.app')

@section('title', 'Purchase Report')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-syne font-semibold text-lg text-slate-800">Purchase Report</h3>
            <p class="text-sm text-slate-500">Filter and analyze purchase data.</p>
        </div>
        
        <form action="{{ route('app.reports.purchase') }}" method="GET" class="flex flex-wrap items-end gap-3 w-full sm:w-auto" data-date-range>
            <div>
                <label class="text-xs font-medium text-slate-500 block mb-1">From Date</label>
                <input type="date" name="from_date" value="{{ request('from_date', now()->toDateString()) }}" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none cursor-pointer">
            </div>
            <div>
                <label class="text-xs font-medium text-slate-500 block mb-1">To Date</label>
                <input type="date" name="to_date" value="{{ request('to_date', now()->toDateString()) }}" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none cursor-pointer">
            </div>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm">
                Filter
            </button>
            <button type="submit" name="export" value="1" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm">
                Export to Excel
            </button>
            <a href="{{ route('app.reports.purchase') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                Clear
            </a>
            <button type="button" onclick="window.print()" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                <i class="ph ph-printer"></i> Print
            </button>
        </form>
    </div>

    @php
        $summaryCount = (int) ($summary['purchase_count'] ?? 0);
        $summaryTotal = (float) ($summary['total_purchase_amount'] ?? 0);
    @endphp

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <p class="text-xs font-medium text-slate-500 mb-1">Purchases Count</p>
            <p class="font-syne font-bold text-xl text-slate-800">{{ $summaryCount }}</p>
        </div>
        <div class="bg-indigo-600 p-4 rounded-xl shadow-sm text-white md:col-span-1">
            <p class="text-xs font-medium text-indigo-100 mb-1">Total Purchase Amount</p>
            @php
                $st = (float) $summaryTotal;
                $st6 = number_format($st, 6, '.', '');
                $stDisp = rtrim(rtrim($st6, '0'), '.');
            @endphp
            <p class="font-syne font-bold text-xl">₹{{ $stDisp }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($purchases->isEmpty())
            @include('partials._empty_state', ['icon' => 'ph-receipt', 'title' => 'No purchase data', 'message' => 'Try adjusting your date filters.'])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Sr No.</th>
                            <th class="px-4 py-3 font-semibold">Supplier Name</th>
                            <th class="px-4 py-3 font-semibold">GST No.</th>
                            <th class="px-4 py-3 font-semibold">Supplier State Code</th>
                            <th class="px-4 py-3 font-semibold">Supplier State</th>
                            <th class="px-4 py-3 font-semibold">Supplier PAN No.</th>
                            <th class="px-4 py-3 font-semibold">Invoice No.</th>
                            <th class="px-4 py-3 font-semibold">Invoice Date</th>
                            <th class="px-4 py-3 font-semibold">HSN Code</th>
                            <th class="px-4 py-3 font-semibold">Product Code</th>
                            <th class="px-4 py-3 font-semibold">Product Description</th>
                            <th class="px-4 py-3 font-semibold text-center">CT</th>
                            <th class="px-4 py-3 font-semibold text-right">Purchase Rate ₹</th>
                            <th class="px-4 py-3 font-semibold text-right">CGST %</th>
                            <th class="px-4 py-3 font-semibold text-right">CGST ₹</th>
                            <th class="px-4 py-3 font-semibold text-right">SGST/UTGST %</th>
                            <th class="px-4 py-3 font-semibold text-right">SGST/UTGST ₹</th>
                            <th class="px-4 py-3 font-semibold text-right">IGST %</th>
                            <th class="px-4 py-3 font-semibold text-right">IGST ₹</th>
                            <th class="px-4 py-3 font-semibold text-right">Net Total Incl. Tax ₹</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($purchases as $row)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-4 py-3 text-slate-500">{{ $loop->iteration + $purchases->firstItem() - 1 }}</td>
                            <td class="px-4 py-3 text-slate-800">{{ $row['supplier_name'] ?: '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $row['supplier_gst'] ?: '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $row['supplier_state_code'] ?: '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $row['supplier_state'] ?: '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $row['supplier_pan'] ?: '-' }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">{{ $row['invoice_no'] ?: '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $row['invoice_date'] ?: '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $row['hsn_code'] ?: '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $row['product_code'] ?: '-' }}</td>
                            <td class="px-4 py-3 text-slate-800">{{ $row['product_description'] ?: '-' }}</td>
                            <td class="px-4 py-3 text-center text-slate-700">{{ (int) $row['ct'] }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ number_format((float) $row['purchase_rate'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ number_format((float) $row['cgst_pct'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ number_format((float) $row['cgst_amt'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ $row['sgst_pct'] === null ? '-' : number_format((float) $row['sgst_pct'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ $row['sgst_amt'] === null ? '-' : number_format((float) $row['sgst_amt'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ $row['igst_pct'] === null ? '-' : number_format((float) $row['igst_pct'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ $row['igst_amt'] === null ? '-' : number_format((float) $row['igst_amt'], 2) }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ number_format((float) $row['net_total'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 border-t border-slate-200">
                        <tr>
                            <td class="px-4 py-3"></td>
                            <td class="px-4 py-3 font-semibold text-slate-900">Total</td>
                            <td class="px-4 py-3"></td>
                            <td class="px-4 py-3"></td>
                            <td class="px-4 py-3"></td>
                            <td class="px-4 py-3"></td>
                            <td class="px-4 py-3"></td>
                            <td class="px-4 py-3"></td>
                            <td class="px-4 py-3"></td>
                            <td class="px-4 py-3"></td>
                            <td class="px-4 py-3"></td>
                            <td class="px-4 py-3"></td>
                            <td class="px-4 py-3"></td>
                            <td class="px-4 py-3"></td>
                            <td class="px-4 py-3"></td>
                            <td class="px-4 py-3"></td>
                            <td class="px-4 py-3"></td>
                            <td class="px-4 py-3"></td>
                            <td class="px-4 py-3"></td>
                            <td class="px-4 py-3 text-right font-bold text-slate-900">{{ number_format((float) ($totals['total_net_purchase_amount'] ?? 0), 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            </div>
            <div class="p-4 border-t border-slate-200">
                {{ $purchases->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        for (const form of document.querySelectorAll('form[data-date-range]')) {
            const from = form.querySelector('input[name="from_date"]');
            const to = form.querySelector('input[name="to_date"]');
            if (!from || !to) continue;

            const sync = () => {
                if (from.value) {
                    to.min = from.value;
                    if (to.value && to.value < from.value) {
                        to.value = from.value;
                    }
                } else {
                    to.min = '';
                }
            };

            from.addEventListener('change', sync);
            from.addEventListener('input', sync);
            sync();

            const openPicker = (el) => {
                if (!el) return;
                try { el.focus(); } catch (e) { }
                if (typeof el.showPicker === 'function') {
                    try { el.showPicker(); } catch (e) { }
                }
            };
            from.addEventListener('click', () => openPicker(from));
            from.addEventListener('focus', () => openPicker(from));
            to.addEventListener('click', () => openPicker(to));
            to.addEventListener('focus', () => openPicker(to));
        }
    });
</script>
@endpush
