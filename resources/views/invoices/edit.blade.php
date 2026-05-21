@extends('layouts.app')

@section('content')
@php
    $invoiceEditCustomersJson = json_encode(
        ($customers ?? collect())->map(function ($c) {
            return [
                'id' => (string) $c->id,
                'name' => (string) $c->name,
                'gstin' => (string) ($c->gstin ?? 'No GSTIN'),
                'tax' => (string) ($c->tax_type ?? ''),
                'credit_limit' => $c->credit_limit === null ? null : (float) $c->credit_limit,
                'outstanding_amount' => (float) ($c->outstanding_amount ?? 0),
            ];
        })->values(),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
    $invoiceEditProductsJson = json_encode(
        ($products ?? collect())->map(function ($p) {
            return [
                'id' => (string) $p->id,
                'name' => (string) $p->name,
                'label' => trim((string) $p->name . ' ' . (string) ($p->volume ?? '')),
                'base_price' => (float) ($p->base_price ?? 0),
                'mrp' => (float) ($p->mrp ?? 0),
                'gst_rate' => (float) ($p->gst_rate ?? 0),
                'pack_size' => (int) ($p->pack_size ?? 1),
                'stock' => (int) ($p->current_stock_units ?? 0),
            ];
        })->values(),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
    $invoiceEditSchemesJson = json_encode(
        ($schemes ?? collect())->map(function ($s) {
            return [
                'id' => (int) $s->id,
                'product_id' => (string) $s->product_id,
                'slab_unit' => (string) ($s->slab_unit ?? ''),
                'slabs' => ($s->schemeSlabs ?? collect())->map(function ($slab) {
                    return [
                        'min_qty' => (int) $slab->min_qty,
                        'max_qty' => $slab->max_qty === null ? null : (int) $slab->max_qty,
                        'free_qty' => (int) $slab->free_qty,
                        'free_product_id' => $slab->free_product_id ? (string) $slab->free_product_id : null,
                    ];
                })->values(),
            ];
        })->values(),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );

    $invoiceEditInitJson = json_encode(
        [
            'selectedCustomer' => (string) old('customer_id', $invoice->customer_id),
            'todayStr' => date('Y-m-d'),
            'existingInvoiceDate' => $invoice->invoice_date->format('Y-m-d'),
            'invoiceDate' => (string) old('invoice_date', $invoice->invoice_date->format('Y-m-d')),
            'dueDate' => (string) old('due_date', (optional($invoice->due_date)->format('Y-m-d') ?? '')),
            'minInvoiceDate' => ($invoice->invoice_date->format('Y-m-d') < date('Y-m-d') ? $invoice->invoice_date->format('Y-m-d') : date('Y-m-d')),
            'paymentType' => (string) old('payment_type', $invoice->payment_type ?? ($invoice->payment_mode === 'Credit' ? 'Credit' : 'Cash')),
            'paidAmount' => (string) old('paid_amount', (string) ($invoice->paid_amount ?? 0)),
            'creditLimitPolicy' => (string) ($creditLimitPolicy ?? 'warn'),
            'taxType' => (string) ($invoice->tax_type ?? ''),
        ],
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );

    $invoiceEditItemsJson = json_encode(
        old('items', $invoice->invoiceItems),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
@endphp
<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h1 class="font-syne text-2xl font-bold text-slate-800">Edit Invoice {{ $invoice->invoice_no }}</h1>
        <p class="text-slate-500 text-sm mt-1">Update existing GST invoice details.</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('app.invoices.index') }}" class="bg-white border border-slate-200 text-slate-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-50 transition-colors shadow-sm">
            Cancel
        </a>
        <a href="{{ route('app.invoices.einvoice_json', $invoice->id) }}" class="bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm flex items-center gap-2">
            <i class="ph ph-file-code"></i> Generate e-Invoice JSON
        </a>
        <button type="submit" form="invoiceForm" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm flex items-center gap-2">
            <i class="ph ph-check-circle"></i> Update Invoice
        </button>
    </div>
</div>

<div id="invoiceEditData"
     data-customers='{{ $invoiceEditCustomersJson }}'
     data-products='{{ $invoiceEditProductsJson }}'
     data-schemes='{{ $invoiceEditSchemesJson }}'
     data-init='{{ $invoiceEditInitJson }}'
     data-items='{{ $invoiceEditItemsJson }}'></div>

<div x-data="invoiceForm()" x-init="init()" class="bg-white border border-slate-200 rounded-2xl shadow-sm">
    <form id="invoiceForm" action="{{ route('app.invoices.update', $invoice->id) }}" method="POST" class="p-6" @submit.prevent="if(items.some(i => i.stock_warning)) { alert('Please fix insufficient stock items before saving.'); return false; } if(isInvoiceDatePastDisallowed()) { alert('Invoice date cannot be in the past'); return false; } if(dueDate && dueDate < invoiceDate) { alert('Due date must be same or after invoice date'); return false; } if(creditLimitPolicy === 'block' && creditLimitExceeded()) { alert(creditLimitMessage()); return false; } $el.submit()">
        @csrf
        @method('PUT')

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

        <!-- Top Section -->
        <div class="grid grid-cols-1 md:grid-cols-6 gap-6 mb-8 pb-8 border-b border-slate-100">
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Invoice No <span class="text-red-500">*</span></label>
                <input type="text" value="{{ $invoice->invoice_no }}" readonly class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-500 cursor-not-allowed font-medium">
            </div>
            
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Date <span class="text-red-500">*</span></label>
                <input type="date" name="invoice_date" x-model="invoiceDate" :min="minInvoiceDate" @change="onInvoiceDateChange()" @focus="openDatePicker($event.target)" @click="openDatePicker($event.target)" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all cursor-pointer hover:border-slate-300">
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
            <div class="sm:col-span-2" x-show="selectedCustomer && (selectedCustomerLimit() > 0)">
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
                    $currentSalesman = old('salesman', $invoice->salesman);
                    $activeNames = $salesmen->pluck('name')->all();
                @endphp
                <select name="salesman" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                    <option value="">Select...</option>
                    @if(!empty($currentSalesman) && !in_array($currentSalesman, $activeNames, true))
                        <option value="{{ $currentSalesman }}" selected disabled>{{ $currentSalesman }} (Inactive)</option>
                    @endif
                    @foreach($salesmen as $s)
                        <option value="{{ $s->name }}" @selected($currentSalesman === $s->name)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Payment Type <span class="text-red-500">*</span></label>
                <select name="payment_type" x-model="paymentType" @change="onPaymentTypeChange()" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                    <option value="Cash">Cash</option>
                    <option value="Credit">Credit</option>
                </select>
                <input type="hidden" name="payment_mode" :value="paymentType === 'Credit' ? 'Credit' : 'Cash'">
            </div>
        </div>

        <!-- Items Section -->
        <div class="mb-4 flex items-center justify-between">
            <h3 class="font-syne font-semibold text-lg text-slate-800">Invoice Items</h3>
            <span class="text-xs font-medium px-3 py-1 rounded-full bg-slate-100 text-slate-600" x-text="'Tax Type: ' + (taxType || 'Auto')"></span>
        </div>

        <div class="mb-6">
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
                                <div class="relative" x-data="{ 
                                    init() {
                                        if(item.product_id) {
                                            let p = products.find(x => x.id == item.product_id);
                                            if(p) this.search = p.name || p.label;
                                        }
                                        // Sync internal showResults to parent item for z-index management
                                        this.$watch('showResults', value => item.showResults = value);
                                    },
                                    search: '', 
                                    showResults: false,
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
                                        onProductSelect(index);
                                    }
                                }" @click.away="showResults = false">
                                    <input type="text" x-model="search" autocomplete="off" autocorrect="off" spellcheck="false" @focus="showResults = true" @input="showResults = true" placeholder="Search product..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none disabled:bg-slate-50 disabled:text-slate-400 transition-all" :disabled="item.parent_id !== undefined" x-show="item.parent_id === undefined">
                                    <input type="hidden" :name="`items[${index}][product_id]`" x-model="item.product_id">
                                    
                                    <div x-show="showResults && filteredProducts.length > 0" class="absolute z-[200] left-0 w-full min-w-[320px] mt-1.5 bg-white border border-slate-200 rounded-xl shadow-2xl max-h-72 overflow-y-auto ring-1 ring-black/5 backdrop-blur-sm">
                                        <div class="p-1.5">
                                            <template x-for="product in filteredProducts" :key="product.id">
                                                <button type="button" @click="selectProduct(product)" class="w-full px-4 py-3 text-left text-sm hover:bg-indigo-50 rounded-lg transition-colors border-b border-slate-50 last:border-0 flex flex-col gap-0.5">
                                                    <span class="font-bold text-slate-800" x-text="product.label"></span>
                                                    <span class="text-[10px] text-slate-400 font-medium uppercase tracking-wider" x-text="'MRP: ₹' + product.mrp + ' | GST: ' + product.gst_rate + '%'"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                
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
                                <input type="number" x-model="item.qty_ct" :name="`items[${index}][qty_ct]`" min="0" step="1" @input="calculateLine(index)" class="w-full px-2 py-1.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none text-center read-only:bg-slate-50 read-only:text-slate-400" :readonly="item.is_free">
                            </td>
                            <td class="px-4 py-3">
                                <input type="number" x-model="item.qty_un" :name="`items[${index}][qty_un]`" min="0" step="1" @input="calculateLine(index)" class="w-full px-2 py-1.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none text-center read-only:bg-slate-50 read-only:text-slate-400" :readonly="item.is_free">
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

        <button type="button" @click="addItem()" class="mb-8 text-sm font-medium text-indigo-600 hover:text-indigo-700 flex items-center gap-1.5 transition-colors bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg">
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

    </form>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        const root = document.getElementById('invoiceEditData');
        const customers = root ? JSON.parse(root.dataset.customers || '[]') : [];
        const products = root ? JSON.parse(root.dataset.products || '[]') : [];
        const schemes = root ? JSON.parse(root.dataset.schemes || '[]') : [];
        const init = root ? JSON.parse(root.dataset.init || '{}') : {};
        const existingItems = root ? JSON.parse(root.dataset.items || '[]') : [];
        Alpine.data('invoiceForm', () => ({
            customers: customers,
            products: products,
            schemes: schemes,
            selectedCustomer: String(init.selectedCustomer || ''),
            todayStr: String(init.todayStr || ''),
            existingInvoiceDate: String(init.existingInvoiceDate || ''),
            invoiceDate: String(init.invoiceDate || ''),
            dueDate: String(init.dueDate || ''),
            minInvoiceDate: String(init.minInvoiceDate || ''),
            paymentType: String(init.paymentType || 'Cash'),
            paidAmount: String(init.paidAmount || ''),
            autoPaid: false,
            creditLimitPolicy: String(init.creditLimitPolicy || 'warn'),
            taxType: String(init.taxType || ''),
            items: [],
            totals: {
                taxable: 0,
                cgst: 0,
                sgst: 0,
                igst: 0,
                
                round_off: 0,
                grand_total: 0
            },

            init() {
                this.updateTaxType();
                this.onInvoiceDateChange();
                
                // Load existing items
                this.items = existingItems.map((item, index) => {
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
                
                // Recover product details for old items
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

                if(this.items.length === 0) {
                    this.addItem();
                }

                if (String(this.paidAmount || '').trim() === '') {
                    this.paidAmount = this.paymentType === 'Credit' ? 0 : this.totals.grand_total;
                }

                this.$watch('totals.grand_total', (v) => {
                    if (this.paymentType !== 'Credit' && this.autoPaid) {
                        this.paidAmount = v;
                    }
                });
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

            isInvoiceDatePastDisallowed() {
                return !!this.invoiceDate && this.invoiceDate < this.todayStr && this.invoiceDate !== this.existingInvoiceDate;
            },

            onInvoiceDateChange() {
                if (!this.invoiceDate) {
                    this.invoiceDate = this.existingInvoiceDate || this.todayStr;
                }
                if (this.isInvoiceDatePastDisallowed()) {
                    this.invoiceDate = this.minInvoiceDate || this.todayStr;
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

            onPaymentTypeChange() {
                if (this.paymentType === 'Credit') {
                    this.autoPaid = false;
                    return;
                }
                if (String(this.paidAmount || '').trim() === '' || parseFloat(this.paidAmount) === 0) {
                    this.autoPaid = true;
                    this.paidAmount = this.totals.grand_total;
                }
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
                
                let disc = parseFloat(item.discount_pct) || 0;
                
                let taxable = item.base_price * totalUnits * (1 - (disc/100));
                let taxAmount = taxable * (item.gst_rate / 100);
                
                item.taxable = taxable;
                item.tax_amount = taxAmount;
                item.line_total = taxable + taxAmount;
                
                this.calculateTotals();
                if (!skipSchemes) {
                    this.checkSchemes(index);
                }
            },

            checkSchemes(index) {
                let item = this.items[index];
                if (!item || item.is_free) return;

                // Remove existing free item added by this line (we do this first to clean up if product is cleared)
                let existingFreeIndex = this.items.findIndex(i => i.parent_id === item.id && i.is_free);
                if (existingFreeIndex !== -1) {
                    this.items.splice(existingFreeIndex, 1);
                    // Adjust index if we removed an item before the current one
                    if (existingFreeIndex < index) {
                        index--;
                    }
                }

                if (!item.product_id) return;

                // Find scheme for this product
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
                            parent_id: item.id // Track which item generated this free item
                        };
                        
                        // Insert immediately after the current item
                        this.items.splice(index + 1, 0, newFreeItem);
                        
                        // Small timeout to allow alpine to render the new item before we calculate
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
                let cess = 0;
                
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

            formatCurrency(amount) {
                return '₹' + parseFloat(amount).toFixed(2);
            }
        }));
    });
</script>
@endsection
