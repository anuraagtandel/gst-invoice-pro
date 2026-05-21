@extends('layouts.app')

@section('content')
@php
    $isSalesmanPanel = request()->routeIs('salesman.*');
@endphp

@php
    $dbgInvoiceAutocompleteSession = 'invoice-autocomplete-stops';
    $dbgInvoiceAutocompleteUrl = '/debug/trae-event';
@endphp

@push('styles')
    <style>
        .invoice-create-scope input.sa-no-spin::-webkit-outer-spin-button,
        .invoice-create-scope input.sa-no-spin::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .invoice-create-scope input.sa-no-spin[type=number] {
            -moz-appearance: textfield;
            appearance: textfield;
        }
    </style>
@endpush

<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h1 class="font-syne text-2xl font-bold text-slate-800">New Invoice</h1>
        <p class="text-slate-500 text-sm mt-1">Create a new GST invoice for a customer.</p>
    </div>

    <div class="{{ $isSalesmanPanel ? 'hidden sm:flex items-stretch sm:items-center gap-3' : 'flex flex-col sm:flex-row items-stretch sm:items-center gap-3' }}">
        <a href="{{ route($isSalesmanPanel ? 'salesman.invoices.index' : 'app.invoices.index') }}"
           class="bg-white border border-slate-200 text-slate-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-50 transition-colors shadow-sm text-center">
            Cancel
        </a>

        <button type="submit"
                form="invoiceForm"
                class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm inline-flex items-center justify-center gap-2">
            <i class="ph ph-check-circle"></i>
            Save Invoice
        </button>
    </div>
</div>

<div x-data="invoiceForm" x-init="init()" class="bg-white border border-slate-200 rounded-2xl shadow-sm invoice-create-scope {{ $isSalesmanPanel ? '' : 'sa-invoice-create' }}">
    <form id="invoiceForm" x-ref="invoiceFormEl" action="{{ route($isSalesmanPanel ? 'salesman.invoices.store' : 'app.invoices.store') }}" method="POST" class="{{ $isSalesmanPanel ? 'p-4 sm:p-6 pb-28 sm:pb-6' : 'p-6' }}" @submit.prevent="
            if (isSalesmanPanel && isMobile) {
                dbgSave('form.submit:event', { source: 'submit.prevent' });
                flowErrors = [];
                if (isInvoiceDatePast()) { flowErrors = ['Invoice date cannot be in the past']; flowStep = 'review'; $nextTick(() => { if ($refs.flowScroll) $refs.flowScroll.scrollTop = 0; }); return false; }
                if (dueDate && dueDate < invoiceDate) { flowErrors = ['Due date must be same or after invoice date']; flowStep = 'review'; $nextTick(() => { if ($refs.flowScroll) $refs.flowScroll.scrollTop = 0; }); return false; }
                if (!selectedCustomer) { flowErrors = ['Please select a customer.']; flowStep = 'customer'; $nextTick(() => { if ($refs.flowScroll) $refs.flowScroll.scrollTop = 0; }); return false; }
                if (baseItems().length === 0) { flowErrors = ['Please add at least one product.']; flowStep = 'products'; $nextTick(() => { if ($refs.flowScroll) $refs.flowScroll.scrollTop = 0; }); return false; }
                let stockErrors = stockErrorMessages();
                if (stockErrors.length) { flowErrors = stockErrors; flowStep = 'review'; $nextTick(() => { if ($refs.flowScroll) $refs.flowScroll.scrollTop = 0; }); return false; }
                if (creditLimitPolicy === 'block' && creditLimitExceeded()) { flowErrors = [creditLimitMessage()]; flowStep = 'review'; $nextTick(() => { if ($refs.flowScroll) $refs.flowScroll.scrollTop = 0; }); return false; }
                dbgSave('form.submit:call', { source: 'submit.prevent' });
                $el.submit();
                return;
            }
            if(items.some(i => i.stock_warning)) { alert('Please fix insufficient stock items before saving.'); return false; } if(isInvoiceDatePast()) { alert('Invoice date cannot be in the past'); return false; } if(dueDate && dueDate < invoiceDate) { alert('Due date must be same or after invoice date'); return false; } if(creditLimitPolicy === 'block' && creditLimitExceeded()) { alert(creditLimitMessage()); return false; } $el.submit()
        ">
        @csrf

        <!-- Error Messages -->
        @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                <div class="flex items-center gap-2 text-red-700 font-medium mb-2">
                    <i class="ph ph-warning-circle text-lg"></i>
                    Please fix the following errors:
                </div>
                <ul class="list-disc list-inside text-sm text-red-600 ml-6 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl flex items-center gap-3">
                <i class="ph ph-warning-circle text-lg"></i>
                <span class="text-sm font-medium">{{ session('error') }}</span>
            </div>
        @endif

        @if($isSalesmanPanel)
            <div class="sm:hidden" x-show="isSalesmanPanel && isMobile" x-cloak>
                <input type="hidden" name="invoice_date" :value="invoiceDate">
                <input type="hidden" name="due_date" :value="dueDate">
                <input type="hidden" name="customer_id" :value="selectedCustomer">
                @php $lockedSalesmanName = isset($lockedSalesmanName) ? trim((string) $lockedSalesmanName) : ''; @endphp
                @if($lockedSalesmanName !== '')
                    <input type="hidden" name="salesman" value="{{ $lockedSalesmanName }}">
                @endif
                <input type="hidden" name="payment_type" :value="paymentType">
                <input type="hidden" name="payment_mode" :value="paymentType === 'Credit' ? 'Credit' : 'Cash'">
                <input type="hidden" name="paid_amount" :value="paidAmount">

                <template x-for="(item, index) in items" :key="'h' + item.id">
                    <div class="hidden">
                        <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id">
                        <input type="hidden" :name="`items[${index}][qty_ct]`" :value="item.qty_ct || 0">
                        <input type="hidden" :name="`items[${index}][qty_un]`" :value="item.qty_un || 0">
                        <input type="hidden" :name="`items[${index}][discount_pct]`" value="0">
                        <input type="hidden" :name="`items[${index}][is_free]`" :value="item.is_free ? '1' : '0'">
                        <input type="hidden" :name="`items[${index}][parent_id]`" :value="item.parent_id || 0">
                    </div>
                </template>

                <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden flex flex-col min-h-[calc(100dvh-12rem)]">
                    <div class="px-4 py-3 border-b border-slate-200 bg-slate-50">
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-sm font-extrabold text-slate-900" x-text="flowStep === 'customer' ? 'Select Customer' : (flowStep === 'products' ? 'Select Products' : 'Review Invoice')"></div>
                            <div class="text-[11px] font-semibold text-slate-500" x-text="flowStep === 'customer' ? '1/4' : (flowStep === 'products' ? '2/4' : '3/4')"></div>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto" x-ref="flowScroll">
                    <div class="p-4 pb-0" x-show="flowErrors.length > 0" x-cloak>
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                            <div class="text-[12px] font-extrabold text-amber-900">Please fix the following:</div>
                            <div class="mt-2 space-y-1">
                                <template x-for="(e, ei) in flowErrors" :key="'fe' + ei">
                                    <div class="text-[12px] font-semibold text-amber-900 whitespace-normal break-words" x-text="'- ' + e"></div>
                                </template>
                            </div>
                        </div>
                    </div>
                    <div class="p-4" x-show="flowStep === 'customer'">
                        <div class="relative">
                            <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
                            <input type="text" x-model="customerQuery" placeholder="Search customer..." class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="mt-4 space-y-3">
                            <template x-for="c in flowCustomers" :key="c.id">
                                <button type="button" @click="flowSelectCustomer(c.id)" class="w-full text-left rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:bg-slate-50 transition-colors">
                                    <div class="font-bold text-slate-900 text-base leading-snug whitespace-normal break-words" x-text="c.name"></div>
                                    <div class="mt-1 text-xs text-slate-500" x-text="c.gstin || 'No GSTIN'"></div>
                                </button>
                            </template>
                            <div class="text-sm text-slate-500 text-center py-6" x-show="flowCustomers.length === 0">
                                No customers found
                            </div>
                        </div>
                    </div>

                    <div class="p-4" x-show="flowStep === 'products'">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-xs text-slate-500">Customer</div>
                                <div class="text-sm font-extrabold text-slate-900 truncate" x-text="selectedCustomerName()"></div>
                            </div>
                            <button type="button" @click="flowErrors = []; flowStep = 'customer'; $nextTick(() => { if ($refs.flowScroll) $refs.flowScroll.scrollTop = 0; })" class="text-sm font-semibold text-indigo-700">Change</button>
                        </div>

                        <div class="mt-4 flex gap-2 overflow-x-auto pb-1">
                            <button type="button" @click="selectedCategory = 'All'" class="px-4 py-2 rounded-xl text-sm font-semibold whitespace-nowrap" :class="selectedCategory === 'All' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700'">All</button>
                            <template x-for="cat in flowCategories" :key="cat">
                                <button type="button" @click="selectedCategory = cat" class="px-4 py-2 rounded-xl text-sm font-semibold whitespace-nowrap" :class="selectedCategory === cat ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700'" x-text="cat"></button>
                            </template>
                        </div>

                        <div class="mt-4 relative">
                            <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
                            <input type="text" x-model="productQuery" placeholder="Search product..." class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>

                        <div class="mt-3 space-y-1.5">
                            <template x-for="p in flowProducts" :key="p.id">
                                <div class="rounded-xl border border-slate-200 bg-white px-3 py-2.5">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-900 leading-snug text-sm line-clamp-2 break-words" x-text="p.label"></div>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <div class="text-sm font-extrabold text-slate-900" x-text="formatCurrency(productLineTotal(p.id))"></div>
                                        </div>
                                    </div>

                                    <div class="mt-2 flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-2">
                                            <div class="text-[10px] font-semibold text-slate-600">CT</div>
                                            <div class="inline-flex items-center gap-1 rounded-xl bg-slate-50 border border-slate-200 p-1">
                                                <button type="button"
                                                    @touchstart="dbgQty('qtyBtn:touchstart', { productId: String(p.id), kind: 'ct', delta: -1 })"
                                                    @pointerdown="dbgQty('qtyBtn:pointerdown', { productId: String(p.id), kind: 'ct', delta: -1, pointerType: $event.pointerType || null })"
                                                    @click="dbgQty('qtyBtn:click', { productId: String(p.id), kind: 'ct', delta: -1 }); flowInc(p.id, 'ct', -1)"
                                                    class="w-9 h-9 rounded-lg border border-slate-200 bg-white text-slate-700 font-extrabold">-</button>
                                                <input type="number" inputmode="numeric" min="0" step="1"
                                                    class="w-14 h-9 rounded-lg bg-white border border-slate-200 font-extrabold text-slate-900 text-sm text-center sa-no-spin focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                                    :value="flowQtyCt(p.id)"
                                                    @focus="$event.target.select()"
                                                    @click="$event.target.select()"
                                                    @blur="dbgQty('qtyInput:blur', { productId: String(p.id), kind: 'ct' })"
                                                    @input="dbgQty('qtyInput:input', { productId: String(p.id), kind: 'ct', value: String($event.target.value || '') }); flowSetQty(p.id, 'ct', $event.target.value)">
                                                <button type="button"
                                                    @touchstart="dbgQty('qtyBtn:touchstart', { productId: String(p.id), kind: 'ct', delta: 1 })"
                                                    @pointerdown="dbgQty('qtyBtn:pointerdown', { productId: String(p.id), kind: 'ct', delta: 1, pointerType: $event.pointerType || null })"
                                                    @click="dbgQty('qtyBtn:click', { productId: String(p.id), kind: 'ct', delta: 1 }); flowInc(p.id, 'ct', 1)"
                                                    class="w-9 h-9 rounded-lg bg-indigo-600 text-white font-extrabold">+</button>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <div class="text-[10px] font-semibold text-slate-600">UN</div>
                                            <div class="inline-flex items-center gap-1 rounded-xl bg-slate-50 border border-slate-200 p-1">
                                                <button type="button"
                                                    @touchstart="dbgQty('qtyBtn:touchstart', { productId: String(p.id), kind: 'un', delta: -1 })"
                                                    @pointerdown="dbgQty('qtyBtn:pointerdown', { productId: String(p.id), kind: 'un', delta: -1, pointerType: $event.pointerType || null })"
                                                    @click="dbgQty('qtyBtn:click', { productId: String(p.id), kind: 'un', delta: -1 }); flowInc(p.id, 'un', -1)"
                                                    class="w-9 h-9 rounded-lg border border-slate-200 bg-white text-slate-700 font-extrabold">-</button>
                                                <input type="number" inputmode="numeric" min="0" step="1"
                                                    class="w-14 h-9 rounded-lg bg-white border border-slate-200 font-extrabold text-slate-900 text-sm text-center sa-no-spin focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                                    :value="flowQtyUn(p.id)"
                                                    @focus="$event.target.select()"
                                                    @click="$event.target.select()"
                                                    @blur="dbgQty('qtyInput:blur', { productId: String(p.id), kind: 'un' })"
                                                    @input="dbgQty('qtyInput:input', { productId: String(p.id), kind: 'un', value: String($event.target.value || '') }); flowSetQty(p.id, 'un', $event.target.value)">
                                                <button type="button"
                                                    @touchstart="dbgQty('qtyBtn:touchstart', { productId: String(p.id), kind: 'un', delta: 1 })"
                                                    @pointerdown="dbgQty('qtyBtn:pointerdown', { productId: String(p.id), kind: 'un', delta: 1, pointerType: $event.pointerType || null })"
                                                    @click="dbgQty('qtyBtn:click', { productId: String(p.id), kind: 'un', delta: 1 }); flowInc(p.id, 'un', 1)"
                                                    class="w-9 h-9 rounded-lg bg-indigo-600 text-white font-extrabold">+</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-2 text-[11px] font-semibold text-amber-700" x-show="stockWarnings[String(p.id)]" x-text="stockWarnings[String(p.id)]" x-cloak></div>
                                </div>
                            </template>
                            <div class="text-sm text-slate-500 text-center py-6" x-show="flowProducts.length === 0">
                                No products found
                            </div>
                        </div>
                    </div>

                    <div class="p-3" x-show="flowStep === 'review'">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-xs text-slate-500">Customer</div>
                                <div class="text-base font-extrabold text-slate-900 whitespace-normal break-words" x-text="selectedCustomerName()"></div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="text-xs font-semibold text-slate-600 mb-1.5">Payment Type</div>
                            <div class="grid grid-cols-2 gap-2 bg-slate-100 p-1 rounded-xl">
                                <button type="button" @click="setPaymentType('Cash')" class="h-11 rounded-lg text-sm font-extrabold transition-colors" :class="paymentType === 'Cash' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-700'">Cash</button>
                                <button type="button" @click="setPaymentType('Credit')" class="h-11 rounded-lg text-sm font-extrabold transition-colors" :class="paymentType === 'Credit' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-700'">Credit</button>
                            </div>
                        </div>

                        <div class="mt-3 space-y-2">
                            <template x-for="it in baseItems()" :key="'r' + it.id">
                                <div class="rounded-lg border border-slate-200 bg-white overflow-hidden">
                                    <div class="px-3 py-2">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="min-w-0">
                                                <div class="font-bold text-slate-900 leading-tight text-sm line-clamp-2 break-words" x-text="productLabel(it.product_id)"></div>
                                                <div class="mt-0.5 text-[11px] text-slate-600 font-semibold">
                                                    <span x-text="'CT: ' + (parseFloat(it.qty_ct) || 0)"></span>
                                                    <span class="text-slate-300 px-1">•</span>
                                                    <span x-text="'UN: ' + (parseFloat(it.qty_un) || 0)"></span>
                                                </div>
                                            </div>
                                            <div class="shrink-0 flex flex-col items-end gap-1.5 pt-1">
                                                <div class="text-right">
                                                    <div class="text-[10px] font-semibold text-slate-500">Amount</div>
                                                    <div class="text-sm font-extrabold text-slate-900 leading-none" x-text="formatCurrency(it.line_total)"></div>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <button type="button"
                                                        @click="reviewEditProductId = (reviewEditProductId === String(it.product_id) ? '' : String(it.product_id))"
                                                        class="w-9 h-9 inline-flex items-center justify-center rounded-lg border border-slate-200 text-slate-700">
                                                        <i class="ph ph-pencil-simple text-base"></i>
                                                    </button>
                                                    <button type="button" @click="flowRemove(it.product_id)" class="w-9 h-9 inline-flex items-center justify-center rounded-lg border border-slate-200 text-slate-600">
                                                        <i class="ph ph-trash text-base"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-2" x-show="reviewEditProductId === String(it.product_id)" x-cloak>
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex items-center gap-2">
                                                    <div class="text-[10px] font-semibold text-slate-600">CT</div>
                                                    <div class="inline-flex items-center gap-1 rounded-xl bg-slate-50 border border-slate-200 p-1">
                                                        <button type="button" @click="flowInc(it.product_id, 'ct', -1)" class="w-9 h-9 rounded-lg border border-slate-200 bg-white text-slate-700 font-extrabold">-</button>
                                                        <input type="number" inputmode="numeric" min="0" step="1"
                                                            class="w-14 h-9 rounded-lg bg-white border border-slate-200 font-extrabold text-slate-900 text-sm text-center sa-no-spin focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                                            :value="flowQtyCt(it.product_id)"
                                                            @focus="$event.target.select()"
                                                            @click="$event.target.select()"
                                                            @input="flowSetQty(it.product_id, 'ct', $event.target.value)">
                                                        <button type="button" @click="flowInc(it.product_id, 'ct', 1)" class="w-9 h-9 rounded-lg bg-indigo-600 text-white font-extrabold">+</button>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <div class="text-[10px] font-semibold text-slate-600">UN</div>
                                                    <div class="inline-flex items-center gap-1 rounded-xl bg-slate-50 border border-slate-200 p-1">
                                                        <button type="button" @click="flowInc(it.product_id, 'un', -1)" class="w-9 h-9 rounded-lg border border-slate-200 bg-white text-slate-700 font-extrabold">-</button>
                                                        <input type="number" inputmode="numeric" min="0" step="1"
                                                            class="w-14 h-9 rounded-lg bg-white border border-slate-200 font-extrabold text-slate-900 text-sm text-center sa-no-spin focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                                            :value="flowQtyUn(it.product_id)"
                                                            @focus="$event.target.select()"
                                                            @click="$event.target.select()"
                                                            @input="flowSetQty(it.product_id, 'un', $event.target.value)">
                                                        <button type="button" @click="flowInc(it.product_id, 'un', 1)" class="w-9 h-9 rounded-lg bg-indigo-600 text-white font-extrabold">+</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-2 text-[11px] font-semibold text-amber-700" x-show="stockWarnings[String(it.product_id)]" x-text="stockWarnings[String(it.product_id)]" x-cloak></div>
                                    </div>

                                    <template x-for="f in freeItemsForParent(it.id)" :key="'rf' + f.id">
                                        <div class="border-t border-slate-100 bg-emerald-50/60 px-3 py-2 pl-5">
                                            <div class="flex items-center gap-2 text-[10px] font-extrabold text-emerald-800 uppercase tracking-wider">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-emerald-100">Free</span>
                                                <span class="text-emerald-700">Free with above product</span>
                                            </div>
                                            <div class="mt-0.5 text-[12px] font-bold text-emerald-900 whitespace-normal break-words" x-text="productLabel(f.product_id)"></div>
                                            <div class="mt-0 text-[11px] font-semibold text-emerald-800" x-text="freeQtyText(f)"></div>
                                        </div>
                                    </template>
                            </template>
                            <div class="text-sm text-slate-500 text-center py-6" x-show="baseItems().length === 0">
                                No items selected
                            </div>
                        </div>

                        <template x-if="groupFreeItems().length > 0">
                            <div class="mt-3 space-y-2">
                                <template x-for="f in groupFreeItems()" :key="'gfi' + f.id">
                                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2">
                                        <div class="inline-flex items-center px-2 py-0.5 rounded-lg bg-emerald-100 text-emerald-800 text-[10px] font-extrabold uppercase tracking-wider">
                                            Free
                                        </div>
                                        <div class="mt-0.5 text-[10px] font-extrabold text-emerald-700 uppercase tracking-wider">Free item</div>
                                        <div class="mt-0.5 text-[12px] font-bold text-emerald-900 whitespace-normal break-words" x-text="productLabel(f.product_id)"></div>
                                        <div class="mt-0 text-[11px] font-semibold text-emerald-800" x-text="freeQtyText(f)"></div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 space-y-1">
                            <div class="flex items-center justify-between text-sm text-slate-700">
                                <div class="font-semibold">Taxable</div>
                                <div class="font-extrabold text-slate-900" x-text="formatCurrency(totals.taxable)"></div>
                            </div>
                            <div class="flex items-center justify-between text-sm text-slate-700">
                                <div class="font-semibold">Tax</div>
                                <div class="font-extrabold text-slate-900" x-text="formatCurrency((parseFloat(totals.cgst)||0) + (parseFloat(totals.sgst)||0) + (parseFloat(totals.igst)||0))"></div>
                            </div>
                            <div class="pt-2 border-t border-slate-200 flex items-center justify-between">
                                <div class="font-syne font-bold text-slate-800">Grand Total</div>
                                <div class="font-syne font-bold text-lg text-indigo-600" x-text="formatCurrency(totals.grand_total)"></div>
                            </div>
                        </div>
                    </div>
                    </div>

                    <div class="shrink-0 bg-white/95 backdrop-blur border-t border-slate-200 p-3 pb-[max(1rem,env(safe-area-inset-bottom))] shadow-[0_-8px_24px_rgba(15,23,42,0.10)]" x-show="flowStep === 'products'" x-cloak>
                        <button type="button" @click="flowGoReview()" class="w-full h-12 inline-flex items-center justify-center rounded-xl bg-indigo-600 text-white font-semibold shadow-sm">
                            <span>Review Invoice</span>
                            <span class="ml-2 text-indigo-100" x-text="'(' + selectedItemCount() + ' Items)'"></span>
                        </button>
                    </div>

                    <div class="shrink-0 bg-white/95 backdrop-blur border-t border-slate-200 p-3 pb-[max(1rem,env(safe-area-inset-bottom))] shadow-[0_-8px_24px_rgba(15,23,42,0.10)]" x-show="flowStep === 'review'" x-cloak>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" @click="flowStep = 'products'; $nextTick(() => { if ($refs.flowScroll) $refs.flowScroll.scrollTop = 0; })" class="h-12 inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-800 font-semibold">
                                Back
                            </button>
                            <button type="button" @click="flowAttemptSave()" class="h-12 inline-flex items-center justify-center rounded-xl bg-indigo-600 text-white font-semibold shadow-sm">
                                Save Invoice
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div x-show="!isSalesmanPanel || !isMobile">
        <!-- Top Section -->
        <div class="{{ $isSalesmanPanel ? 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 sm:gap-6 mb-8 pb-8 border-b border-slate-100' : 'grid grid-cols-1 md:grid-cols-6 gap-6 mb-8 pb-8 border-b border-slate-100' }}">
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Invoice No <span class="text-red-500">*</span></label>
                <input type="text" value="{{ $nextInvoiceNo }}" readonly class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-500 cursor-not-allowed font-medium">
            </div>
            
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Date <span class="text-red-500">*</span></label>
                <input type="date" name="invoice_date" x-model="invoiceDate" :min="todayStr" @change="onInvoiceDateChange()" @focus="openDatePicker($event.target)" @click="openDatePicker($event.target)" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all cursor-pointer hover:border-slate-300">
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Due Date</label>
                <input type="date" name="due_date" x-model="dueDate" :min="invoiceDate" @focus="openDatePicker($event.target)" @click="openDatePicker($event.target)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all cursor-pointer hover:border-slate-300">
            </div>

            <div class="relative" x-data="{ 
                search: '', 
                showResults: false,
                get filteredCustomers() {
                    if (this.search === '') return [];
                    return $data.customers.filter(c => 
                        c.name.toLowerCase().includes(this.search.toLowerCase()) || 
                        (c.gstin && c.gstin.toLowerCase().includes(this.search.toLowerCase()))
                    );
                },
                selectCustomer(customer) {
                    this.search = customer.name;
                    $data.selectedCustomer = customer.id;
                    this.showResults = false;
                    $data.updateTaxType();
                },
                init() {
                    if($data.selectedCustomer) {
                        let c = $data.customers.find(x => x.id == $data.selectedCustomer);
                        if(c) this.search = c.name;
                    }
                }
            }" @click.away="showResults = false">
                <label class="block text-xs font-medium text-slate-700 mb-1">Customer <span class="text-red-500">*</span></label>
                <input type="text" x-model="search" @focus="showResults = true" @input="showResults = true" placeholder="Search customer..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all">
                <input type="hidden" name="customer_id" x-model="selectedCustomer">
                
                <div x-show="showResults && filteredCustomers.length > 0" class="absolute z-[150] w-full mt-1.5 bg-white border border-slate-200 rounded-xl shadow-2xl max-h-72 overflow-y-auto ring-1 ring-black/5 backdrop-blur-sm">
                    <div class="p-1.5">
                        <template x-for="customer in filteredCustomers" :key="customer.id">
                            <button type="button" @click="selectCustomer(customer)" class="w-full px-4 py-3 text-left text-sm hover:bg-indigo-50 rounded-lg transition-colors flex flex-col gap-0.5 border-b border-slate-50 last:border-0">
                                <span class="font-bold text-slate-800" x-text="customer.name"></span>
                                <span class="text-[10px] text-slate-500 uppercase tracking-widest font-bold" x-text="customer.gstin"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
            <div class="sm:col-span-2 lg:col-span-2" x-show="selectedCustomer && (selectedCustomerLimit() > 0)">
                <div class="text-xs font-semibold" :class="creditLimitExceeded() ? 'text-red-700' : 'text-slate-600'">
                    Outstanding: ₹<span x-text="formatMoney6(selectedCustomerOutstanding())"></span>
                    <span class="text-slate-400">/</span>
                    Limit: ₹<span x-text="formatMoney6(selectedCustomerLimit())"></span>
                    <span class="text-slate-400">•</span>
                    Projected: ₹<span x-text="formatMoney6(projectedOutstanding())"></span>
                </div>
                <div class="mt-1 text-[11px] font-semibold" x-show="creditLimitExceeded()" :class="creditLimitPolicy === 'block' ? 'text-red-700' : 'text-amber-700'">
                    <span x-text="creditLimitPolicy === 'block' ? 'Credit limit exceeded (blocked).' : 'Credit limit exceeded (warning).'"></span>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Salesman</label>
                @php
                    $lockedSalesmanName = isset($lockedSalesmanName) ? trim((string) $lockedSalesmanName) : '';
                    $currentSalesman = old('salesman', $lockedSalesmanName);
                @endphp
                @if($isSalesmanPanel && $lockedSalesmanName !== '')
                    <input type="text" value="{{ $lockedSalesmanName }}" readonly class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-700 cursor-not-allowed font-medium">
                    <input type="hidden" name="salesman" value="{{ $lockedSalesmanName }}">
                @else
                    <select name="salesman" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                        <option value="">Select...</option>
                        @foreach($salesmen as $s)
                            <option value="{{ $s->name }}" @selected($currentSalesman === $s->name)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Payment Type <span class="text-red-500">*</span></label>
                @if($isSalesmanPanel)
                    <div class="sm:hidden">
                        <div class="grid grid-cols-2 gap-2 bg-slate-100 p-1 rounded-xl">
                            <button type="button"
                                @click="setPaymentType('Cash')"
                                class="h-11 rounded-lg text-sm font-semibold transition-colors whitespace-nowrap"
                                :class="paymentType === 'Cash' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-700'">
                                Cash
                            </button>
                            <button type="button"
                                @click="setPaymentType('Credit')"
                                class="h-11 rounded-lg text-sm font-semibold transition-colors whitespace-nowrap"
                                :class="paymentType === 'Credit' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-slate-700'">
                                Credit
                            </button>
                        </div>
                        <input type="hidden" name="payment_type" :value="paymentType">
                    </div>
                    <div class="hidden sm:block">
                        <select name="payment_type" x-model="paymentType" @change="onPaymentTypeChange()" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                            <option value="Cash">Cash</option>
                            <option value="Credit">Credit</option>
                        </select>
                    </div>
                @else
                    <select name="payment_type" x-model="paymentType" @change="onPaymentTypeChange()" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                        <option value="Cash">Cash</option>
                        <option value="Credit">Credit</option>
                    </select>
                @endif
                <input type="hidden" name="payment_mode" :value="paymentType === 'Credit' ? 'Credit' : 'Cash'">
            </div>
        </div>

        <!-- Items Section -->
        <div class="mb-4 flex items-center justify-between">
            <h3 class="font-syne font-semibold text-lg text-slate-800">Invoice Items</h3>
            <span class="text-xs font-medium px-3 py-1 rounded-full bg-slate-100 text-slate-600" x-text="'Tax Type: ' + (taxType || 'Auto')"></span>
        </div>

        <div x-show="schemePreview.length > 0" x-cloak class="mb-4 bg-indigo-50/40 border border-indigo-100 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div class="font-syne font-semibold text-slate-800">Scheme Preview</div>
                <div class="text-[11px] font-semibold text-indigo-700 uppercase tracking-wider" x-text="schemePreview.length + ' applied'"></div>
            </div>
            <div class="mt-3 space-y-2">
                <template x-for="(sp, i) in schemePreview" :key="sp.key || i">
                    <div class="bg-white border border-indigo-100 rounded-lg px-3 py-2">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-sm font-bold text-slate-900 truncate" x-text="sp.scheme_name"></div>
                                <div class="mt-1 text-[11px] text-slate-600">
                                    Total Qty (CT): <span class="font-bold text-slate-800" x-text="sp.total_ct"></span>
                                    <span class="text-slate-300 px-1">•</span>
                                    Slab: <span class="font-bold text-slate-800" x-text="sp.qualified ? sp.slab : 'Not qualified'"></span>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-[11px] text-slate-500">Free</div>
                                <div class="text-sm font-bold text-emerald-700" x-text="sp.qualified ? (sp.free_qty_units + ' UN') : '—'"></div>
                            </div>
                        </div>
                        <div class="mt-1 text-[11px] text-slate-600">
                            Free Product: <span class="font-bold text-slate-800" x-text="sp.free_product_name"></span>
                        </div>
                        <div class="mt-1 text-[11px] text-indigo-700 font-semibold" x-show="sp.suggestion_text" x-text="sp.suggestion_text"></div>
                    </div>
                </template>
            </div>
        </div>

        <div class="mb-6">
            <template x-if="isMobile && isSalesmanPanel">
                <div class="space-y-3">
                    <template x-for="(item, index) in items" :key="item.id">
                        <div class="border border-slate-200 rounded-xl p-3 bg-white">
                            <div class="flex items-start justify-between gap-3">
                                <div class="text-sm font-semibold text-slate-800">Item <span x-text="index + 1"></span></div>
                                <button type="button" @click="removeItem(index)" class="w-9 h-9 inline-flex items-center justify-center rounded-lg text-slate-500 hover:bg-red-50 hover:text-red-600 transition-colors">
                                    <i class="ph ph-trash text-lg"></i>
                                </button>
                            </div>

                            <div class="mt-3">
                                <div class="relative" x-data="{ 
                                    init() {
                                        if(item.product_id) {
                                            let p = products.find(x => x.id == item.product_id);
                                            if(p) this.search = p.name || p.label;
                                        }
                                    },
                                    search: '', 
                                    showResults: false,
                                    dbg(tag, extra = {}) {
                                        try {
                                            window.__traeInvoiceAutocompleteDbg({
                                                tag,
                                                scope: 'product-search',
                                                variant: 'salesman-mobile',
                                                rowIndex: index,
                                                search: String(this.search || ''),
                                                showResults: !!this.showResults,
                                                filteredLen: (this.filteredProducts || []).length,
                                                ...extra,
                                            });
                                        } catch (e) {
                                        }
                                    },
                                    get filteredProducts() {
                                        if (this.search === '') return [];
                                        return products.filter(p => 
                                            p.label.toLowerCase().includes(this.search.toLowerCase())
                                        );
                                    },
                                    selectProduct(product) {
                                        this.search = product.name || product.label;
                                        item.product_id = product.id;
                                        this.showResults = false;
                                        this.dbg('select', { productId: product.id });
                                        onProductSelect(index);
                                    }
                                }" @click.away="showResults = false; dbg('away')">
                                    <input type="text" x-model="search" autocomplete="off" autocorrect="off" spellcheck="false" @focus="showResults = true; dbg('focus')" @input="showResults = true; dbg('input')" placeholder="Search product..." class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:ring-inset focus:outline-none disabled:bg-slate-50 disabled:text-slate-400 transition-all invoice-product-search relative z-10 focus:z-20" :disabled="item.parent_id !== undefined" x-show="item.parent_id === undefined">
                                    <input type="hidden" :name="`items[${index}][product_id]`" x-model="item.product_id">

                                    <div x-show="showResults && filteredProducts.length > 0" class="absolute z-[200] left-0 right-0 w-full mt-1.5 bg-white border border-slate-200 rounded-xl shadow-2xl max-h-72 overflow-y-auto ring-1 ring-black/5 backdrop-blur-sm">
                                        <div class="p-1.5">
                                            <template x-for="product in filteredProducts" :key="product.id">
                                                <button type="button" @click="selectProduct(product)" class="w-full px-4 py-3 text-left text-sm hover:bg-indigo-50 rounded-lg transition-colors border-b border-slate-50 last:border-0 flex flex-col gap-0.5">
                                                    <span class="font-bold text-slate-800 whitespace-normal break-words leading-snug" x-text="product.label"></span>
                                                    <span class="text-[10px] text-slate-400 font-medium uppercase tracking-wider" x-text="'MRP: ₹' + product.mrp + ' | GST: ' + product.gst_rate + '%'"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <template x-if="item.parent_id !== undefined && item.product_id">
                                    <div class="mt-2">
                                        <span class="text-sm font-medium text-slate-700 bg-slate-50 border border-slate-200 rounded-md px-2 py-2 inline-block w-full cursor-not-allowed whitespace-normal break-words leading-snug" x-text="products.find(p => p.id == item.product_id)?.label || 'Product not found'"></span>
                                    </div>
                                </template>

                                <div class="mt-2 text-xs text-slate-500 space-y-1" x-show="item.product_id">
                                    <div class="whitespace-normal break-words" x-text="'MRP: ₹' + item.mrp + ' | GST: ' + item.gst_rate + '% | Pack: ' + item.pack_size"></div>
                                    <div :class="item.stock_warning ? 'text-red-600 font-bold' : 'text-slate-500'" x-text="'Stock: ' + (products.find(p => p.id == item.product_id)?.stock || 0)"></div>
                                    <div x-show="item.stock_warning" class="text-red-600 font-bold animate-pulse">Insufficient quantity!</div>
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">CT</label>
                                    <input type="number" inputmode="numeric" x-model="item.qty_ct" :name="`items[${index}][qty_ct]`" min="0" step="1" @input="calculateLine(index)" class="w-full px-3 py-3 border border-slate-200 rounded-lg text-base focus:ring-2 focus:ring-indigo-500 outline-none text-center sa-no-spin" :readonly="item.is_free" @if(!$isSalesmanPanel) onwheel="this.blur()" @endif>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">UN</label>
                                    <input type="number" inputmode="numeric" x-model="item.qty_un" :name="`items[${index}][qty_un]`" min="0" step="1" @input="calculateLine(index)" class="w-full px-3 py-3 border border-slate-200 rounded-lg text-base focus:ring-2 focus:ring-indigo-500 outline-none text-center sa-no-spin" :readonly="item.is_free" @if(!$isSalesmanPanel) onwheel="this.blur()" @endif>
                                </div>
                            </div>

                            <input type="hidden" :name="`items[${index}][discount_pct]`" value="0">
                            <input type="hidden" :name="`items[${index}][is_free]`" :value="item.is_free ? '1' : '0'">
                            <input type="hidden" :name="`items[${index}][parent_id]`" :value="item.parent_id">

                            <div class="mt-4 grid grid-cols-3 gap-2">
                                <div class="bg-slate-50 border border-slate-200 rounded-lg p-2">
                                    <div class="text-[11px] font-semibold text-slate-600">Taxable</div>
                                    <div class="text-sm font-bold text-slate-800" x-text="formatCurrency(item.taxable)"></div>
                                </div>
                                <div class="bg-slate-50 border border-slate-200 rounded-lg p-2">
                                    <div class="text-[11px] font-semibold text-slate-600">GST</div>
                                    <div class="text-sm font-bold text-slate-800" x-text="formatCurrency(item.tax_amount)"></div>
                                </div>
                                <div class="bg-indigo-50 border border-indigo-100 rounded-lg p-2">
                                    <div class="text-[11px] font-semibold text-indigo-700">Amount</div>
                                    <div class="text-sm font-bold text-indigo-700" x-text="formatCurrency(item.line_total)"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <template x-if="!isMobile || !isSalesmanPanel">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap min-w-[1000px]">
                        <thead class="bg-slate-50 text-slate-500 border-y border-slate-200">
                            <tr>
                                <th class="px-4 py-3 font-semibold w-12">#</th>
                                <th class="px-4 py-3 font-semibold min-w-[250px]">Product</th>
                                <th class="px-4 py-3 font-semibold w-24">CT</th>
                                <th class="px-4 py-3 font-semibold w-24">UN</th>
                                <th class="px-4 py-3 font-semibold text-right w-32">Taxable</th>
                                <th class="px-4 py-3 font-semibold text-right w-32">GST</th>
                                <th class="px-4 py-3 font-semibold text-right w-32">Amount</th>
                                <th class="px-4 py-3 font-semibold w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(item, index) in items" :key="item.id">
                                <tr class="hover:bg-slate-50/50 transition-colors relative" :class="item.showResults ? 'z-[100]' : 'z-0'">
                                    <td class="px-4 py-3 text-slate-500" x-text="index + 1"></td>
                                    <td class="px-4 py-3">
                                        @if(!$isSalesmanPanel)
                                        <div class="relative" x-data="{ 
                                            init() {
                                                if(item.product_id) {
                                                    let p = products.find(x => x.id == item.product_id);
                                                    if(p) this.search = p.name || p.label;
                                                }
                                                this.$watch('showResults', value => {
                                                    item.showResults = value;
                                                    if (!value) {
                                                        this.dropdownStyle = '';
                                                    }
                                                });
                                                this._repositionHandler = () => { if (this.showResults) this.reposition(); };
                                                window.addEventListener('scroll', this._repositionHandler, true);
                                                window.addEventListener('resize', this._repositionHandler);
                                            },
                                            search: '', 
                                            showResults: false,
                                            dropdownStyle: '',
                                            dbg(tag, extra = {}) {
                                                try {
                                                    window.__traeInvoiceAutocompleteDbg({
                                                        tag,
                                                        scope: 'product-search',
                                                        variant: 'admin-desktop',
                                                        rowIndex: index,
                                                        search: String(this.search || ''),
                                                        showResults: !!this.showResults,
                                                        filteredLen: (this.filteredProducts || []).length,
                                                        ...extra,
                                                    });
                                                } catch (e) {
                                                }
                                            },
                                            reposition() {
                                                const el = this.$refs.input;
                                                if (!el) return;
                                                const r = el.getBoundingClientRect();
                                                const spaceBelow = window.innerHeight - r.bottom;
                                                const spaceAbove = r.top;
                                                const openUp = spaceBelow < 240 && spaceAbove > spaceBelow;
                                                const maxH = Math.max(180, Math.min(420, (openUp ? (spaceAbove - 12) : (spaceBelow - 12))));
                                                const width = Math.max(r.width, 360);
                                                const left = Math.max(8, Math.min(r.left, window.innerWidth - width - 8));
                                                const top = openUp ? Math.max(8, r.top - maxH - 6) : (r.bottom + 6);
                                                this.dropdownStyle = `left:${left}px;top:${top}px;width:${width}px;max-height:${maxH}px;`;
                                            },
                                            get filteredProducts() {
                                                if (this.search === '') return [];
                                                return products.filter(p => 
                                                    p.label.toLowerCase().includes(this.search.toLowerCase())
                                                );
                                            },
                                            selectProduct(product) {
                                                this.search = product.name || product.label;
                                                item.product_id = product.id;
                                                this.showResults = false;
                                                this.dropdownStyle = '';
                                                this.dbg('select', { productId: product.id });
                                                onProductSelect(index);
                                            }
                                        }" @click.away="showResults = false">
                                            <input type="text" x-ref="input" x-model="search" autocomplete="off" autocorrect="off" spellcheck="false" @focus="showResults = true; reposition(); dbg('focus')" @input="showResults = true; reposition(); dbg('input')" placeholder="Search product..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:ring-inset focus:outline-none disabled:bg-slate-50 disabled:text-slate-400 transition-all invoice-product-search relative z-10 focus:z-20" :disabled="item.parent_id !== undefined" x-show="item.parent_id === undefined">
                                            <input type="hidden" :name="`items[${index}][product_id]`" x-model="item.product_id">
                                            
                                            <template x-if="showResults && filteredProducts.length > 0">
                                                <template x-teleport="body">
                                                    <div :style="dropdownStyle" class="fixed z-[9999] bg-white border border-slate-200 rounded-xl shadow-2xl overflow-y-auto overscroll-contain ring-1 ring-black/5">
                                                        <div class="p-1.5">
                                                            <template x-for="product in filteredProducts" :key="product.id">
                                                                <button type="button" @click="selectProduct(product)" class="w-full px-4 py-3 text-left text-sm hover:bg-indigo-50 rounded-lg transition-colors border-b border-slate-50 last:border-0 flex flex-col gap-1">
                                                                    <span class="font-bold text-slate-800 whitespace-normal break-words leading-snug" x-text="product.label"></span>
                                                                    <span class="text-[10px] text-slate-400 font-medium uppercase tracking-wider whitespace-normal break-words" x-text="'MRP: ₹' + product.mrp + ' | GST: ' + product.gst_rate + '%'"></span>
                                                                </button>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>
                                            </template>
                                        </div>
                                        @else
                                        <div class="relative" x-data="{ 
                                            init() {
                                                if(item.product_id) {
                                                    let p = products.find(x => x.id == item.product_id);
                                                    if(p) this.search = p.name || p.label;
                                                }
                                                this.$watch('showResults', value => {
                                                    item.showResults = value;
                                                    if (!value) {
                                                        this.dropdownStyle = '';
                                                    }
                                                });
                                                this._repositionHandler = () => { if (this.showResults) this.reposition(); };
                                                window.addEventListener('scroll', this._repositionHandler, true);
                                                window.addEventListener('resize', this._repositionHandler);
                                            },
                                            search: '', 
                                            showResults: false,
                                            dropdownStyle: '',
                                            dbg(tag, extra = {}) {
                                                try {
                                                    window.__traeInvoiceAutocompleteDbg({
                                                        tag,
                                                        scope: 'product-search',
                                                        variant: 'salesman-desktop',
                                                        rowIndex: index,
                                                        search: String(this.search || ''),
                                                        showResults: !!this.showResults,
                                                        filteredLen: (this.filteredProducts || []).length,
                                                        ...extra,
                                                    });
                                                } catch (e) {
                                                }
                                            },
                                            reposition() {
                                                const el = this.$refs.input;
                                                if (!el) return;
                                                const r = el.getBoundingClientRect();
                                                const spaceBelow = window.innerHeight - r.bottom;
                                                const spaceAbove = r.top;
                                                const openUp = spaceBelow < 240 && spaceAbove > spaceBelow;
                                                const maxH = Math.max(180, Math.min(420, (openUp ? (spaceAbove - 12) : (spaceBelow - 12))));
                                                const width = Math.max(r.width, 360);
                                                const left = Math.max(8, Math.min(r.left, window.innerWidth - width - 8));
                                                const top = openUp ? Math.max(8, r.top - maxH - 6) : (r.bottom + 6);
                                                this.dropdownStyle = `left:${left}px;top:${top}px;width:${width}px;max-height:${maxH}px;`;
                                            },
                                            get filteredProducts() {
                                                if (this.search === '') return [];
                                                return products.filter(p => 
                                                    p.label.toLowerCase().includes(this.search.toLowerCase())
                                                );
                                            },
                                            selectProduct(product) {
                                                this.search = product.name || product.label;
                                                item.product_id = product.id;
                                                this.showResults = false;
                                                this.dropdownStyle = '';
                                                this.dbg('select', { productId: product.id });
                                                onProductSelect(index);
                                            }
                                        }" @click.away="showResults = false">
                                            <input type="text" x-ref="input" x-model="search" autocomplete="off" autocorrect="off" spellcheck="false" @focus="showResults = true; reposition(); dbg('focus')" @input="showResults = true; reposition(); dbg('input')" placeholder="Search product..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:ring-inset focus:outline-none disabled:bg-slate-50 disabled:text-slate-400 transition-all invoice-product-search relative z-10 focus:z-20" :disabled="item.parent_id !== undefined" x-show="item.parent_id === undefined">
                                            <input type="hidden" :name="`items[${index}][product_id]`" x-model="item.product_id">
                                            
                                            <template x-if="showResults && filteredProducts.length > 0">
                                                <template x-teleport="body">
                                                    <div :style="dropdownStyle" class="fixed z-[9999] bg-white border border-slate-200 rounded-xl shadow-2xl overflow-y-auto overscroll-contain ring-1 ring-black/5">
                                                        <div class="p-1.5">
                                                            <template x-for="product in filteredProducts" :key="product.id">
                                                                <button type="button" @click="selectProduct(product)" class="w-full px-4 py-3 text-left text-sm hover:bg-indigo-50 rounded-lg transition-colors border-b border-slate-50 last:border-0 flex flex-col gap-0.5">
                                                                    <span class="font-bold text-slate-800" x-text="product.label"></span>
                                                                    <span class="text-[10px] text-slate-400 font-medium uppercase tracking-wider" x-text="'MRP: ₹' + product.mrp + ' | GST: ' + product.gst_rate + '%'"></span>
                                                                </button>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>
                                            </template>
                                        </div>
                                        @endif
                                        
                                        <template x-if="item.parent_id !== undefined && item.product_id">
                                            <div>
                                                <span class="text-sm font-medium text-slate-700 bg-slate-50 border border-slate-200 rounded-md px-2 py-1.5 inline-block w-full cursor-not-allowed" x-text="products.find(p => p.id == item.product_id)?.label || 'Product not found'"></span>
                                            </div>
                                        </template>
                                        <div class="text-[10px] mt-1 flex justify-between items-center">
                                            <div class="text-slate-400" x-show="item.product_id" x-text="'MRP: ₹' + item.mrp + ' | GST: ' + item.gst_rate + '% | Pack: ' + item.pack_size"></div>
                                            <div x-show="item.product_id" :class="item.stock_warning ? 'text-red-500 font-bold' : 'text-slate-400'" x-text="'Stock: ' + (products.find(p => p.id == item.product_id)?.stock || 0)"></div>
                                        </div>
                                        <div x-show="item.stock_warning" class="text-[10px] text-red-600 font-bold mt-0.5 animate-pulse">
                                            Insufficient quantity!
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" x-model="item.qty_ct" :name="`items[${index}][qty_ct]`" min="0" step="1" @input="calculateLine(index)" class="w-full px-2 py-1.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none text-center sa-no-spin" :readonly="item.is_free" @if(!$isSalesmanPanel) onwheel="this.blur()" @endif>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" x-model="item.qty_un" :name="`items[${index}][qty_un]`" min="0" step="1" @input="calculateLine(index)" class="w-full px-2 py-1.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none text-center sa-no-spin" :readonly="item.is_free" @if(!$isSalesmanPanel) onwheel="this.blur()" @endif>
                                    </td>
                                    <input type="hidden" :name="`items[${index}][discount_pct]`" value="0">
                                    <input type="hidden" :name="`items[${index}][is_free]`" :value="item.is_free ? '1' : '0'">
                                    <input type="hidden" :name="`items[${index}][parent_id]`" :value="item.parent_id">
                                    <td class="px-4 py-3 text-right font-medium text-slate-700" x-text="formatCurrency(item.taxable)"></td>
                                    <td class="px-4 py-3 text-right text-slate-500">
                                        <div x-text="formatCurrency(item.tax_amount)"></div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-slate-800" x-text="formatCurrency(item.line_total)"></td>
                                    <td class="px-4 py-3 text-center">
                                        <button type="button" @click="removeItem(index)" class="w-7 h-7 inline-flex items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 transition-colors">
                                            <i class="ph ph-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>

        <button type="button" @click="addItem()" class="{{ $isSalesmanPanel ? 'w-full sm:w-auto justify-center mb-8 text-sm font-medium text-indigo-600 hover:text-indigo-700 inline-flex items-center gap-1.5 transition-colors bg-indigo-50 hover:bg-indigo-100 px-3 py-2 rounded-lg' : 'mb-8 text-sm font-medium text-indigo-600 hover:text-indigo-700 flex items-center gap-1.5 transition-colors bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg' }}">
            <i class="ph ph-plus"></i> Add Item Row
        </button>

        <!-- Totals Section -->
        <div class="flex flex-col md:flex-row justify-end border-t border-slate-100 pt-6">
            <div class="w-full md:w-80 space-y-3">
                <div class="flex justify-between text-sm text-slate-600">
                    <span>Taxable Amount:</span>
                    <span class="font-medium" x-text="formatCurrency(totals.taxable)"></span>
                </div>
                <div class="flex justify-between text-sm text-slate-600" x-show="taxType !== 'IGST'">
                    <span>Total CGST:</span>
                    <span class="font-medium" x-text="formatCurrency(totals.cgst)"></span>
                </div>
                <div class="flex justify-between text-sm text-slate-600" x-show="taxType !== 'IGST'">
                    <span x-text="taxType === 'CGST_UTGST' ? 'Total UTGST:' : 'Total SGST:'"></span>
                    <span class="font-medium" x-text="formatCurrency(totals.sgst)"></span>
                </div>
                <div class="flex justify-between text-sm text-slate-600" x-show="taxType === 'IGST'">
                    <span>Total IGST:</span>
                    <span class="font-medium" x-text="formatCurrency(totals.igst)"></span>
                </div>
                
                <div class="flex justify-between text-sm text-slate-600">
                    <span>Round Off:</span>
                    <span class="font-medium" x-text="formatCurrency(totals.round_off)"></span>
                </div>
                <div class="pt-3 border-t border-slate-200 flex justify-between items-center">
                    <span class="font-syne font-bold text-slate-800">Grand Total:</span>
                    <span class="font-syne font-bold text-xl text-indigo-600" x-text="formatCurrency(totals.grand_total)"></span>
                </div>
                <div class="pt-3 border-t border-slate-200 space-y-3">
                    <div class="flex items-center justify-between gap-4">
                        <label class="text-sm text-slate-600 font-medium">Paid Amount:</label>
                        <input type="number" step="0.000001" min="0" name="paid_amount" x-model="paidAmount" @input="autoPaid = false" class="w-40 px-3 py-2 border border-slate-200 rounded-lg text-sm text-right focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div class="flex justify-between text-sm text-slate-600">
                        <span>Pending Amount:</span>
                        <span class="font-semibold text-slate-800" x-text="formatCurrency(pendingAmount())"></span>
                    </div>
                    <div class="flex justify-between text-xs font-semibold">
                        <span class="text-slate-500">Payment Status:</span>
                        <span :class="paymentStatus() === 'Paid' ? 'text-emerald-700' : (paymentStatus() === 'Partial' ? 'text-amber-700' : 'text-red-700')" x-text="paymentStatus()"></span>
                    </div>
                </div>
            </div>
        </div>

        </div>
    </form>
</div>

@php
    $invoiceFormCustomers = ($customers ?? collect())->map(function ($customer) {
        return [
            'id' => (string) ($customer->id ?? ''),
            'name' => (string) ($customer->name ?? ''),
            'gstin' => (string) ($customer->gstin ?? 'No GSTIN'),
            'tax' => (string) ($customer->tax_type ?? ''),
            'credit_limit' => ($customer->credit_limit === null || $customer->credit_limit === '') ? null : (float) $customer->credit_limit,
            'outstanding_amount' => (float) ($customer->outstanding_amount ?? 0),
        ];
    })->values()->all();

    $invoiceFormProducts = ($products ?? collect())->map(function ($p) {
        $label = trim((string) (($p->name ?? '') . ' ' . ($p->volume ?? '')));
        if ($label === '') {
            $label = trim((string) ($p->name ?? ''));
        }
        return [
            'id' => (string) ($p->id ?? ''),
            'name' => (string) ($p->name ?? ''),
            'label' => $label,
            'category' => (string) ($p->category ?? ''),
            'base_price' => (float) ($p->base_price ?? 0),
            'mrp' => (float) ($p->mrp ?? 0),
            'gst_rate' => (float) ($p->gst_rate ?? 0),
            'pack_size' => (int) ($p->pack_size ?? 1),
            'stock' => (float) ($p->current_stock_units ?? 0),
        ];
    })->values()->all();

    $invoiceFormSchemes = ($schemes ?? collect())->map(function ($s) {
        return [
            'id' => (int) ($s->id ?? 0),
            'name' => (string) ($s->name ?? ''),
            'product_id' => (string) ($s->product_id ?? ''),
            'slab_unit' => (string) ($s->slab_unit ?? ''),
            'slabs' => ($s->schemeSlabs ?? collect())->map(function ($slab) {
                return [
                    'min_qty' => (float) ($slab->min_qty ?? 0),
                    'max_qty' => ($slab->max_qty === null || $slab->max_qty === '') ? null : (float) $slab->max_qty,
                    'free_qty' => (float) ($slab->free_qty ?? 0),
                    'free_product_id' => ($slab->free_product_id === null || $slab->free_product_id === '') ? null : (string) $slab->free_product_id,
                ];
            })->values()->all(),
        ];
    })->values()->all();

    $invoiceFormProductGroupsMap = ($productGroupsPairs ?? collect())->groupBy('product_id')->map(function ($rows) {
        return $rows->pluck('product_group_id')->values()->all();
    })->all();

    $invoiceFormGroupSchemes = ($groupSchemes ?? collect())->map(function ($gs) {
        return [
            'id' => (int) ($gs->id ?? 0),
            'name' => (string) ($gs->name ?? ''),
            'product_group_id' => (int) ($gs->product_group_id ?? 0),
            'free_product_id' => (int) ($gs->free_product_id ?? 0),
            'is_active' => (bool) ($gs->is_active ?? false),
            'slabs' => ($gs->slabs ?? collect())->map(function ($sl) {
                return [
                    'min_qty_ct' => (int) ($sl->min_qty_ct ?? 0),
                    'max_qty_ct' => (int) ($sl->max_qty_ct ?? 0),
                    'free_qty_units' => (int) ($sl->free_qty_units ?? 0),
                ];
            })->values()->all(),
        ];
    })->values()->all();

    $invoiceFormOldItems = old('items') ? array_values((array) old('items')) : null;
    $invoiceFormSelectedCustomer = (string) old('customer_id', '');
    $invoiceFormTodayStr = (string) date('Y-m-d');
    $invoiceFormInvoiceDate = (string) old('invoice_date', $invoiceFormTodayStr);
    $invoiceFormDueDate = (string) old('due_date', '');
    $invoiceFormPaymentType = (string) old('payment_type', 'Cash');
    $invoiceFormPaidAmount = old('paid_amount', '');
    $invoiceFormAutoPaid = old('paid_amount', null) === null;

    $jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
@endphp

<div id="invoice-form-seed"
    class="hidden"
    data-customers='{!! json_encode($invoiceFormCustomers, $jsonFlags) !!}'
    data-products='{!! json_encode($invoiceFormProducts, $jsonFlags) !!}'
    data-schemes='{!! json_encode($invoiceFormSchemes, $jsonFlags) !!}'
    data-product-groups-map='{!! json_encode($invoiceFormProductGroupsMap, $jsonFlags) !!}'
    data-group-schemes='{!! json_encode($invoiceFormGroupSchemes, $jsonFlags) !!}'
    data-old-items='{!! json_encode($invoiceFormOldItems, $jsonFlags) !!}'
    data-selected-customer='{!! json_encode($invoiceFormSelectedCustomer, $jsonFlags) !!}'
    data-today-str='{!! json_encode($invoiceFormTodayStr, $jsonFlags) !!}'
    data-invoice-date='{!! json_encode($invoiceFormInvoiceDate, $jsonFlags) !!}'
    data-due-date='{!! json_encode($invoiceFormDueDate, $jsonFlags) !!}'
    data-payment-type='{!! json_encode($invoiceFormPaymentType, $jsonFlags) !!}'
    data-credit-limit-policy='{!! json_encode((string) ($creditLimitPolicy ?? 'warn'), $jsonFlags) !!}'
    data-paid-amount='{!! json_encode($invoiceFormPaidAmount, $jsonFlags) !!}'
    data-auto-paid='{!! json_encode($invoiceFormAutoPaid, $jsonFlags) !!}'
    data-is-salesman-panel='{!! json_encode($isSalesmanPanel, $jsonFlags) !!}'
    data-debug-url="{{ $dbgInvoiceAutocompleteUrl ?? '' }}"
    data-debug-session="{{ $dbgInvoiceAutocompleteSession }}"></div>

<script>
    document.addEventListener('alpine:init', () => {
        //#region debug-point salesman-qty-errors
        const __traeIsSalesmanMobile = () => {
            try {
                const seed = document.getElementById('invoice-form-seed');
                const raw = seed && seed.dataset ? seed.dataset.isSalesmanPanel : null;
                const isSalesmanPanel = raw ? JSON.parse(raw) : false;
                return !!isSalesmanPanel && window.innerWidth < 640;
            } catch (e) {
                return false;
            }
        };

        window.addEventListener('error', (e) => {
            try {
                if (!__traeIsSalesmanMobile()) return;
                window.__traeInvoiceAutocompleteDbg({
                    sessionId: 'salesman-qty-increment',
                    scope: 'qty',
                    tag: 'window.error',
                    message: String(e && e.message ? e.message : ''),
                    filename: String(e && e.filename ? e.filename : ''),
                    lineno: (e && typeof e.lineno === 'number') ? e.lineno : null,
                    colno: (e && typeof e.colno === 'number') ? e.colno : null,
                    stack: e && e.error && e.error.stack ? String(e.error.stack) : null,
                });
            } catch (err) {
            }
        });

        window.addEventListener('unhandledrejection', (e) => {
            try {
                if (!__traeIsSalesmanMobile()) return;
                const r = e && e.reason ? e.reason : null;
                window.__traeInvoiceAutocompleteDbg({
                    sessionId: 'salesman-qty-increment',
                    scope: 'qty',
                    tag: 'window.unhandledrejection',
                    message: r && r.message ? String(r.message) : String(r || ''),
                    stack: r && r.stack ? String(r.stack) : null,
                });
            } catch (err) {
            }
        });
        //#endregion debug-point salesman-qty-errors

        //#region debug-point invoice-autocomplete-transport
        window.__traeInvoiceAutocompleteDbg = (payload) => {
            try {
                const seed = document.getElementById('invoice-form-seed');
                const url = seed && seed.dataset ? (seed.dataset.debugUrl || '') : '';
                const sessionId = seed && seed.dataset ? (seed.dataset.debugSession || '') : '';
                if (!url) return;
                const csrf = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrf ? (csrf.getAttribute('content') || '') : '';
                const body = JSON.stringify({
                    ts: Date.now(),
                    sessionId,
                    page: 'invoice-create',
                    path: window.location.pathname,
                    ...payload,
                });
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                    },
                    body,
                    keepalive: true,
                }).catch(() => {});
            } catch (e) {
            }
        };
        //#endregion debug-point invoice-autocomplete-transport

        //#region debug-point salesman-save-dup
        window.__traeInvoiceSaveDbg = (payload) => {
            try {
                window.__traeInvoiceAutocompleteDbg({
                    sessionId: 'salesman-save-dup',
                    scope: 'invoice-save',
                    ...payload,
                });
            } catch (e) {
            }
        };

        try {
            if (!window.__traeInvoiceSaveDbgSubmitPatched) {
                window.__traeInvoiceSaveDbgSubmitPatched = true;
                const origSubmit = HTMLFormElement.prototype.submit;
                HTMLFormElement.prototype.submit = function () {
                    try {
                        if (this && this.id === 'invoiceForm') {
                            window.__traeInvoiceSaveDbg({
                                tag: 'form.submit()',
                                ts: Date.now(),
                                action: this.action || null,
                                method: (this.method || '').toUpperCase(),
                                stack: (new Error('submit')).stack || null,
                            });
                        }
                    } catch (e) {
                    }
                    return origSubmit.call(this);
                };
            }
        } catch (e) {
        }

        window.addEventListener('pageshow', (e) => {
            try {
                window.__traeInvoiceSaveDbg({
                    tag: 'window.pageshow',
                    ts: Date.now(),
                    persisted: !!(e && e.persisted),
                    path: window.location.pathname,
                });
            } catch (err) {
            }
        });
        //#endregion debug-point salesman-save-dup

        Alpine.data('invoiceForm', () => {
            const seedEl = document.getElementById('invoice-form-seed');
            const seedGet = (key, fallback) => {
                try {
                    if (!seedEl || !seedEl.dataset) return fallback;
                    const raw = seedEl.dataset[key];
                    if (raw === undefined || raw === null || raw === '') return fallback;
                    return JSON.parse(raw);
                } catch (e) {
                    return fallback;
                }
            };

            const todayStr = seedGet('todayStr', '');

            return {
                customers: seedGet('customers', []),
                products: seedGet('products', []),
                schemes: seedGet('schemes', []),
                productGroupsMap: seedGet('productGroupsMap', {}),
                groupSchemes: seedGet('groupSchemes', []),
                oldItems: seedGet('oldItems', null),
                selectedCustomer: seedGet('selectedCustomer', ''),
                todayStr: todayStr,
                invoiceDate: seedGet('invoiceDate', todayStr),
                dueDate: seedGet('dueDate', ''),
                paymentType: seedGet('paymentType', 'Cash'),
                creditLimitPolicy: seedGet('creditLimitPolicy', 'warn'),
                paidAmount: seedGet('paidAmount', ''),
                autoPaid: seedGet('autoPaid', true),
                isSalesmanPanel: seedGet('isSalesmanPanel', false),
                isMobile: window.innerWidth < 640,
                flowStep: null,
                customerQuery: '',
                productQuery: '',
                selectedCategory: 'All',
                reviewEditProductId: '',
                flowErrors: [],
                stockWarnings: {},
                recentCustomerIds: [],
                taxType: 'CGST_UTGST',
                items: [],
                schemePreview: [],
                totals: {
                    taxable: 0,
                    cgst: 0,
                    sgst: 0,
                    igst: 0,
                    round_off: 0,
                    grand_total: 0
                },

                init() {
                    this.isMobile = window.innerWidth < 640;
                    window.addEventListener('resize', () => {
                        this.isMobile = window.innerWidth < 640;
                    });

                    this.flowStep = (this.isSalesmanPanel && this.isMobile) ? (this.selectedCustomer ? 'products' : 'customer') : null;
                    this.recentCustomerIds = this.loadRecentCustomerIds();

                    this.updateTaxType();
                    this.onInvoiceDateChange();

                    if (Array.isArray(this.oldItems) && this.oldItems.length > 0) {
                        const oldItems = this.oldItems;
                        this.items = oldItems.map((item, index) => {
                            return {
                                id: item.id || (Date.now() + index),
                                product_id: item.product_id ? String(item.product_id) : '',
                                qty_ct: item.qty_ct !== null ? item.qty_ct : '',
                                qty_un: item.qty_un !== null ? item.qty_un : '',
                                discount_pct: item.discount_pct || 0,
                                is_free: item.is_free == 1 || item.is_free === true || item.is_free === 'true',
                                parent_id: item.parent_id || undefined,
                                showResults: false,
                                base_price: 0, mrp: 0, gst_rate: 0, pack_size: 1,
                                taxable: 0, tax_amount: 0, line_total: 0
                            };
                        });

                        this.items.forEach((item, index) => {
                            if(item.product_id) {
                                let p = this.products.find(x => x.id == item.product_id);
                                if(p) {
                                    item.base_price = p.base_price;
                                    item.mrp = p.mrp;
                                    item.gst_rate = p.gst_rate;
                                    item.pack_size = p.pack_size;
                                }
                            }
                            this.calculateLine(index, true);
                        });
                    } else {
                        if (!(this.isSalesmanPanel && this.isMobile)) {
                            this.addItem();
                        }
                    }

                    if (String(this.paidAmount || '').trim() === '') {
                        this.autoPaid = this.paymentType !== 'Credit';
                        this.paidAmount = this.paymentType === 'Credit' ? 0 : this.totals.grand_total;
                    }

                    this.$watch('totals.grand_total', (v) => {
                        if (this.paymentType !== 'Credit' && this.autoPaid) {
                            this.paidAmount = v;
                        }
                    });
                },

                //#region debug-point invoice-autocomplete-parent
                dbgInvoiceAutocomplete(tag, extra = {}) {
                    try {
                        window.__traeInvoiceAutocompleteDbg({
                            tag,
                            scope: 'invoiceForm',
                            isSalesmanPanel: !!this.isSalesmanPanel,
                            isMobile: !!this.isMobile,
                            itemsLen: Array.isArray(this.items) ? this.items.length : null,
                            ...extra,
                        });
                    } catch (e) {
                    }
                },
                //#endregion debug-point invoice-autocomplete-parent

                //#region debug-point salesman-qty
                dbgQty(tag, extra = {}) {
                    try {
                        if (!this.isSalesmanPanel || !this.isMobile) return;
                        const ae = document.activeElement;
                        window.__traeInvoiceAutocompleteDbg({
                            sessionId: 'salesman-qty-increment',
                            tag,
                            scope: 'qty',
                            flowStep: String(this.flowStep || ''),
                            activeTag: ae ? String(ae.tagName || '') : null,
                            activeType: ae && ae.getAttribute ? (ae.getAttribute('type') || null) : null,
                            activeValue: ae && 'value' in ae ? String(ae.value ?? '') : null,
                            ...extra,
                        });
                    } catch (e) {
                    }
                },
                //#endregion debug-point salesman-qty

                //#region debug-point salesman-save-dup
                dbgSave(tag, extra = {}) {
                    try {
                        if (!this.isSalesmanPanel || !this.isMobile) return;
                        const ae = document.activeElement;
                        window.__traeInvoiceSaveDbg({
                            tag,
                            ts: Date.now(),
                            flowStep: String(this.flowStep || ''),
                            selectedCustomer: String(this.selectedCustomer || ''),
                            itemsLen: Array.isArray(this.items) ? this.items.length : null,
                            baseItemsLen: (() => { try { return this.baseItems().length; } catch (e) { return null; } })(),
                            paymentType: String(this.paymentType || ''),
                            activeTag: ae ? String(ae.tagName || '') : null,
                            activeType: ae && ae.getAttribute ? (ae.getAttribute('type') || null) : null,
                            ...extra,
                        });
                    } catch (e) {
                    }
                },
                //#endregion debug-point salesman-save-dup

            loadRecentCustomerIds() {
                try {
                    const raw = window.localStorage.getItem('salesman_recent_customers');
                    const parsed = JSON.parse(raw || '[]');
                    if (!Array.isArray(parsed)) return [];
                    return parsed.map(x => String(x)).filter(Boolean).slice(0, 10);
                } catch (e) {
                    return [];
                }
            },

            saveRecentCustomerId(id) {
                const sid = String(id || '');
                if (!sid) return;
                const next = [sid, ...this.recentCustomerIds.filter(x => x !== sid)].slice(0, 10);
                this.recentCustomerIds = next;
                try {
                    window.localStorage.setItem('salesman_recent_customers', JSON.stringify(next));
                } catch (e) {
                }
            },

            selectedCustomerName() {
                const c = this.customers.find(x => x.id == this.selectedCustomer);
                return c ? c.name : '';
            },

            get flowCustomers() {
                const q = String(this.customerQuery || '').trim().toLowerCase();
                const list = this.customers
                    .filter(c => {
                        if (!q) return true;
                        return String(c.name || '').toLowerCase().includes(q);
                    })
                    .map(c => ({ ...c, _recentIndex: this.recentCustomerIds.indexOf(String(c.id)) }));

                list.sort((a, b) => {
                    const ar = a._recentIndex;
                    const br = b._recentIndex;
                    if (ar !== -1 && br === -1) return -1;
                    if (ar === -1 && br !== -1) return 1;
                    if (ar !== -1 && br !== -1) return ar - br;
                    return String(a.name || '').localeCompare(String(b.name || ''));
                });
                return list;
            },

            get flowCategories() {
                const set = new Set();
                this.products.forEach(p => {
                    const c = String(p.category || '').trim();
                    if (c) set.add(c);
                });
                return Array.from(set).sort((a, b) => a.localeCompare(b));
            },

            get flowProducts() {
                const q = String(this.productQuery || '').trim().toLowerCase();
                const cat = String(this.selectedCategory || 'All');
                return this.products.filter(p => {
                    if (q) {
                        return String(p.label || '').toLowerCase().includes(q);
                    }
                    if (cat === 'All') return true;
                    return String(p.category || '') === cat;
                });
            },

            flowSelectCustomer(customerId) {
                this.flowErrors = [];
                this.stockWarnings = {};
                this.selectedCustomer = customerId;
                this.updateTaxType();
                this.saveRecentCustomerId(customerId);
                this.flowStep = 'products';
            },

            flowItemIndex(productId) {
                const pid = String(productId || '');
                return this.items.findIndex(i => !i.is_free && String(i.product_id || '') === pid);
            },

            flowEnsureItem(productId) {
                const pid = String(productId || '');
                let idx = this.flowItemIndex(pid);
                if (idx !== -1) return idx;

                this.items.push({
                    id: Date.now() + Math.random().toString(36).substr(2, 9),
                    product_id: pid,
                    qty_ct: 0,
                    qty_un: 0,
                    discount_pct: 0,
                    is_free: false,
                    base_price: 0,
                    mrp: 0,
                    gst_rate: 0,
                    pack_size: 1,
                    taxable: 0,
                    tax_amount: 0,
                    line_total: 0,
                    stock_warning: false
                });
                idx = this.items.length - 1;
                this.onProductSelect(idx);
                return idx;
            },

            flowQtyCt(productId) {
                const idx = this.flowItemIndex(productId);
                if (idx === -1) return 0;
                const n = parseFloat(this.items[idx].qty_ct);
                return Number.isFinite(n) ? Math.trunc(n) : 0;
            },

            flowQtyUn(productId) {
                const idx = this.flowItemIndex(productId);
                if (idx === -1) return 0;
                const n = parseFloat(this.items[idx].qty_un);
                return Number.isFinite(n) ? Math.trunc(n) : 0;
            },

            flowInc(productId, kind, delta) {
                this.flowErrors = [];
                const idx = this.flowEnsureItem(productId);
                const item = this.items[idx];
                const d = Math.trunc(parseFloat(delta) || 0);
                this.dbgQty('flowInc:start', {
                    productId: String(productId || ''),
                    kind: String(kind || ''),
                    delta: d,
                    idx,
                    qty_ct: item ? item.qty_ct : null,
                    qty_un: item ? item.qty_un : null,
                });
                if (kind === 'ct') {
                    if (d > 0 && this.wouldExceedStock(productId, 'ct', 1)) {
                        this.dbgQty('flowInc:blocked', { productId: String(productId || ''), kind: 'ct', reason: 'stock' });
                        return;
                    }
                    const cur = Math.trunc(parseFloat(item.qty_ct) || 0);
                    const next = cur + d;
                    item.qty_ct = next < 0 ? 0 : next;
                } else {
                    if (d > 0 && this.wouldExceedStock(productId, 'un', 1)) {
                        this.dbgQty('flowInc:blocked', { productId: String(productId || ''), kind: 'un', reason: 'stock' });
                        return;
                    }
                    const cur = Math.trunc(parseFloat(item.qty_un) || 0);
                    const next = cur + d;
                    item.qty_un = next < 0 ? 0 : next;
                }

                if ((parseFloat(item.qty_ct) || 0) <= 0 && (parseFloat(item.qty_un) || 0) <= 0) {
                    this.dbgQty('flowInc:remove', {
                        productId: String(productId || ''),
                        idx,
                        qty_ct: item ? item.qty_ct : null,
                        qty_un: item ? item.qty_un : null,
                    });
                    this.removeItem(idx);
                    return;
                }
                this.calculateLine(idx);
                this.refreshStockWarning(productId);
                this.dbgQty('flowInc:end', {
                    productId: String(productId || ''),
                    kind: String(kind || ''),
                    idx,
                    qty_ct: item ? item.qty_ct : null,
                    qty_un: item ? item.qty_un : null,
                });
            },

            flowSetQty(productId, kind, rawValue) {
                this.flowErrors = [];
                const pid = String(productId || '');
                const idx = this.flowEnsureItem(pid);
                const item = this.items[idx];
                const v = Math.max(0, Math.trunc(parseFloat(rawValue || 0) || 0));
                this.dbgQty('flowSetQty', {
                    productId: pid,
                    kind: String(kind || ''),
                    rawValue: rawValue === undefined ? null : String(rawValue),
                    normalized: v,
                    idx,
                });

                if (kind === 'ct') {
                    item.qty_ct = v;
                } else {
                    item.qty_un = v;
                }

                if ((parseFloat(item.qty_ct) || 0) <= 0 && (parseFloat(item.qty_un) || 0) <= 0) {
                    this.removeItem(idx);
                    delete this.stockWarnings[pid];
                    return;
                }

                this.calculateLine(idx);
                this.refreshStockWarning(pid);
            },

            flowRemove(productId) {
                this.flowErrors = [];
                const idx = this.flowItemIndex(productId);
                if (idx === -1) return;
                this.removeItem(idx);
                delete this.stockWarnings[String(productId || '')];
            },

            productLabel(productId) {
                const p = this.products.find(x => x.id == productId);
                return p ? (p.label || p.name || '') : '';
            },

            productLineTotal(productId) {
                const idx = this.flowItemIndex(productId);
                if (idx === -1) return 0;
                return parseFloat(this.items[idx].line_total) || 0;
            },

            baseItems() {
                return this.items.filter(i => !i.is_free && String(i.product_id || '').trim() !== '' && ((parseFloat(i.qty_ct) || 0) > 0 || (parseFloat(i.qty_un) || 0) > 0));
            },

            flowGoReview() {
                this.flowErrors = [];
                if (!this.selectedCustomer) {
                    this.flowStep = 'customer';
                    return;
                }
                if (this.baseItems().length === 0) {
                    this.flowErrors = ['Please add at least one product.'];
                    return;
                }
                this.flowStep = 'review';
                this.reviewEditProductId = '';
                this.$nextTick(() => {
                    if (this.$refs && this.$refs.flowScroll) {
                        this.$refs.flowScroll.scrollTop = 0;
                    }
                });

                setTimeout(() => {
                    try {
                        for (let i = 0; i < this.items.length; i++) {
                            const it = this.items[i];
                            if (!it || it.is_free) continue;
                            try { this.checkSchemes(i); } catch (e) {}
                        }
                        try { this.applyGroupSchemes(); } catch (e) {}
                        try { this.recomputeSchemePreview(); } catch (e) {}
                    } catch (e) {
                    }
                }, 0);
            },

            flowAttemptSave() {
                this.flowErrors = [];
                this.dbgSave('flowAttemptSave:start');
                const stockErrors = this.stockErrorMessages();
                if (stockErrors.length) {
                    this.flowErrors = stockErrors;
                    this.dbgSave('flowAttemptSave:block', { reason: 'stock', errorsLen: stockErrors.length });
                    this.$nextTick(() => {
                        if (this.$refs && this.$refs.flowScroll) {
                            this.$refs.flowScroll.scrollTop = 0;
                        }
                    });
                    return;
                }
                if (this.creditLimitPolicy === 'block' && this.creditLimitExceeded()) {
                    this.flowErrors = [this.creditLimitMessage()];
                    this.dbgSave('flowAttemptSave:block', { reason: 'credit_limit' });
                    this.$nextTick(() => {
                        if (this.$refs && this.$refs.flowScroll) {
                            this.$refs.flowScroll.scrollTop = 0;
                        }
                    });
                    return;
                }
                if (this.$refs && this.$refs.invoiceFormEl) {
                    this.dbgSave('flowAttemptSave:submit');
                    this.$refs.invoiceFormEl.submit();
                }
            },

            stockErrorMessages() {
                const errors = [];
                const seen = new Set();
                for (const it of this.baseItems()) {
                    const p = this.products.find(x => x.id == it.product_id);
                    if (!p) continue;
                    const pack = (parseFloat(it.pack_size) || 0) > 0 ? (parseFloat(it.pack_size) || 1) : 1;
                    const reqUnits = (parseFloat(it.qty_ct) || 0) * pack + (parseFloat(it.qty_un) || 0);
                    const availUnits = parseFloat(p.stock) || 0;
                    if (reqUnits > availUnits + 1e-9) {
                        const label = p.label || p.name || 'Product';
                        const msg = `Insufficient stock for ${label}. Available: ${availUnits} units, Requested: ${reqUnits} units`;
                        if (!seen.has(msg)) {
                            seen.add(msg);
                            errors.push(msg);
                        }
                    }
                }
                return errors;
            },

            getStock(productId) {
                const p = this.products.find(x => x.id == productId);
                return p ? (parseFloat(p.stock) || 0) : 0;
            },

            getPackSize(productId) {
                const p = this.products.find(x => x.id == productId);
                const pack = p ? (parseFloat(p.pack_size) || 0) : 0;
                return pack > 0 ? pack : 1;
            },

            requestedUnitsForProduct(productId) {
                const pid = String(productId || '');
                const idx = this.flowItemIndex(pid);
                if (idx === -1) return 0;
                const it = this.items[idx];
                const pack = (parseFloat(it.pack_size) || 0) > 0 ? (parseFloat(it.pack_size) || 1) : this.getPackSize(pid);
                const ct = parseFloat(it.qty_ct) || 0;
                const un = parseFloat(it.qty_un) || 0;
                return (ct * pack) + un;
            },

            refreshStockWarning(productId) {
                const pid = String(productId || '');
                const avail = this.getStock(pid);
                const req = this.requestedUnitsForProduct(pid);
                if (req > avail + 1e-9) {
                    this.stockWarnings[pid] = `⚠ Only ${avail} units available`;
                } else {
                    delete this.stockWarnings[pid];
                }
            },

            wouldExceedStock(productId, kind, step) {
                const pid = String(productId || '');
                const idx = this.flowEnsureItem(pid);
                const it = this.items[idx];
                const avail = this.getStock(pid);
                const pack = (parseFloat(it.pack_size) || 0) > 0 ? (parseFloat(it.pack_size) || 1) : this.getPackSize(pid);
                const ct = parseFloat(it.qty_ct) || 0;
                const un = parseFloat(it.qty_un) || 0;
                const current = (ct * pack) + un;
                const next = current + (kind === 'ct' ? (pack * (step || 1)) : (step || 1));
                if (next > avail + 1e-9) {
                    this.stockWarnings[pid] = `⚠ Only ${avail} units available`;
                    return true;
                }
                delete this.stockWarnings[pid];
                return false;
            },

            selectedItemCount() {
                return this.baseItems().length;
            },

            freeItemsForParent(parentId) {
                const pid = String(parentId ?? '');
                return this.items.filter(i => !!i && i.is_free && String(i.parent_id ?? '') === pid && !i.__gs);
            },

            groupFreeItems() {
                return this.items.filter(i => !!i && i.is_free && !!i.__gs);
            },

            allFreeItems() {
                return this.items.filter(i => !!i && i.is_free);
            },

            freeParentLabel(freeItem) {
                if (!freeItem) return '';
                if (freeItem.__gs) return 'Scheme (group)';
                const pid = String(freeItem.parent_id ?? '');
                if (!pid || pid === '0') return '';
                const parent = this.items.find(i => !!i && !i.is_free && String(i.id) === pid);
                if (!parent) return '';
                return `For: ${this.productLabel(parent.product_id)}`;
            },

            freeQtyText(item) {
                const ct = parseFloat(item.qty_ct) || 0;
                const un = parseFloat(item.qty_un) || 0;
                if (ct > 0 && un > 0) return `${ct} CT + ${un} UN`;
                if (ct > 0) return `${ct} CT`;
                return `${un} UN`;
            },

            productSchemeBanner(productId) {
                return null;
            },

            updateTaxType() {
                if(!this.selectedCustomer) {
                    this.taxType = 'CGST_UTGST';
                    return;
                }
                const customer = this.customers.find(c => c.id == this.selectedCustomer);
                this.taxType = customer ? customer.tax : 'CGST_UTGST';
                
                // Recalculate everything when tax type changes
                this.items.forEach((_, i) => this.calculateLine(i));
            },

            pendingAmount() {
                let total = parseFloat(this.totals.grand_total) || 0;
                let paid = parseFloat(this.paidAmount) || 0;
                let pending = total - paid;
                if (pending < 0) pending = 0;
                return pending;
            },

            paymentStatus() {
                let total = parseFloat(this.totals.grand_total) || 0;
                let paid = parseFloat(this.paidAmount) || 0;
                let pending = total - paid;
                if (paid <= 0) return 'Unpaid';
                if (pending <= 0) return 'Paid';
                return 'Partial';
            },

            selectedCustomerObj() {
                return this.customers.find(c => String(c.id) === String(this.selectedCustomer)) || null;
            },

            selectedCustomerOutstanding() {
                const c = this.selectedCustomerObj();
                return c ? (parseFloat(c.outstanding_amount) || 0) : 0;
            },

            selectedCustomerLimit() {
                const c = this.selectedCustomerObj();
                const v = c ? c.credit_limit : null;
                return v === null || v === undefined ? 0 : (parseFloat(v) || 0);
            },

            projectedOutstanding() {
                return (this.selectedCustomerOutstanding() || 0) + (parseFloat(this.pendingAmount()) || 0);
            },

            creditLimitExceeded() {
                const limit = this.selectedCustomerLimit();
                if (!limit || limit <= 0) return false;
                return this.projectedOutstanding() > (limit + 0.0000001);
            },

            creditLimitMessage() {
                const limit = this.selectedCustomerLimit();
                return `Credit limit exceeded. Outstanding: ${this.formatMoney6(this.selectedCustomerOutstanding())}, New Pending: ${this.formatMoney6(this.pendingAmount())}, Projected: ${this.formatMoney6(this.projectedOutstanding())}, Limit: ${this.formatMoney6(limit)}.`;
            },

            formatMoney6(v) {
                let n = parseFloat(v);
                if (!isFinite(n)) n = 0;
                let fixed = (Math.round(n * 1000000) / 1000000).toFixed(6);
                return fixed.replace(/\.?0+$/, '');
            },

            isInvoiceDatePast() {
                return !!this.invoiceDate && this.invoiceDate < this.todayStr;
            },

            onInvoiceDateChange() {
                if (this.isInvoiceDatePast()) {
                    this.invoiceDate = this.todayStr;
                }
                if (this.dueDate && this.dueDate < this.invoiceDate) {
                    this.dueDate = this.invoiceDate;
                }
            },

            openDatePicker(el) {
                if (!el) return;
                if (this._datePickerLock) return;
                if (typeof el.showPicker !== 'function') return;
                this._datePickerLock = true;
                try {
                    el.showPicker();
                } catch (e) {
                }
                setTimeout(() => {
                    this._datePickerLock = false;
                }, 250);
            },

            setPaymentType(type) {
                this.paymentType = type;
                this.onPaymentTypeChange();
            },

            onPaymentTypeChange() {
                if (this.paymentType === 'Credit') {
                    this.autoPaid = false;
                    if (String(this.paidAmount || '').trim() === '' || parseFloat(this.paidAmount) === parseFloat(this.totals.grand_total)) {
                        this.paidAmount = 0;
                    }
                    return;
                }
                this.autoPaid = true;
                this.paidAmount = this.totals.grand_total;
            },

            addItem() {
                this.items.push({
                    id: Date.now() + Math.random().toString(36).substr(2, 9),
                    product_id: '',
                    qty_ct: '',
                    qty_un: '',
                    discount_pct: 0,
                    is_free: false,
                    base_price: 0,
                    mrp: 0,
                    gst_rate: 0,
                    pack_size: 1,
                    taxable: 0,
                    tax_amount: 0,
                    line_total: 0,
                    stock_warning: false
                });
            },

            removeItem(index) {
                if(this.items.length > 1) {
                    let item = this.items[index];
                    // Remove any associated free items first
                    if (item && !item.is_free) {
                        let freeIndex = this.items.findIndex(i => i.parent_id === item.id && i.is_free);
                        if (freeIndex !== -1) {
                            this.items.splice(freeIndex, 1);
                            // Adjust index if we removed an item before the current one
                            if (freeIndex < index) {
                                index--;
                            }
                        }
                    }
                    this.items.splice(index, 1);
                    this.calculateTotals();
                    this.applyGroupSchemes();
                    this.recomputeSchemePreview();
                }
            },

            onProductSelect(index) {
                let item = this.items[index];
                if(!item.product_id) {
                    item.base_price = 0;
                    item.mrp = 0;
                    item.gst_rate = 0;
                    item.pack_size = 1;
                } else {
                    let p = this.products.find(x => x.id == item.product_id);
                    if(p) {
                        item.base_price = p.base_price;
                        item.mrp = p.mrp;
                        item.gst_rate = p.gst_rate;
                        item.pack_size = p.pack_size;
                    }
                }
                this.calculateLine(index);
            },

            calculateLine(index, skipSchemes = false) {
                let item = this.items[index];
                
                if(item.is_free || !item.product_id) {
                    item.taxable = 0;
                    item.tax_amount = 0;
                    item.line_total = 0;
                    this.calculateTotals();
                    return;
                }

                let ct = parseFloat(item.qty_ct) || 0;
                let un = parseFloat(item.qty_un) || 0;
                let totalUnits = (ct * item.pack_size) + un;
                
                // Stock Check
                let product = this.products.find(p => p.id == item.product_id);
                if (product) {
                    item.stock_warning = totalUnits > product.stock;
                } else {
                    item.stock_warning = false;
                }
                
                let taxable = item.base_price * totalUnits;
                let taxAmount = taxable * (item.gst_rate / 100);
                
                item.taxable = taxable;
                item.tax_amount = taxAmount;
                item.line_total = taxable + taxAmount;
                
                this.calculateTotals();
                if (!skipSchemes) {
                    this.checkSchemes(index);
                    this.applyGroupSchemes();
                    this.recomputeSchemePreview();
                }
            },

            checkSchemes(index) {
                let item = this.items[index];
                if (!item || item.is_free) return;

                // Remove existing free item added by this line
                let existingFreeIndex = this.items.findIndex(i => i.parent_id === item.id && i.is_free);
                if (existingFreeIndex !== -1) {
                    this.items.splice(existingFreeIndex, 1);
                    if (existingFreeIndex < index) index--;
                }

                if (!item.product_id) return;

                let scheme = this.schemes.find(s => s.product_id == item.product_id);
                let qtyToCheck = 0;
                if (scheme) {
                    qtyToCheck = scheme.slab_unit === 'CT' ? (parseFloat(item.qty_ct) || 0) : ((parseFloat(item.qty_ct) || 0) * item.pack_size + (parseFloat(item.qty_un) || 0));
                }

                let applicableSlab = null;
                if (scheme) {
                    for (let slab of scheme.slabs) {
                        if (qtyToCheck >= slab.min_qty && (slab.max_qty === null || qtyToCheck <= slab.max_qty)) {
                            applicableSlab = slab;
                            break;
                        }
                    }
                }

                if (applicableSlab && applicableSlab.free_qty > 0) {
                    let freeProductId = applicableSlab.free_product_id || item.product_id;
                    let freeProduct = this.products.find(p => p.id == freeProductId);
                    
                    if (freeProduct) {
                        let newFreeItem = {
                            id: Date.now() + Math.random().toString(36).substr(2, 9),
                            product_id: String(freeProductId),
                            qty_ct: 0,
                            qty_un: applicableSlab.free_qty,
                            discount_pct: 0,
                            is_free: true,
                            base_price: freeProduct.base_price,
                            mrp: freeProduct.mrp,
                            gst_rate: freeProduct.gst_rate,
                            pack_size: freeProduct.pack_size,
                            taxable: 0,
                            tax_amount: 0,
                            line_total: 0,
                            parent_id: item.id
                        };
                        
                        this.items.splice(index + 1, 0, newFreeItem);
                        setTimeout(() => {
                            this.calculateLine(index + 1, true);
                        }, 10);
                    }
                }
            },

            calculateTotals() {
                let tax = 0;
                let cgst = 0;
                let sgst = 0;
                let igst = 0;
                
                
                this.items.forEach(item => {
                    tax += item.taxable;
                    
                    if(this.taxType === 'IGST') {
                        igst += item.tax_amount;
                    } else {
                        cgst += (item.tax_amount / 2);
                        sgst += (item.tax_amount / 2);
                    }
                });

                let grandBeforeRound = tax + cgst + sgst + igst;
                let rounded = Math.round(grandBeforeRound);
                
                this.totals.taxable = tax;
                this.totals.cgst = cgst;
                this.totals.sgst = sgst;
                this.totals.igst = igst;
                this.totals.round_off = rounded - grandBeforeRound;
                this.totals.grand_total = rounded;
            },
            applyGroupSchemes() {
                // Rebuild group-scheme free items from scratch
                this.items = this.items.filter(i => !(i.is_free && i.__gs));

                if (!Array.isArray(this.groupSchemes) || this.groupSchemes.length === 0) {
                    return;
                }

                for (const scheme of this.groupSchemes) {
                    if (!scheme || !scheme.is_active) continue;
                    const gid = String(scheme.product_group_id);
                    let unitsPerCarton = null;
                    let totalUnits = 0;
                    for (const it of this.items) {
                        if (it.is_free) continue;
                        const pg = this.productGroupsMap[String(it.product_id)] || [];
                        if (pg.some(x => String(x) === gid)) {
                            const pack = parseFloat(it.pack_size) || 0;
                            if (!unitsPerCarton && pack > 0) {
                                unitsPerCarton = pack;
                            }
                            const ct = parseFloat(it.qty_ct) || 0;
                            const un = parseFloat(it.qty_un) || 0;
                            const packSize = (parseFloat(it.pack_size) || unitsPerCarton || 0);
                            if (packSize > 0) {
                                totalUnits += (ct * packSize) + un;
                            }
                        }
                    }
                    if (!unitsPerCarton || unitsPerCarton <= 0) continue;
                    const totalCt = Math.floor((parseFloat(totalUnits) || 0) / unitsPerCarton);
                    if (totalCt <= 0) continue;

                    let matched = null;
                    const slabsSorted = (scheme.slabs || []).slice().sort((a,b) => (a.min_qty_ct||0) - (b.min_qty_ct||0));
                    for (const s of slabsSorted) {
                        if (totalCt >= s.min_qty_ct && totalCt <= s.max_qty_ct) {
                            matched = s;
                        }
                    }
                    if (!matched || !matched.free_qty_units || matched.free_qty_units <= 0) continue;

                    const freePid = String(scheme.free_product_id);
                    const freeProduct = this.products.find(p => String(p.id) === freePid);
                    if (!freeProduct) continue;

                    const freeItem = {
                        id: Date.now() + Math.random().toString(36).substr(2,9),
                        product_id: freePid,
                        qty_ct: 0,
                        qty_un: matched.free_qty_units,
                        discount_pct: 0,
                        is_free: true,
                        base_price: freeProduct.base_price,
                        mrp: freeProduct.mrp,
                        gst_rate: freeProduct.gst_rate,
                        pack_size: freeProduct.pack_size,
                        taxable: 0,
                        tax_amount: 0,
                        line_total: 0,
                        parent_id: 0, // lock editing in UI
                        __gs: scheme.id
                    };
                    this.items.push(freeItem);
                }
            },
            recomputeSchemePreview() {
                const previews = [];

                if (Array.isArray(this.groupSchemes) && this.groupSchemes.length > 0) {
                    for (const scheme of this.groupSchemes) {
                        if (!scheme || !scheme.is_active) continue;
                        const gid = String(scheme.product_group_id);

                        let unitsPerCarton = null;
                        let totalUnits = 0;
                        for (const it of this.items) {
                            if (it.is_free) continue;
                            const pg = this.productGroupsMap[String(it.product_id)] || [];
                            if (!pg.some(x => String(x) === gid)) continue;

                            const pack = parseFloat(it.pack_size) || 0;
                            if (!unitsPerCarton && pack > 0) {
                                unitsPerCarton = pack;
                            }
                            const ct = parseFloat(it.qty_ct) || 0;
                            const un = parseFloat(it.qty_un) || 0;
                            const packSize = (parseFloat(it.pack_size) || unitsPerCarton || 0);
                            if (packSize > 0) {
                                totalUnits += (ct * packSize) + un;
                            }
                        }
                        if (!unitsPerCarton || unitsPerCarton <= 0) continue;
                        const totalCt = Math.floor((parseFloat(totalUnits) || 0) / unitsPerCarton);
                        if (totalCt <= 0) continue;

                        let matched = null;
                        const slabsSorted = (scheme.slabs || []).slice().sort((a,b) => (a.min_qty_ct||0) - (b.min_qty_ct||0));
                        for (const s of slabsSorted) {
                            if (totalCt >= s.min_qty_ct && totalCt <= s.max_qty_ct) {
                                matched = s;
                            }
                        }
                        const freePid = String(scheme.free_product_id);
                        const freeProduct = this.products.find(p => String(p.id) === freePid);

                        const first = slabsSorted[0] || null;
                        const qualified = !!(matched && matched.free_qty_units && matched.free_qty_units > 0);
                        if (!qualified && !(first && totalCt < first.min_qty_ct)) {
                            continue;
                        }

                        let slabText = qualified
                            ? `${matched.min_qty_ct}–${matched.max_qty_ct} CT`
                            : (first ? `${first.min_qty_ct}–${first.max_qty_ct} CT` : '-');

                        let suggestionText = null;
                        if (!qualified && first) {
                            const req = first.min_qty_ct - totalCt;
                            if (req > 0 && (first.free_qty_units || 0) > 0) {
                                suggestionText = `Add ${req} more CT to qualify for scheme (Get ${first.free_qty_units} units free)`;
                            }
                        } else if (qualified) {
                            const next = slabsSorted.find(x => (x.min_qty_ct || 0) > totalCt);
                            if (next) {
                                const req = (next.min_qty_ct || 0) - totalCt;
                                if (req > 0 && (next.free_qty_units || 0) > 0) {
                                    suggestionText = `Add ${req} more CT to get ${next.free_qty_units} units free`;
                                }
                            }
                        }

                        previews.push({
                            key: `gs-${scheme.id}`,
                            scheme_name: scheme.name || 'Scheme',
                            total_ct: totalCt,
                            slab: slabText,
                            qualified: qualified,
                            free_qty_units: qualified ? matched.free_qty_units : (first ? (first.free_qty_units || 0) : 0),
                            free_product_name: freeProduct ? (freeProduct.label || freeProduct.name || 'Product') : 'Product',
                            suggestion_text: suggestionText,
                        });
                    }
                }

                if (Array.isArray(this.schemes) && this.schemes.length > 0) {
                    for (const it of this.items) {
                        if (!it || it.is_free || !it.product_id) continue;
                        const scheme = this.schemes.find(s => s.product_id == it.product_id);
                        if (!scheme) continue;

                        const ct = parseFloat(it.qty_ct) || 0;
                        const un = parseFloat(it.qty_un) || 0;
                        const pack = parseFloat(it.pack_size) || 1;
                        const totalUnits = (ct * pack) + un;
                        const qtyToCheck = scheme.slab_unit === 'CT' ? ct : totalUnits;
                        const totalCt = Math.floor(totalUnits / pack);

                        let applicableSlab = null;
                        for (let slab of scheme.slabs || []) {
                            if (qtyToCheck >= slab.min_qty && (slab.max_qty === null || qtyToCheck <= slab.max_qty)) {
                                applicableSlab = slab;
                                break;
                            }
                        }
                        const slabs = (scheme.slabs || []).slice().sort((a,b) => (a.min_qty||0) - (b.min_qty||0));
                        const first = slabs[0] || null;
                        const qualified = !!(applicableSlab && (applicableSlab.free_qty > 0));
                        if (!qualified && !(scheme.slab_unit === 'CT' && first && ct < first.min_qty)) {
                            continue;
                        }

                        const freePid = String(applicableSlab.free_product_id || it.product_id);
                        const freeProduct = this.products.find(p => String(p.id) === freePid);

                        let suggestionText = null;
                        if (!qualified && scheme.slab_unit === 'CT' && first) {
                            const req = first.min_qty - ct;
                            if (req > 0 && (first.free_qty || 0) > 0) {
                                suggestionText = `Add ${req} more CT to qualify for scheme (Get ${first.free_qty} units free)`;
                            }
                        } else if (qualified && scheme.slab_unit === 'CT') {
                            const next = slabs.find(x => (x.min_qty || 0) > ct);
                            if (next) {
                                const req = (next.min_qty || 0) - ct;
                                if (req > 0 && (next.free_qty || 0) > 0) {
                                    suggestionText = `Add ${req} more CT to get ${next.free_qty} units free`;
                                }
                            }
                        }

                        previews.push({
                            key: `ps-${scheme.id}-${it.id}`,
                            scheme_name: scheme.name || 'Scheme',
                            total_ct: totalCt,
                            slab: qualified
                                ? `${applicableSlab.min_qty}–${applicableSlab.max_qty === null ? '∞' : applicableSlab.max_qty} ${scheme.slab_unit}`
                                : (first ? `${first.min_qty}–${first.max_qty === null ? '∞' : first.max_qty} ${scheme.slab_unit}` : '-'),
                            qualified: qualified,
                            free_qty_units: qualified ? applicableSlab.free_qty : (first ? (first.free_qty || 0) : 0),
                            free_product_name: freeProduct ? (freeProduct.label || freeProduct.name || 'Product') : 'Product',
                            suggestion_text: suggestionText,
                        });
                    }
                }

                this.schemePreview = previews;
            },

            formatCurrency(amount) {
                return '₹' + parseFloat(amount).toFixed(2);
            }
            };
        });
    });
</script>
@endsection
