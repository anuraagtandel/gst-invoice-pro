@extends('layouts.app')

@section('title', 'Receive Payment')

@section('content')
<div x-data="receivePayment()" x-init="init()" class="space-y-6">
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-syne font-semibold text-lg text-slate-800">Receive Payment</h3>
            <p class="text-sm text-slate-500">Allocate payment to one or multiple pending invoices.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('app.payments.receive.store') }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Customer <span class="text-red-500">*</span></label>
                <select name="customer_id" x-model="customerId" @change="loadInvoices()" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                    <option value="">Select...</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('customer_id')" class="mt-2" />
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Payment Date <span class="text-red-500">*</span></label>
                <input type="date" name="payment_date" x-model="paymentDate" @focus="openDatePicker($event.target)" @click="openDatePicker($event.target)" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none cursor-pointer">
                <x-input-error :messages="$errors->get('payment_date')" class="mt-2" />
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Payment Mode <span class="text-red-500">*</span></label>
                <select name="payment_mode" x-model="paymentMode" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                    <option value="Cash">Cash</option>
                    <option value="UPI">UPI</option>
                    <option value="Cheque">Cheque</option>
                    <option value="Bank">Bank</option>
                    <option value="Other">Other</option>
                </select>
                <x-input-error :messages="$errors->get('payment_mode')" class="mt-2" />
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Payment Amount <span class="text-red-500">*</span></label>
                <input type="number" step="0.000001" min="0" name="amount" x-model="amount" @input="recompute()" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none text-right">
                <x-input-error :messages="$errors->get('amount')" class="mt-2" />
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Reference No</label>
                <input type="text" name="reference_no" x-model="referenceNo" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                <x-input-error :messages="$errors->get('reference_no')" class="mt-2" />
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Notes</label>
                <input type="text" name="notes" x-model="notes" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
            </div>
        </div>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 bg-slate-50 rounded-xl border border-slate-200 p-4">
            <div class="text-sm text-slate-700">
                <span class="font-semibold">Allocated:</span>
                <span class="font-bold" x-text="format6(totalAllocated)"></span>
                <span class="text-slate-500">/</span>
                <span class="font-bold" x-text="format6(amount)"></span>
                <template x-if="allocationError">
                    <span class="ml-3 text-red-700 font-semibold" x-text="allocationError"></span>
                </template>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="autoAllocateOldest()" class="px-4 py-2 rounded-lg bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 text-sm font-semibold">
                    Auto Allocate (Oldest First)
                </button>
                <button type="button" @click="clearAllocations()" class="px-4 py-2 rounded-lg bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 text-sm font-semibold">
                    Clear
                </button>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h4 class="font-syne font-semibold text-slate-800">Pending Invoices</h4>
                <div class="text-sm text-slate-500" x-text="loading ? 'Loading...' : (invoices.length + ' invoices')"></div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Invoice No</th>
                            <th class="px-6 py-3 font-semibold">Invoice Date</th>
                            <th class="px-6 py-3 font-semibold">Due Date</th>
                            <th class="px-6 py-3 font-semibold text-right">Total</th>
                            <th class="px-6 py-3 font-semibold text-right">Paid</th>
                            <th class="px-6 py-3 font-semibold text-right">Pending</th>
                            <th class="px-6 py-3 font-semibold text-right">Allocate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(inv, idx) in invoices" :key="inv.id">
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 font-semibold text-indigo-600" x-text="inv.invoice_no"></td>
                                <td class="px-6 py-4 text-slate-600" x-text="formatDate(inv.invoice_date)"></td>
                                <td class="px-6 py-4 text-slate-600" x-text="inv.due_date ? formatDate(inv.due_date) : '-'"></td>
                                <td class="px-6 py-4 text-right text-slate-700" x-text="formatCurrency(inv.total_amount)"></td>
                                <td class="px-6 py-4 text-right text-slate-700" x-text="formatCurrency(inv.paid_amount)"></td>
                                <td class="px-6 py-4 text-right font-bold text-amber-800" x-text="formatCurrency(inv.pending_amount)"></td>
                                <td class="px-6 py-4 text-right">
                                    <input type="hidden" :name="`allocations[${idx}][invoice_id]`" :value="inv.id">
                                    <input type="number" step="0.000001" min="0" :max="inv.pending_amount" :name="`allocations[${idx}][amount]`" x-model="inv.allocate" @input="recompute()" class="w-32 px-3 py-2 border border-slate-200 rounded-lg text-sm text-right focus:ring-2 focus:ring-indigo-500 outline-none">
                                </td>
                            </tr>
                        </template>
                        <tr x-show="!loading && invoices.length === 0">
                            <td colspan="7" class="px-6 py-6 text-slate-500">No pending invoices for selected customer.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" :disabled="submitDisabled()" class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors shadow-sm">
                Receive & Allocate Payment
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function receivePayment() {
        return {
            customerId: '{{ (string) ($selectedCustomerId ?? '') }}',
            invoiceId: '{{ (string) ($selectedInvoiceId ?? '') }}',
            paymentDate: '{{ date('Y-m-d') }}',
            paymentMode: 'Cash',
            amount: '',
            referenceNo: '',
            notes: '',
            invoices: [],
            loading: false,
            totalAllocated: 0,
            allocationError: '',
            init() {
                if (this.customerId) this.loadInvoices();
            },
            openDatePicker(el) {
                if (!el) return;
                if (typeof el.showPicker === 'function') {
                    try { el.showPicker(); } catch (e) { }
                }
            },
            async loadInvoices() {
                this.invoices = [];
                this.allocationError = '';
                if (!this.customerId) return;

                this.loading = true;
                try {
                    const res = await fetch(`/app/api/payments/pending-invoices?customer_id=${encodeURIComponent(this.customerId)}`);
                    const data = await res.json();
                    const invs = Array.isArray(data.invoices) ? data.invoices : [];
                    this.invoices = invs.map(i => ({
                        ...i,
                        allocate: ''
                    }));
                } catch (e) {
                    this.invoices = [];
                } finally {
                    this.loading = false;
                    if (this.invoiceId) {
                        const inv = this.invoices.find(x => String(x.id) === String(this.invoiceId));
                        if (inv && (parseFloat(this.amount) || 0) > 0) {
                            const pending = parseFloat(inv.pending_amount) || 0;
                            const remaining = parseFloat(this.amount) || 0;
                            inv.allocate = this.format6(Math.min(pending, remaining));
                        }
                    }
                    this.recompute();
                }
            },
            clearAllocations() {
                this.invoices.forEach(i => i.allocate = '');
                this.recompute();
            },
            autoAllocateOldest() {
                let remaining = parseFloat(this.amount) || 0;
                this.invoices.forEach(i => {
                    const pending = parseFloat(i.pending_amount) || 0;
                    if (remaining <= 0) {
                        i.allocate = '';
                        return;
                    }
                    const use = Math.min(pending, remaining);
                    i.allocate = use > 0 ? this.format6(use) : '';
                    remaining = remaining - use;
                });
                this.recompute();
            },
            recompute() {
                const pay = parseFloat(this.amount) || 0;
                let total = 0;
                let err = '';
                this.invoices.forEach(i => {
                    let a = parseFloat(i.allocate);
                    if (!isFinite(a)) a = 0;
                    if (a < 0) a = 0;
                    const pending = parseFloat(i.pending_amount) || 0;
                    if (a > pending + 0.0000001) err = 'Allocation cannot exceed invoice pending.';
                    total += a;
                });
                total = Math.round(total * 1000000) / 1000000;
                this.totalAllocated = total;
                if (!err && total > pay + 0.0000001) err = 'Allocated amount exceeds payment amount.';
                this.allocationError = err;
            },
            submitDisabled() {
                const pay = parseFloat(this.amount) || 0;
                if (!this.customerId) return true;
                if (pay <= 0) return true;
                if (this.invoices.length === 0) return true;
                if (this.allocationError) return true;
                return false;
            },
            formatDate(d) {
                if (!d) return '';
                return String(d).split('T')[0].split('-').reverse().join('-');
            },
            format6(v) {
                let n = parseFloat(v);
                if (!isFinite(n)) return '';
                let fixed = (Math.round(n * 1000000) / 1000000).toFixed(6);
                return fixed.replace(/\.?0+$/, '');
            },
            formatCurrency(v) {
                let n = parseFloat(v);
                if (!isFinite(n)) n = 0;
                let fixed = (Math.round(n * 1000000) / 1000000).toFixed(6);
                fixed = fixed.replace(/\.?0+$/, '');
                return '₹' + fixed;
            }
        }
    }
</script>
@endpush
@endsection
