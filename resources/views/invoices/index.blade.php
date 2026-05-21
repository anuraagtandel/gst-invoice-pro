@extends('layouts.app')

@php
    $isSalesmanPanel = request()->routeIs('salesman.*');
@endphp

@section('title', $isSalesmanPanel ? 'My Invoices' : 'Invoices')

@section('content')
<div class="space-y-6 {{ $isSalesmanPanel ? 'pb-6' : '' }}">

    <!-- Header & Actions -->
    @if($isSalesmanPanel)
        @if(session('success') && session('created_invoice_id'))
            <div class="sm:hidden" x-data="{ open: true }" x-show="open" x-cloak>
                <div class="fixed inset-0 z-[9999] bg-slate-900/40"></div>
                <div class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
                    <div class="w-full max-w-sm rounded-2xl bg-white border border-slate-200 shadow-2xl p-5">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                <i class="ph ph-check-circle text-2xl"></i>
                            </div>
                            <div class="mt-3 text-base font-extrabold text-slate-900">Invoice Saved Successfully</div>
                            <div class="mt-1 text-xs text-slate-500">Choose what you want to do next.</div>
                        </div>
                        <div class="mt-5 grid grid-cols-1 gap-3">
                            <a href="{{ route('salesman.invoices.print', (int) session('created_invoice_id')) }}" target="_blank" @click="open = false" class="h-12 inline-flex items-center justify-center rounded-xl bg-indigo-600 text-white font-semibold shadow-sm">
                                Print
                            </a>
                            <a href="{{ route('salesman.invoices.create') }}" class="h-12 inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-800 font-semibold">
                                New Invoice
                            </a>
                            <button type="button" @click="open = false" class="h-11 inline-flex items-center justify-center rounded-xl bg-slate-100 text-slate-700 font-semibold">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-white p-4 sm:p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="hidden sm:flex items-center justify-between gap-3">
                <div>
                    <h1 class="font-syne text-xl font-bold text-slate-900 leading-tight">My Invoices</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Create invoice, print, and logout from here.</p>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                    @csrf
                    <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors inline-flex items-center gap-2">
                        <i class="ph ph-sign-out text-lg"></i> Logout
                    </button>
                </form>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:mt-4">
                <form action="{{ route('salesman.invoices.index') }}" method="GET" class="relative sm:col-span-2 hidden sm:block">
                    <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search invoice / customer..." class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
                </form>
                <a href="{{ route('salesman.invoices.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg text-sm font-semibold transition-colors flex items-center justify-center gap-2 shadow-sm">
                    <i class="ph ph-plus text-lg"></i> Create Invoice
                </a>
            </div>

            @if(session('success') && session('created_invoice_id'))
                <div class="hidden sm:block mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                    <div class="text-sm font-extrabold text-emerald-800">Invoice Saved Successfully</div>
                    <div class="mt-3 grid grid-cols-2 gap-3">
                        <a href="{{ route('salesman.invoices.print', (int) session('created_invoice_id')) }}" target="_blank" class="h-11 inline-flex items-center justify-center rounded-xl bg-indigo-600 text-white font-semibold shadow-sm">
                            Print
                        </a>
                        <a href="{{ route('salesman.invoices.create') }}" class="h-11 inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-800 font-semibold">
                            New Invoice
                        </a>
                    </div>
                </div>
            @endif
        </div>

        @php
            $preset = $salesmanFilterPreset ?? (string) request('preset', 'today');
            $from = $salesmanFilterFrom ?? (string) request('from', date('Y-m-d'));
            $to = $salesmanFilterTo ?? (string) request('to', date('Y-m-d'));
        @endphp

        <div x-data="salesmanCashFilter({ preset: '{{ $preset }}', from: '{{ $from }}', to: '{{ $to }}', search: @js((string) request('search', '')) })" class="space-y-3">
            <div class="bg-white p-3 sm:p-4 rounded-2xl border border-slate-200 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-[11px] font-extrabold text-slate-500 uppercase tracking-widest">TODAY'S COLLECTION</div>
                        <div class="mt-1 text-2xl sm:text-3xl font-extrabold text-slate-900 leading-none" x-text="money(summary.cash_collected)"></div>
                    </div>
                    <div class="text-[11px] font-semibold text-slate-500" x-show="loading">Updating...</div>
                </div>

                <div class="mt-3 grid grid-cols-2 gap-2 sm:gap-3">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 sm:px-4 sm:py-3">
                        <div class="text-[11px] font-extrabold text-slate-500 uppercase tracking-widest">Cash</div>
                        <div class="mt-1 text-sm font-bold text-slate-700" x-text="summary.cash_invoices + ' Invoices'"></div>
                        <div class="mt-0.5 text-base sm:text-lg font-extrabold text-slate-900" x-text="money(summary.cash_sales)"></div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 sm:px-4 sm:py-3">
                        <div class="text-[11px] font-extrabold text-slate-500 uppercase tracking-widest">Credit</div>
                        <div class="mt-1 text-sm font-bold text-slate-700" x-text="summary.credit_invoices + ' Invoices'"></div>
                        <div class="mt-0.5 text-base sm:text-lg font-extrabold text-slate-900" x-text="money(summary.credit_sales)"></div>
                    </div>
                </div>

                <div class="mt-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 sm:px-4 sm:py-3 flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-extrabold text-slate-500 uppercase tracking-widest">Total Sales</div>
                        <div class="mt-0.5 text-base sm:text-lg font-extrabold text-slate-900" x-text="money(summary.total_sales)"></div>
                    </div>
                </div>
            </div>

            <div class="bg-white p-2 sm:p-2.5 rounded-xl border border-slate-200 shadow-sm">
                <div class="grid grid-cols-4 gap-1 rounded-2xl bg-slate-100 p-1">
                    <button type="button" class="h-10 rounded-xl text-sm font-semibold" @click="setPreset('today')" :class="preset === 'today' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-700'">Today</button>
                    <button type="button" class="h-10 rounded-xl text-sm font-semibold" @click="setPreset('week')" :class="preset === 'week' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-700'">Week</button>
                    <button type="button" class="h-10 rounded-xl text-sm font-semibold" @click="setPreset('month')" :class="preset === 'month' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-700'">Month</button>
                    <button type="button" class="h-10 rounded-xl text-sm font-semibold" @click="preset = 'custom'" :class="preset === 'custom' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-700'">Custom</button>
                </div>

                <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-2" x-show="preset === 'custom'" x-cloak>
                    <div>
                        <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">From</div>
                        <input type="date" x-model="from" @change="apply()" @focus="$event.target.showPicker?.()" @click="$event.target.showPicker?.()" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none cursor-pointer">
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">To</div>
                        <input type="date" x-model="to" @change="apply()" @focus="$event.target.showPicker?.()" @click="$event.target.showPicker?.()" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none cursor-pointer">
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Invoice No</div>
                        <div class="relative">
                            <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
                            <input type="text" inputmode="numeric" x-model="searchInvoice" @keydown.enter.prevent="apply()" @input.debounce.400ms="apply()" placeholder="Search invoice no..." class="w-full pl-10 pr-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <form action="{{ route('app.invoices.index') }}" method="GET" class="relative w-full sm:w-96">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by invoice no or customer..." class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
            </form>
            <div class="flex gap-3 w-full sm:w-auto">
                <a href="{{ route('app.invoices.deleted') }}" class="w-full sm:w-auto bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-2">
                    <i class="ph ph-trash text-lg"></i> Trash
                </a>
                <a href="{{ route('app.invoices.create') }}" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-2 shadow-sm">
                    <i class="ph ph-plus text-lg"></i> New Invoice
                </a>
            </div>
        </div>
    @endif

    <!-- List -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($invoices->isEmpty())
            @include('partials._empty_state', ['icon' => 'ph-receipt', 'title' => 'No invoices found', 'message' => 'Create a new invoice to get started.'])
        @else
            @if($isSalesmanPanel)
                <div class="divide-y divide-slate-100">
                    @foreach($invoices as $invoice)
                        @php
                            if (!empty($invoice->payment_status)) {
                                $status = $invoice->payment_status;
                            } else {
                                $paid = (float) ($invoice->paid_amount ?? 0);
                                $pending = (float) ($invoice->pending_amount ?? 0);
                                if ($paid <= 0) {
                                    $status = 'Unpaid';
                                } elseif ($pending <= 0) {
                                    $status = 'Paid';
                                } else {
                                    $status = 'Partial';
                                }
                            }
                        @endphp
                        <div class="p-4 sm:p-5">
                            <div class="flex items-start gap-3">
                                <div class="flex-1 min-w-0">
                                    <p class="font-syne font-bold text-base text-slate-900">{{ $invoice->invoice_no }}</p>
                                    <p class="mt-1 text-sm text-slate-800 font-semibold leading-snug whitespace-normal break-words">
                                        {{ $invoice->customer->name ?? 'Unknown' }}
                                    </p>
                                    <p class="text-xs text-slate-500 mt-1">Date: {{ $invoice->invoice_date->format('d-m-Y') }}</p>
                                </div>

                                <div class="shrink-0 w-32 text-right">
                                    <span class="inline-flex justify-center px-2 py-1 text-[10px] font-bold rounded uppercase tracking-wider {{ $status === 'Paid' ? 'bg-emerald-50 text-emerald-700' : ($status === 'Partial' ? 'bg-indigo-50 text-indigo-700' : 'bg-amber-50 text-amber-800') }}">
                                        {{ $status }}
                                    </span>
                                    <p class="mt-2 text-xs text-slate-500">Amount</p>
                                    <p class="font-extrabold text-slate-900 leading-tight">₹{{ number_format($invoice->grand_total, 2) }}</p>
                                    <p class="text-[11px] text-slate-500 mt-1 leading-snug">Pending: <span class="font-semibold text-slate-800">₹{{ number_format((float) ($invoice->pending_amount ?? 0), 2) }}</span></p>
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-3">
                                <a href="{{ route('salesman.invoices.print', $invoice->id) }}" target="_blank" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg text-sm font-semibold transition-colors flex items-center justify-center gap-2">
                                    <i class="ph ph-printer text-lg"></i> Print
                                </a>
                                <a href="{{ route('salesman.invoices.print', ['id' => $invoice->id, 'pdf' => 1]) }}" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-lg text-sm font-semibold transition-colors flex items-center justify-center gap-2">
                                    <i class="ph ph-download-simple text-lg"></i> PDF
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-3 font-semibold">Invoice No</th>
                                <th class="px-6 py-3 font-semibold">Date</th>
                                <th class="px-6 py-3 font-semibold">Salesman</th>
                                <th class="px-6 py-3 font-semibold">Customer</th>
                                <th class="px-6 py-3 font-semibold">Payment</th>
                                <th class="px-6 py-3 font-semibold">Status</th>
                                <th class="px-6 py-3 font-semibold">Due Date</th>
                                <th class="px-6 py-3 font-semibold">Tax Type</th>
                                <th class="px-6 py-3 font-semibold text-right">Taxable</th>
                                <th class="px-6 py-3 font-semibold text-right">GST+Cess</th>
                                <th class="px-6 py-3 font-semibold text-right">Total</th>
                                <th class="px-6 py-3 font-semibold text-right">Pending</th>
                                <th class="px-6 py-3 font-semibold text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($invoices as $invoice)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 font-medium text-indigo-600">{{ $invoice->invoice_no }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $invoice->invoice_date->format('d-m-Y') }}</td>
                                <td class="px-6 py-4 text-slate-700 whitespace-nowrap max-w-[140px] truncate" title="{{ $invoice->salesman ?: '' }}">{{ $invoice->salesman ?: '-' }}</td>
                                <td class="px-6 py-4 text-slate-800">{{ $invoice->customer->name ?? 'Unknown' }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-[10px] font-bold rounded bg-slate-100 text-slate-700 uppercase tracking-wider">
                                        {{ $invoice->payment_type ?? ($invoice->payment_mode === 'Credit' ? 'Credit' : 'Cash') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        if (!empty($invoice->payment_status)) {
                                            $status = $invoice->payment_status;
                                        } else {
                                            $paid = (float) ($invoice->paid_amount ?? 0);
                                            $pending = (float) ($invoice->pending_amount ?? 0);
                                            if ($paid <= 0) {
                                                $status = 'Unpaid';
                                            } elseif ($pending <= 0) {
                                                $status = 'Paid';
                                            } else {
                                                $status = 'Partial';
                                            }
                                        }
                                    @endphp
                                    <span class="px-2 py-1 text-[10px] font-bold rounded uppercase tracking-wider {{ $status === 'Paid' ? 'bg-emerald-50 text-emerald-700' : ($status === 'Partial' ? 'bg-indigo-50 text-indigo-700' : 'bg-amber-50 text-amber-800') }}">
                                        {{ $status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ optional($invoice->due_date)->format('d-m-Y') ?: '-' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-[10px] font-bold rounded bg-slate-100 text-slate-600 uppercase tracking-wider">
                                        {{ str_replace('_', '+', $invoice->tax_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-slate-600">₹{{ number_format($invoice->taxable_amount, 2) }}</td>
                                <td class="px-6 py-4 text-right text-slate-600">₹{{ number_format($invoice->total_cgst + $invoice->total_sgst + $invoice->total_cess, 2) }}</td>
                                <td class="px-6 py-4 text-right font-bold text-slate-800">₹{{ number_format($invoice->grand_total, 2) }}</td>
                                <td class="px-6 py-4 text-right font-semibold text-slate-800">₹{{ number_format((float) ($invoice->pending_amount ?? 0), 2) }}</td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('app.invoices.print', $invoice->id) }}" target="_blank" class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 transition-colors" title="Print Invoice">
                                            <i class="ph ph-printer text-lg"></i>
                                        </a>
                                        <a href="{{ route('app.invoices.print', ['id' => $invoice->id, 'pdf' => 1]) }}" class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:bg-emerald-50 hover:text-emerald-700 transition-colors" title="Download PDF">
                                            <i class="ph ph-download-simple text-lg"></i>
                                        </a>
                                        <a href="{{ route('app.invoices.edit', $invoice->id) }}" class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors" title="Edit Invoice">
                                            <i class="ph ph-pencil-simple text-lg"></i>
                                        </a>
                                        <form action="{{ route('app.invoices.destroy', $invoice->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this invoice?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 transition-colors" title="Delete Invoice">
                                                <i class="ph ph-trash text-lg"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            <div class="p-4 border-t border-slate-200">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

@if($isSalesmanPanel)
@push('scripts')
<script>
    window.salesmanCashFilter = function (init) {
        const preset = (init && init.preset) ? String(init.preset) : 'today';
        const from = (init && init.from) ? String(init.from) : '';
        const to = (init && init.to) ? String(init.to) : '';
        const search = (init && init.search) ? String(init.search) : '';
        return {
            preset,
            from,
            to,
            searchInvoice: search,
            loading: false,
            summary: { cash_collected: 0, cash_sales: 0, cash_invoices: 0, credit_invoices: 0, credit_sales: 0, credit_collected: 0, total_sales: 0 },
            init() {
                this.fetchSummary();
            },
            async fetchSummary() {
                this.loading = true;
                try {
                    const url = new URL('{{ route("salesman.invoices.cashSummary") }}', window.location.origin);
                    url.searchParams.set('from', this.from);
                    url.searchParams.set('to', this.to);
                    const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    this.summary = {
                        cash_collected: parseFloat(data.cash_collected || 0),
                        cash_sales: parseFloat(data.cash_sales || 0),
                        cash_invoices: parseInt(data.cash_invoices || 0, 10),
                        credit_invoices: parseInt(data.credit_invoices || 0, 10),
                        credit_sales: parseFloat(data.credit_sales || 0),
                        credit_collected: parseFloat(data.credit_collected || 0),
                        total_sales: parseFloat(data.total_sales || 0),
                    };
                } catch (e) {
                } finally {
                    this.loading = false;
                }
            },
            label() {
                if (this.preset === 'week') return 'This Week Cash Collection';
                if (this.preset === 'month') return 'This Month Cash Collection';
                if (this.preset === 'custom') return 'Cash Collection';
                return "Today's Cash Collection";
            },
            money(v) {
                try {
                    return '₹' + (parseFloat(v || 0)).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                } catch (e) {
                    return '₹' + (parseFloat(v || 0)).toFixed(2);
                }
            },
            setPreset(p) {
                this.preset = p;
                const now = new Date();
                const fmt = (d) => {
                    const y = d.getFullYear();
                    const m = String(d.getMonth() + 1).padStart(2, '0');
                    const day = String(d.getDate()).padStart(2, '0');
                    return `${y}-${m}-${day}`;
                };
                if (p === 'today') {
                    this.from = fmt(now);
                    this.to = fmt(now);
                } else if (p === 'week') {
                    const day = (now.getDay() + 6) % 7;
                    const start = new Date(now);
                    start.setDate(now.getDate() - day);
                    this.from = fmt(start);
                    this.to = fmt(now);
                } else if (p === 'month') {
                    const start = new Date(now.getFullYear(), now.getMonth(), 1);
                    this.from = fmt(start);
                    this.to = fmt(now);
                }
                if (p !== 'custom') {
                    this.searchInvoice = '';
                }
                this.apply();
            },
            apply() {
                const url = new URL(window.location.href);
                url.searchParams.set('preset', this.preset);
                url.searchParams.set('from', this.from);
                url.searchParams.set('to', this.to);
                const q = String(this.searchInvoice || '').trim();
                if (this.preset === 'custom' && q) {
                    url.searchParams.set('search', q);
                } else {
                    url.searchParams.delete('search');
                }
                url.searchParams.delete('page');
                window.location.href = url.toString();
            }
        };
    };
</script>
@endpush
@endif
