@extends('layouts.app')

@section('title', 'Sales Report')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-syne font-semibold text-lg text-slate-800">Sales Report</h3>
            <p class="text-sm text-slate-500">Filter and analyze sales data.</p>
        </div>
        
        <form id="salesReportFilters" action="{{ route('app.reports.sales') }}" method="GET" class="flex flex-wrap items-end gap-3 w-full sm:w-auto" data-date-range>
            <div>
                <label class="text-xs font-medium text-slate-500 block mb-1">From Date</label>
                <input type="date" name="from_date" value="{{ request('from_date', now()->toDateString()) }}" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none cursor-pointer">
            </div>
            <div>
                <label class="text-xs font-medium text-slate-500 block mb-1">To Date</label>
                <input type="date" name="to_date" value="{{ request('to_date', now()->toDateString()) }}" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none cursor-pointer">
            </div>
            <div>
                <label class="text-xs font-medium text-slate-500 block mb-1">Salesman</label>
                <select name="salesman" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white min-w-[180px]">
                    <option value="" @selected(($salesmanFilter ?? '') === '')>All</option>
                    <option value="__NONE__" @selected(($salesmanFilter ?? '') === '__NONE__')>Unassigned</option>
                    @foreach($salesmen as $s)
                        <option value="{{ $s->name }}" @selected(($salesmanFilter ?? '') === $s->name)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm">
                Filter
            </button>
            <a href="{{ route('app.reports.sales') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                Clear
            </a>
        </form>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <p class="text-xs font-medium text-slate-500 mb-1">Invoices Count</p>
            <p class="font-syne font-bold text-xl text-slate-800">{{ $summary['count'] }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <p class="text-xs font-medium text-slate-500 mb-1">Total Taxable</p>
            <p class="font-syne font-bold text-xl text-slate-800">₹{{ number_format($summary['taxable'], 2) }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <p class="text-xs font-medium text-slate-500 mb-1">Total CGST</p>
            <p class="font-syne font-bold text-xl text-slate-800">₹{{ number_format($summary['cgst'], 2) }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <p class="text-xs font-medium text-slate-500 mb-1">Total UTGST</p>
            <p class="font-syne font-bold text-xl text-slate-800">₹{{ number_format($summary['sgst'], 2) }}</p>
        </div>
        <div class="bg-indigo-600 p-4 rounded-xl shadow-sm text-white">
            <p class="text-xs font-medium text-indigo-100 mb-1">Total Revenue</p>
            <p class="font-syne font-bold text-xl">₹{{ number_format($summary['grand_total'], 2) }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200">
            <h4 class="font-syne font-semibold text-slate-800">Sales by Salesman</h4>
        </div>
        @if(($salesBySalesman ?? collect())->isEmpty())
            <div class="p-6 text-slate-500 text-sm">No data.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Salesman</th>
                            <th class="px-6 py-3 font-semibold text-right">Invoice Count</th>
                            <th class="px-6 py-3 font-semibold text-right">Total Sales</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($salesBySalesman as $r)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-3 font-medium text-slate-800">{{ $r->salesman_name }}</td>
                                <td class="px-6 py-3 text-right text-slate-600">{{ number_format((int) $r->invoice_count) }}</td>
                                <td class="px-6 py-3 text-right font-semibold text-slate-900">₹{{ number_format((float) $r->total_sales, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-end gap-3">
            <div class="flex-1">
                <label class="text-xs font-medium text-slate-500 block mb-1">Customer Name</label>
                <input id="customerNameFilter" type="text" placeholder="Type to search customer..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($invoices->isEmpty())
            @include('partials._empty_state', ['icon' => 'ph-chart-line-up', 'title' => 'No sales data', 'message' => 'Try adjusting your date filters.'])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold">#</th>
                            <th class="px-6 py-3 font-semibold">Invoice No</th>
                            <th class="px-6 py-3 font-semibold">Date</th>
                            <th class="px-6 py-3 font-semibold">Customer Name</th>
                            <th class="px-6 py-3 font-semibold">Salesman</th>
                            <th class="px-6 py-3 font-semibold">GSTIN</th>
                            <th class="px-6 py-3 font-semibold text-right">Taxable</th>
                            <th class="px-6 py-3 font-semibold text-right">CGST</th>
                            <th class="px-6 py-3 font-semibold text-right">UTGST/IGST</th>
                            <th class="px-6 py-3 font-semibold text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" data-invoice-body>
                        @foreach($invoices as $invoice)
                        <tr class="hover:bg-slate-50/50 transition-colors" data-row data-customer="{{ strtolower((string) ($invoice->customer->business_name ?? '-')) }}" data-original-index="{{ $loop->iteration }}">
                            <td class="px-6 py-3 text-slate-500">{{ $loop->iteration + $invoices->firstItem() - 1 }}</td>
                            <td class="px-6 py-3 font-medium text-indigo-600"><a href="{{ route('app.invoices.print', $invoice->id) }}" target="_blank">{{ $invoice->invoice_no }}</a></td>
                            <td class="px-6 py-3 text-slate-600">{{ $invoice->invoice_date->format('d-m-Y') }}</td>
                            <td class="px-6 py-3 text-slate-800">{{ $invoice->customer->business_name ?? '-' }}</td>
                            <td class="px-6 py-3 text-slate-700">{{ $invoice->salesman ?: '-' }}</td>
                            <td class="px-6 py-3 text-slate-600">{{ $invoice->customer->gstin ?? 'URP' }}</td>
                            <td class="px-6 py-3 text-right text-slate-600">{{ number_format($invoice->taxable_amount, 2) }}</td>
                            <td class="px-6 py-3 text-right text-slate-600">{{ number_format($invoice->total_cgst, 2) }}</td>
                            <td class="px-6 py-3 text-right text-slate-600">{{ number_format($invoice->total_sgst, 2) }}</td>
                            <td class="px-6 py-3 text-right font-medium text-slate-800">{{ number_format($invoice->grand_total, 2) }}</td>
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
                if (typeof el.showPicker === 'function') {
                    try { el.showPicker(); } catch (e) { }
                }
            };
            from.addEventListener('click', () => openPicker(from));
            from.addEventListener('focus', () => openPicker(from));
            to.addEventListener('click', () => openPicker(to));
            to.addEventListener('focus', () => openPicker(to));
        }

        const filterInput = document.getElementById('customerNameFilter');
        if (filterInput) {
            const tbody = document.querySelector('tbody[data-invoice-body]');
            const allRows = tbody ? Array.from(tbody.querySelectorAll('tr[data-row]')) : [];

            const restoreOrder = () => {
                if (!tbody) return;
                const sorted = allRows
                    .slice()
                    .sort((a, b) => (Number(a.dataset.originalIndex) || 0) - (Number(b.dataset.originalIndex) || 0));
                for (const r of sorted) {
                    r.style.display = '';
                    tbody.appendChild(r);
                }
            };

            const applyLocalFilter = () => {
                const q = (filterInput.value || '').trim().toLowerCase();
                if (q === '') {
                    restoreOrder();
                    return;
                }

                const matches = [];
                const nonMatches = [];
                for (const r of allRows) {
                    const name = (r.dataset.customer || '');
                    if (name.includes(q)) {
                        matches.push(r);
                    } else {
                        nonMatches.push(r);
                    }
                }
                if (tbody) {
                    for (const r of matches) {
                        r.style.display = '';
                        tbody.appendChild(r);
                    }
                    for (const r of nonMatches) {
                        r.style.display = 'none';
                        tbody.appendChild(r);
                    }
                }
            };

            filterInput.addEventListener('input', applyLocalFilter);

            applyLocalFilter();
        }
    });
</script>
@endpush
