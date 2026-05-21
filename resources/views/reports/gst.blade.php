@extends('layouts.app')

@section('title', 'GST Report (B2B Only)')

@section('content')
<div class="space-y-6" x-data="gstReport()">

    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-syne font-semibold text-lg text-slate-800">GST Report</h3>
            <p class="text-sm text-slate-500">B2B Invoices Only</p>
        </div>
        
        <div class="flex flex-wrap gap-2 w-full lg:w-auto">
            <button type="button" @click="downloadSelectedJson" :disabled="selected.length === 0" class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm flex items-center gap-2">
                <i class="ph ph-download-simple"></i> Download Selected JSON
            </button>
            <form action="{{ route('app.reports.gst') }}" method="GET" class="inline">
                @foreach(request()->except('export', 'page') as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <input type="hidden" name="export" value="1">
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                    <i class="ph ph-file-xls"></i> Export Excel
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
        <form action="{{ route('app.reports.gst') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 items-end" data-date-range data-server-has-date="{{ (request()->filled('from_date') || request()->filled('to_date')) ? '1' : '0' }}">
            <div>
                <label class="text-xs font-medium text-slate-500 block mb-1">Date</label>
                <select id="gstDatePreset" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                    <option value="custom">Custom</option>
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-medium text-slate-500 block mb-1">From Date</label>
                <input id="gstFromDate" type="date" name="from_date" value="{{ request('from_date', now()->toDateString()) }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none gst-custom-date cursor-pointer">
            </div>
            <div>
                <label class="text-xs font-medium text-slate-500 block mb-1">To Date</label>
                <input id="gstToDate" type="date" name="to_date" value="{{ request('to_date', now()->toDateString()) }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none gst-custom-date cursor-pointer">
            </div>
            <div>
                <label class="text-xs font-medium text-slate-500 block mb-1">Customer Name</label>
                <input id="gstCustomerNameFilter" type="text" name="customer_name" value="{{ request('customer_name') }}" placeholder="Search..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="text-xs font-medium text-slate-500 block mb-1">GSTIN</label>
                <input id="gstGstinFilter" type="text" name="gstin" value="{{ request('gstin') }}" placeholder="Search..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="text-xs font-medium text-slate-500 block mb-1">Invoice No.</label>
                <input id="gstInvoiceNoFilter" type="text" name="invoice_no" value="{{ request('invoice_no') }}" placeholder="Search..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="text-xs font-medium text-slate-500 block mb-1">JSON Status</label>
                <select id="gstJsonStatusFilter" name="json_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                    <option value="">All</option>
                    <option value="Not Generated" {{ request('json_status') === 'Not Generated' ? 'selected' : '' }}>Not Generated</option>
                    <option value="Generated" {{ request('json_status') === 'Generated' ? 'selected' : '' }}>Generated</option>
                    <option value="Downloaded" {{ request('json_status') === 'Downloaded' ? 'selected' : '' }}>Downloaded</option>
                </select>
            </div>
            <div class="lg:col-span-6 flex justify-end gap-2 mt-2">
                <a href="{{ route('app.reports.gst') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2 rounded-lg text-sm font-medium transition-colors">Clear</a>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm">Filter</button>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-center">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Invoices</span>
            <span class="text-xl font-bold text-slate-800">{{ number_format($summary['count']) }}</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-center">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Taxable</span>
            <span class="text-xl font-bold text-slate-800">₹{{ number_format($summary['taxable'], 2) }}</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-center">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">CGST</span>
            <span class="text-xl font-bold text-slate-800">₹{{ number_format($summary['cgst'], 2) }}</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-center">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">SGST/UTGST</span>
            <span class="text-xl font-bold text-slate-800">₹{{ number_format($summary['sgst'], 2) }}</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-center">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">IGST</span>
            <span class="text-xl font-bold text-slate-800">₹0.00</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-indigo-200 shadow-sm flex flex-col justify-center bg-indigo-50/30">
            <span class="text-xs font-semibold text-indigo-600 uppercase tracking-wider mb-1">Grand Total</span>
            <span class="text-xl font-bold text-indigo-700">₹{{ number_format($summary['grand_total'], 2) }}</span>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($invoices->isEmpty())
            @include('partials._empty_state', ['icon' => 'ph-file-text', 'title' => 'No B2B invoices found', 'message' => 'No B2B sales data found for the selected period and filters.'])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 font-semibold text-center w-12">
                                <input type="checkbox" @change="toggleAll" :checked="allSelected" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            </th>
                            <th class="px-4 py-3 font-semibold">Sr No.</th>
                            <th class="px-4 py-3 font-semibold">Date</th>
                            <th class="px-4 py-3 font-semibold">Invoice No.</th>
                            <th class="px-4 py-3 font-semibold">Customer Name</th>
                            <th class="px-4 py-3 font-semibold">GSTIN No.</th>
                            <th class="px-4 py-3 font-semibold">State Code</th>
                            <th class="px-4 py-3 font-semibold">State</th>
                            <th class="px-4 py-3 font-semibold text-right">Taxable Amount</th>
                            <th class="px-4 py-3 font-semibold text-right">CGST</th>
                            <th class="px-4 py-3 font-semibold text-right">SGST/UTGST</th>
                            <th class="px-4 py-3 font-semibold text-right">IGST</th>
                            <th class="px-4 py-3 font-semibold text-right">Final Value</th>
                            <th class="px-4 py-3 font-semibold text-center">JSON Status</th>
                            <th class="px-4 py-3 font-semibold text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" data-gst-body>
                        @foreach($invoices as $idx => $invoice)
                        @php
                            $customer = $invoice->customer;
                            $customerStateCode = $customer ? preg_replace('/\D/', '', $customer->pos_code ?: $customer->state_code) : '';
                            $sameState = $customerStateCode === '' || $firmStateCode === '' || $customerStateCode === $firmStateCode;
                            
                            $sgst = $sameState ? $invoice->total_sgst : 0;
                            $igst = $sameState ? 0 : $invoice->total_sgst;
                            $customerBusinessName = $customer ? (string) ($customer->business_name ?? '') : '';
                            $rowStatus = (string) ($invoice->einvoice_status ?: 'Not Generated');
                        @endphp
                        <tr
                            class="hover:bg-slate-50/50 transition-colors"
                            data-row
                            data-original-index="{{ $idx }}"
                            data-date="{{ $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : '' }}"
                            data-customer="{{ strtolower($customerBusinessName) }}"
                            data-gstin="{{ strtolower((string) ($customer?->gstin ?? '')) }}"
                            data-invoice="{{ strtolower((string) ($invoice->invoice_no ?? '')) }}"
                            data-status="{{ strtolower($rowStatus) }}"
                        >
                            <td class="px-4 py-3 text-center">
                                <input type="checkbox" value="{{ $invoice->id }}" x-model="selected" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 row-checkbox">
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ $invoices->firstItem() + $idx }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $invoice->invoice_date ? $invoice->invoice_date->format('d-m-Y') : '-' }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">
                                <button
                                    type="button"
                                    class="text-left hover:underline hover:decoration-indigo-400 hover:text-indigo-700 transition-colors"
                                    @click="openInvoiceDetails('{{ route('app.invoices.details_json', $invoice->id) }}')"
                                >
                                    {{ $invoice->invoice_no }}
                                </button>
                            </td>
                            <td class="px-4 py-3 text-slate-800">{{ $customer ? $customer->name : '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $customer ? $customer->gstin : '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $customer ? $customer->state_code : '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $customer ? $customer->state : '-' }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">₹{{ number_format($invoice->taxable_amount, 2) }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">₹{{ number_format($invoice->total_cgst, 2) }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ $sgst > 0 ? '₹'.number_format($sgst, 2) : '-' }}</td>
                            <td class="px-4 py-3 text-right text-slate-700">{{ $igst > 0 ? '₹'.number_format($igst, 2) : '-' }}</td>
                            <td class="px-4 py-3 text-right font-bold text-slate-800">₹{{ number_format($invoice->grand_total, 2) }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($invoice->einvoice_status === 'Downloaded')
                                    <span class="px-2 py-1 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-700 uppercase tracking-wider">Downloaded</span>
                                @elseif($invoice->einvoice_status === 'Generated')
                                    <span class="px-2 py-1 text-[10px] font-bold rounded-full bg-blue-100 text-blue-700 uppercase tracking-wider">Generated</span>
                                @else
                                    <span class="px-2 py-1 text-[10px] font-bold rounded-full bg-slate-100 text-slate-600 uppercase tracking-wider">Not Generated</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('app.invoices.einvoice_json', $invoice->id) }}" target="_blank" class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 transition-colors" title="Download JSON">
                                    <i class="ph ph-download-simple text-lg"></i>
                                </a>
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

<div
    x-show="invoiceModalOpen"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center"
    @keydown.escape.window="closeInvoiceModal"
>
    <div class="absolute inset-0 bg-slate-900/50" @click="closeInvoiceModal"></div>
    <div class="relative w-[min(1100px,95vw)] max-h-[90vh] overflow-hidden rounded-2xl bg-white shadow-xl border border-slate-200">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
            <div class="min-w-0">
                <div class="text-sm font-semibold text-slate-900 truncate" x-text="invoiceModalTitle"></div>
                <div class="text-xs text-slate-500" x-text="invoiceModalSubtitle"></div>
            </div>
            <button type="button" class="w-9 h-9 inline-flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100" @click="closeInvoiceModal">
                <i class="ph ph-x text-lg"></i>
            </button>
        </div>
        <div class="p-5 overflow-y-auto max-h-[calc(90vh-64px)]">
            <template x-if="invoiceModalLoading">
                <div class="text-sm text-slate-600">Loading...</div>
            </template>
            <template x-if="invoiceModalError">
                <div class="text-sm text-red-600" x-text="invoiceModalError"></div>
            </template>

            <template x-if="invoiceModalData">
                <div class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="rounded-xl border border-slate-200 p-4">
                            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Invoice</div>
                            <div class="mt-2 text-sm text-slate-800">
                                <div><span class="font-semibold">No:</span> <span x-text="invoiceModalData.invoice.invoice_no"></span></div>
                                <div><span class="font-semibold">Date:</span> <span x-text="invoiceModalData.invoice.invoice_date"></span></div>
                                <div><span class="font-semibold">Type:</span> <span x-text="invoiceModalData.invoice.tax_type"></span></div>
                            </div>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-4">
                            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Customer</div>
                            <div class="mt-2 text-sm text-slate-800">
                                <div class="font-semibold" x-text="invoiceModalData.customer.business_name || invoiceModalData.customer.name"></div>
                                <div class="text-slate-600" x-text="invoiceModalData.customer.address"></div>
                                <div class="text-slate-600">
                                    <span x-text="invoiceModalData.customer.city"></span>
                                    <span x-show="invoiceModalData.customer.state !== ''">, </span>
                                    <span x-text="invoiceModalData.customer.state"></span>
                                </div>
                                <div class="mt-1"><span class="font-semibold">GSTIN:</span> <span x-text="invoiceModalData.customer.gstin || '-'"></span></div>
                            </div>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-4">
                            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Payment</div>
                            <div class="mt-2 text-sm text-slate-800">
                                <div><span class="font-semibold">Status:</span> <span x-text="invoiceModalData.invoice.payment_status"></span></div>
                                <div><span class="font-semibold">Mode:</span> <span x-text="invoiceModalData.invoice.payment_mode"></span></div>
                                <div x-show="invoiceModalData.invoice.due_date"><span class="font-semibold">Due:</span> <span x-text="invoiceModalData.invoice.due_date"></span></div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-200 bg-slate-50 text-sm font-semibold text-slate-800">Items</div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm whitespace-nowrap">
                                <thead class="bg-white text-slate-500 border-b border-slate-200">
                                    <tr>
                                        <th class="px-4 py-2 text-left font-semibold">#</th>
                                        <th class="px-4 py-2 text-left font-semibold">Product</th>
                                        <th class="px-4 py-2 text-left font-semibold">HSN</th>
                                        <th class="px-4 py-2 text-center font-semibold">Pack</th>
                                        <th class="px-4 py-2 text-center font-semibold">Qty CT/UN</th>
                                        <th class="px-4 py-2 text-right font-semibold">Taxable</th>
                                        <th class="px-4 py-2 text-right font-semibold">CGST</th>
                                        <th class="px-4 py-2 text-right font-semibold" x-text="invoiceModalData.invoice.sgst_label"></th>
                                        <th class="px-4 py-2 text-right font-semibold">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="(it, idx) in invoiceModalData.items" :key="idx">
                                        <tr>
                                            <td class="px-4 py-2 text-slate-600" x-text="idx + 1"></td>
                                            <td class="px-4 py-2 text-slate-800">
                                                <div class="flex items-center gap-2">
                                                    <span x-text="it.product_description"></span>
                                                    <span x-show="it.is_free" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 uppercase tracking-wider">FREE</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-2 text-slate-600" x-text="it.hsn_code"></td>
                                            <td class="px-4 py-2 text-center text-slate-600" x-text="it.pack"></td>
                                            <td class="px-4 py-2 text-center text-slate-600" x-text="`${Math.round(it.qty_ct)}/${Math.round(it.qty_un)}`"></td>
                                            <td class="px-4 py-2 text-right text-slate-700" x-text="formatMoney(it.taxable)"></td>
                                            <td class="px-4 py-2 text-right text-slate-700" x-text="formatMoney(it.cgst)"></td>
                                            <td class="px-4 py-2 text-right text-slate-700" x-text="formatMoney(invoiceModalData.invoice.tax_type === 'IGST' ? it.igst : it.sgst)"></td>
                                            <td class="px-4 py-2 text-right font-semibold text-slate-800" x-text="formatMoney(it.line_total)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2"></div>
                        <div class="rounded-xl border border-slate-200 p-4">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-600">Taxable</span>
                                <span class="font-semibold text-slate-800" x-text="formatMoney(invoiceModalData.invoice.taxable_amount)"></span>
                            </div>
                            <div class="mt-2 flex items-center justify-between text-sm">
                                <span class="text-slate-600">CGST</span>
                                <span class="font-semibold text-slate-800" x-text="formatMoney(invoiceModalData.invoice.total_cgst)"></span>
                            </div>
                            <div class="mt-2 flex items-center justify-between text-sm">
                                <span class="text-slate-600" x-text="invoiceModalData.invoice.sgst_label"></span>
                                <span class="font-semibold text-slate-800" x-text="formatMoney(invoiceModalData.invoice.tax_type === 'IGST' ? invoiceModalData.invoice.total_igst : invoiceModalData.invoice.total_sgst)"></span>
                            </div>
                            <div class="mt-2 flex items-center justify-between text-sm">
                                <span class="text-slate-600">Cess</span>
                                <span class="font-semibold text-slate-800" x-text="formatMoney(invoiceModalData.invoice.total_cess)"></span>
                            </div>
                            <div class="mt-3 pt-3 border-t border-slate-200 flex items-center justify-between text-sm">
                                <span class="text-slate-700 font-semibold">Invoice Value</span>
                                <span class="text-slate-900 font-bold" x-text="formatMoney(invoiceModalData.invoice.grand_total)"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

</div>

<form id="bulk-json-form" action="{{ route('app.reports.gst.bulk-json') }}" method="POST" class="hidden" target="_blank">
    @csrf
    <input type="hidden" name="invoice_ids" id="bulk-invoice-ids">
</form>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const datePreset = document.getElementById('gstDatePreset');
        const from = document.getElementById('gstFromDate');
        const to = document.getElementById('gstToDate');
        const customerName = document.getElementById('gstCustomerNameFilter');
        const gstin = document.getElementById('gstGstinFilter');
        const invoiceNo = document.getElementById('gstInvoiceNoFilter');
        const jsonStatus = document.getElementById('gstJsonStatusFilter');
        const filterForm = document.querySelector('form[data-date-range]');

        const tbody = document.querySelector('tbody[data-gst-body]');
        const allRows = tbody ? Array.from(tbody.querySelectorAll('tr[data-row]')) : [];

        const formatIso = (d) => {
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${y}-${m}-${day}`;
        };

        const startOfWeekMonday = (d) => {
            const out = new Date(d.getFullYear(), d.getMonth(), d.getDate());
            const day = out.getDay();
            const diff = (day + 6) % 7;
            out.setDate(out.getDate() - diff);
            return out;
        };

        const serverHasDate = ((filterForm && filterForm.dataset && filterForm.dataset.serverHasDate) ? String(filterForm.dataset.serverHasDate) : '0') === '1';
        let dateFilterEnabled = serverHasDate;

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

        const applyLocalFilters = () => {
            const qCustomer = (customerName?.value || '').trim().toLowerCase();
            const qGstin = (gstin?.value || '').trim().toLowerCase();
            const qInv = (invoiceNo?.value || '').trim().toLowerCase();
            const qStatus = (jsonStatus?.value || '').trim().toLowerCase();

            let fromVal = (from?.value || '').trim();
            let toVal = (to?.value || '').trim();

            const preset = (datePreset?.value || 'custom').trim();
            if (preset !== 'custom') {
                dateFilterEnabled = true;
                const today = new Date();
                const end = formatIso(today);
                let start = end;
                if (preset === 'week') {
                    start = formatIso(startOfWeekMonday(today));
                } else if (preset === 'month') {
                    start = formatIso(new Date(today.getFullYear(), today.getMonth(), 1));
                }
                if (from) from.value = start;
                if (to) to.value = end;
                fromVal = start;
                toVal = end;
            }

            const hasAny =
                qCustomer !== '' ||
                qGstin !== '' ||
                qInv !== '' ||
                qStatus !== '' ||
                (dateFilterEnabled && (fromVal !== '' || toVal !== ''));

            if (!hasAny) {
                restoreOrder();
                return;
            }

            const matches = [];
            const nonMatches = [];

            for (const r of allRows) {
                let ok = true;

                if (qCustomer !== '') {
                    ok = ok && ((r.dataset.customer || '').includes(qCustomer));
                }
                if (qGstin !== '') {
                    ok = ok && ((r.dataset.gstin || '').includes(qGstin));
                }
                if (qInv !== '') {
                    ok = ok && ((r.dataset.invoice || '').includes(qInv));
                }
                if (qStatus !== '') {
                    ok = ok && ((r.dataset.status || '') === qStatus.toLowerCase());
                }
                if (dateFilterEnabled && (fromVal !== '' || toVal !== '')) {
                    const d = (r.dataset.date || '');
                    if (fromVal !== '') {
                        ok = ok && (d >= fromVal);
                    }
                    if (toVal !== '') {
                        ok = ok && (d <= toVal);
                    }
                }

                if (ok) {
                    matches.push(r);
                } else {
                    nonMatches.push(r);
                }
            }

            if (tbody) {
                matches.sort((a, b) => (Number(a.dataset.originalIndex) || 0) - (Number(b.dataset.originalIndex) || 0));
                nonMatches.sort((a, b) => (Number(a.dataset.originalIndex) || 0) - (Number(b.dataset.originalIndex) || 0));
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

        const updateCustomDateVisibility = () => {
            for (const el of document.querySelectorAll('.gst-custom-date')) {
                el.readOnly = false;
                el.disabled = false;
            }
        };

        if (datePreset) {
            datePreset.addEventListener('change', () => {
                updateCustomDateVisibility();
                applyLocalFilters();
            });
        }
        if (customerName) customerName.addEventListener('input', applyLocalFilters);
        if (gstin) gstin.addEventListener('input', applyLocalFilters);
        if (invoiceNo) invoiceNo.addEventListener('input', applyLocalFilters);
        if (jsonStatus) jsonStatus.addEventListener('change', applyLocalFilters);
        if (from) {
            from.addEventListener('change', applyLocalFilters);
            from.addEventListener('input', applyLocalFilters);
            from.addEventListener('change', () => { dateFilterEnabled = true; });
            from.addEventListener('input', () => { dateFilterEnabled = true; });
            from.addEventListener('focus', () => {
                if (datePreset && (datePreset.value || '').trim() !== 'custom') {
                    datePreset.value = 'custom';
                    updateCustomDateVisibility();
                }
            });
        }
        if (to) {
            to.addEventListener('change', applyLocalFilters);
            to.addEventListener('input', applyLocalFilters);
            to.addEventListener('change', () => { dateFilterEnabled = true; });
            to.addEventListener('input', () => { dateFilterEnabled = true; });
            to.addEventListener('focus', () => {
                if (datePreset && (datePreset.value || '').trim() !== 'custom') {
                    datePreset.value = 'custom';
                    updateCustomDateVisibility();
                }
            });
        }

        updateCustomDateVisibility();
        applyLocalFilters();

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

    document.addEventListener('alpine:init', () => {
        Alpine.data('gstReport', () => ({
            selected: [],
            invoiceModalOpen: false,
            invoiceModalLoading: false,
            invoiceModalError: '',
            invoiceModalData: null,
            invoiceModalTitle: '',
            invoiceModalSubtitle: '',
            formatMoney(v) {
                const n = Number(v || 0);
                return '₹' + n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
            openInvoiceDetails(url) {
                this.invoiceModalOpen = true;
                this.invoiceModalLoading = true;
                this.invoiceModalError = '';
                this.invoiceModalData = null;
                this.invoiceModalTitle = 'Invoice Details';
                this.invoiceModalSubtitle = '';

                fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then(r => {
                        if (!r.ok) throw new Error('Failed to load invoice details');
                        return r.json();
                    })
                    .then(data => {
                        this.invoiceModalData = data;
                        const invNo = data?.invoice?.invoice_no || '';
                        const invDate = data?.invoice?.invoice_date || '';
                        const cust = (data?.customer?.business_name || data?.customer?.name || '').trim();
                        this.invoiceModalTitle = invNo !== '' ? `Invoice ${invNo}` : 'Invoice Details';
                        const parts = [];
                        if (invDate) parts.push(invDate);
                        if (cust) parts.push(cust);
                        this.invoiceModalSubtitle = parts.join(' • ');
                    })
                    .catch(e => {
                        this.invoiceModalError = (e && e.message) ? e.message : 'Failed to load invoice details';
                    })
                    .finally(() => {
                        this.invoiceModalLoading = false;
                    });
            },
            closeInvoiceModal() {
                this.invoiceModalOpen = false;
            },
            getVisibleCheckboxes() {
                return Array.from(document.querySelectorAll('.row-checkbox'))
                    .filter(cb => cb.closest('tr') && cb.closest('tr').style.display !== 'none');
            },
            get allSelected() {
                const checkboxes = this.getVisibleCheckboxes();
                if (checkboxes.length === 0) return false;
                const visibleValues = checkboxes.map(cb => cb.value);
                return visibleValues.every(v => this.selected.includes(v));
            },
            toggleAll(e) {
                const checkboxes = this.getVisibleCheckboxes();
                if (e.target.checked) {
                    const toAdd = Array.from(checkboxes).map(cb => cb.value);
                    this.selected = Array.from(new Set([...this.selected, ...toAdd]));
                } else {
                    const toRemove = new Set(Array.from(checkboxes).map(cb => cb.value));
                    this.selected = this.selected.filter(v => !toRemove.has(v));
                }
            },
            downloadSelectedJson() {
                if (this.selected.length === 0) return;
                const form = document.getElementById('bulk-json-form');
                document.getElementById('bulk-invoice-ids').value = JSON.stringify(this.selected);
                form.submit();
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
        }));
    });
</script>
@endpush
