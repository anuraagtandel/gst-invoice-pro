@extends('layouts.app')

@section('title', 'Purchases')

@section('content')
@php
    $defaultSupplier = $suppliers->first(fn($s) => strcasecmp($s->name, 'VARUN BEVERAGES') === 0) ?? $suppliers->first();
    $defaultSupplierPayload = $defaultSupplier ? ['id' => (string) $defaultSupplier->id, 'name' => $defaultSupplier->name] : null;
    $productsPayload = ($products ?? collect())->map(function ($p) {
        return [
            'id' => (string) $p->id,
            'label' => trim($p->name . ' ' . ($p->volume ?? '') . ' (Pack: ' . $p->pack_size . ')'),
            'pack_size' => (int) $p->pack_size,
            'gst_rate' => (float) $p->gst_rate,
        ];
    })->values();
    $productsJson = json_encode($productsPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $defaultSupplierJson = json_encode($defaultSupplierPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
@endphp

@push('styles')
    <style>
        .purchase-entry-scope input.purchase-no-spin::-webkit-outer-spin-button,
        .purchase-entry-scope input.purchase-no-spin::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .purchase-entry-scope input.purchase-no-spin[type=number] {
            -moz-appearance: textfield;
            appearance: textfield;
        }
    </style>
@endpush

<div id="purchasesPage" data-products='{{ $productsJson }}' data-default-supplier='{{ $defaultSupplierJson }}' x-data="purchaseManager()" class="space-y-6">

    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto">
            <form action="{{ route('app.purchases.index') }}" method="GET" class="flex flex-col sm:flex-row items-center gap-3 w-full">
                <div class="relative w-full sm:w-64">
                    <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Bill No or Supplier..." class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <input type="date" name="start_date" value="{{ request('start_date', now()->toDateString()) }}" class="w-full sm:w-auto px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all cursor-pointer" title="Start Date">
                    <span class="text-slate-400 text-xs font-bold uppercase tracking-widest">to</span>
                    <input type="date" name="end_date" value="{{ request('end_date', now()->toDateString()) }}" class="w-full sm:w-auto px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all cursor-pointer" title="End Date">
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <button type="submit" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Filter
                    </button>
                    @if(request('search') || request('start_date') || request('end_date'))
                        <a href="{{ route('app.purchases.index') }}" class="text-red-600 hover:text-red-700 text-xs font-bold uppercase tracking-wider px-2 transition-colors">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>
        <button @click="openModal()" class="w-full lg:w-auto bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-2 shadow-sm">
            <i class="ph ph-plus text-lg"></i> Add Purchase
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($purchases->isEmpty())
            @include('partials._empty_state', ['icon' => 'ph-shopping-cart', 'title' => 'No purchases found', 'message' => 'Record your first purchase entry to add stock.'])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold">#</th>
                            <th class="px-6 py-3 font-semibold">Bill No</th>
                            <th class="px-6 py-3 font-semibold">Date</th>
                            <th class="px-6 py-3 font-semibold">Supplier</th>
                            <th class="px-6 py-3 font-semibold text-center">Items Count</th>
                            <th class="px-6 py-3 font-semibold text-center">Total CT</th>
                            <th class="px-6 py-3 font-semibold text-right">Total Amount</th>
                            <th class="px-6 py-3 font-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($purchases as $purchase)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 text-slate-500">{{ $loop->iteration + $purchases->firstItem() - 1 }}</td>
                            <td class="px-6 py-4 font-medium text-slate-800">{{ $purchase->bill_no }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $purchase->bill_date->format('d M, Y') }}</td>
                            <td class="px-6 py-4 text-slate-600">
                                @if($purchase->supplier_ref)
                                    <div class="flex flex-col">
                                        <span class="font-medium text-slate-800">{{ $purchase->supplier_ref->name }}</span>
                                        @if($purchase->supplier_ref->gstin)
                                            <span class="text-[10px] text-slate-500 uppercase tracking-wider">GST: {{ $purchase->supplier_ref->gstin }}</span>
                                        @endif
                                    </div>
                                @else
                                    {{ $purchase->supplier ?? '-' }}
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="bg-slate-100 text-slate-600 px-2 py-1 rounded-full text-xs font-bold">{{ $purchase->purchaseItems->count() }} items</span>
                            </td>
                            <td class="px-6 py-4 text-center text-slate-700 font-semibold">
                                @php
                                    $ct = (float) $purchase->purchaseItems->sum('qty_ct');
                                    $ct6 = number_format($ct, 6, '.', '');
                                    $ctDisp = rtrim(rtrim($ct6, '0'), '.');
                                @endphp
                                {{ $ctDisp }} CT
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-slate-800">
                                @php
                                    $ta = (float) $purchase->total_amount;
                                    $ta6 = number_format($ta, 6, '.', '');
                                    $taDisp = rtrim(rtrim($ta6, '0'), '.');
                                @endphp
                                ₹{{ $taDisp }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('app.purchases.show', $purchase->id) }}" class="w-8 h-8 rounded text-slate-600 hover:bg-slate-100 flex items-center justify-center transition-colors" title="View Purchase">
                                        <i class="ph ph-eye text-lg"></i>
                                    </a>
                                    <button @click="editPurchase({{ $purchase->id }})" class="w-8 h-8 rounded text-indigo-600 hover:bg-indigo-50 flex items-center justify-center transition-colors">
                                        <i class="ph ph-pencil-simple text-lg"></i>
                                    </button>
                                    <form action="{{ route('app.purchases.destroy', $purchase->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this purchase? This will remove the stock from inventory.');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="w-8 h-8 rounded text-red-600 hover:bg-red-50 flex items-center justify-center transition-colors">
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
            <div class="p-4 border-t border-slate-200">
                {{ $purchases->links() }}
            </div>
        @endif
    </div>

    <!-- Modal -->
    <div 
        x-show="isModalOpen" 
        class="fixed inset-0 z-[100] overflow-y-auto" 
        aria-labelledby="modal-title" 
        role="dialog" 
        aria-modal="true"
        x-cloak
    >
        <!-- Backdrop -->
        <div 
            x-show="isModalOpen"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" 
            @click="isModalOpen = false"
        ></div>

        <div class="flex min-h-full items-center justify-center px-4 py-4 text-center sm:px-6 sm:py-8">
            <div 
                x-show="isModalOpen"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-[960px] purchase-entry-scope"
            >
                <!-- Header -->
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="font-syne font-semibold text-lg text-slate-800" id="modal-title" x-text="formMethod === 'POST' ? 'Add Purchase Entry' : 'Edit Purchase Entry'"></h3>
                    <button @click="isModalOpen = false" type="button" class="text-slate-400 hover:text-slate-500 transition-colors">
                        <i class="ph ph-x text-xl"></i>
                    </button>
                </div>
                
                <!-- Body -->
                <div class="px-6 py-5">
                    <form :action="formAction" method="POST">
                        @csrf
                        <input type="hidden" name="_method" x-model="formMethod">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="space-y-1">
                    <label class="text-xs font-medium text-slate-700">Bill No <span class="text-red-500">*</span></label>
                    <input type="text" name="bill_no" x-model="form.bill_no" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-medium text-slate-700">Bill Date <span class="text-red-500">*</span></label>
                    <input type="date" name="bill_date" x-model="form.bill_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div class="space-y-1 relative" x-data="{ 
                    showResults: false,
                    suppliers: [
                        @foreach($suppliers as $s)
                        {
                            id: '{{ $s->id }}',
                            name: '{{ addslashes($s->name) }}',
                            gstin: '{{ $s->gstin }}'
                        },
                        @endforeach
                    ],
                    get filteredSuppliers() {
                        const term = (form.supplier || '').toLowerCase();
                        if (term === '') return this.suppliers;
                        return this.suppliers.filter(s => 
                            s.name.toLowerCase().includes(term) || 
                            (s.gstin && s.gstin.toLowerCase().includes(term))
                        );
                    },
                    selectSupplier(supplier) {
                        form.supplier = supplier.name;
                        form.supplier_id = supplier.id;
                        this.showResults = false;
                    },
                    toggleResults() {
                        this.showResults = !this.showResults;
                    }
                }" @click.away="showResults = false">
                    <label class="text-xs font-medium text-slate-700">Supplier Name <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="text" x-model="form.supplier" @focus="showResults = true" placeholder="Search or select supplier..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none pr-10">
                        <button type="button" @click="toggleResults()" class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-slate-600">
                            <i class="ph ph-caret-down transition-transform duration-200" :class="showResults ? 'rotate-180' : ''"></i>
                        </button>
                    </div>
                    <input type="hidden" name="supplier_id" :value="form.supplier_id">
                    <input type="hidden" name="supplier" :value="form.supplier">
                    
                    <!-- Autocomplete/Dropdown Results -->
                    <div x-show="showResults" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="absolute z-[110] w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl max-h-60 overflow-y-auto overflow-x-hidden">
                        <div class="p-1">
                            <template x-for="supplier in filteredSuppliers" :key="supplier.id">
                                <button type="button" @click="selectSupplier(supplier)" class="w-full px-4 py-2.5 text-left text-sm hover:bg-indigo-50 rounded-lg transition-colors flex flex-col gap-0.5 mb-0.5 last:mb-0">
                                    <span class="font-semibold text-slate-800" x-text="supplier.name"></span>
                                    <span class="text-[10px] text-slate-500 uppercase tracking-widest font-bold" x-text="supplier.gstin ? 'GSTIN: ' + supplier.gstin : 'Unregistered'"></span>
                                </button>
                            </template>
                            <div x-show="filteredSuppliers.length === 0" class="px-4 py-3 text-sm text-slate-500 italic text-center">
                                No matching suppliers found.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-medium text-slate-700">Vehicle Number</label>
                    <input type="text" name="vehicle_number" x-model="form.vehicle_number" @input="onVehicleNumberInput($event)" placeholder="e.g. GA03X1234" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none" style="text-transform: uppercase;">
                </div>
            </div>

            <!-- Items -->
            <div class="border border-slate-200 rounded-lg overflow-hidden mb-6">
                <div class="bg-slate-50 px-4 py-2 border-b border-slate-200 flex justify-between items-center">
                    <h4 class="text-sm font-semibold text-slate-800">Products Received</h4>
                    <button type="button" @click="addItem()" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                        <i class="ph ph-plus"></i> Add Item Row
                    </button>
                </div>
                <div class="p-4 space-y-3 bg-slate-50/30">
                    <template x-for="(item, index) in items" :key="item._row_key || index">
                        <div class="flex flex-nowrap items-end gap-3 p-3 bg-white border border-slate-100 rounded-lg shadow-sm overflow-x-auto">
                            <div class="w-16 shrink-0">
                                <label class="text-[10px] text-slate-500 uppercase font-bold tracking-wider block mb-1">Sr. No.</label>
                                <div class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-slate-50 text-slate-600 font-semibold text-center" x-text="index + 1"></div>
                            </div>
                            <div class="" style="width: 40%">
                                <label class="text-[10px] text-slate-500 uppercase font-bold tracking-wider block mb-1">Product</label>
                                <select :name="`items[${index}][product_id]`" x-model="item.product_id" x-init="$nextTick(() => { $el.value = item.product_id ? String(item.product_id) : '' })" x-effect="$el.value = item.product_id ? String(item.product_id) : ''" required @change="onProductSelect(index)" class="w-full px-3 py-2 pr-10 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none whitespace-nowrap" :title="products.find(p => String(p.id) === String(item.product_id))?.label || ''">
                                    <option value="">Select...</option>
                                    <template x-for="product in products" :key="product.id">
                                        <option :value="String(product.id)" x-text="product.label"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="flex-[1] w-24 shrink-0">
                                <label class="text-[10px] text-slate-500 uppercase font-bold tracking-wider block mb-1">Cartons</label>
                                <input type="number" step="1" min="0" :name="`items[${index}][qty_ct]`" x-model="item.qty_ct" @input="calculateLine(index)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none purchase-no-spin" onwheel="this.blur()">
                            </div>
                            <div class="flex-[1] w-36 shrink-0">
                                <label class="text-[10px] text-slate-500 uppercase font-bold tracking-wider block mb-1">Purchase Rate</label>
                                <input type="number" step="0.000001" min="0" required :name="`items[${index}][purchase_rate]`" x-model="item.purchase_rate" @input="calculateLine(index)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none purchase-no-spin" onwheel="this.blur()">
                            </div>
                            <div class="flex-[1] w-36 shrink-0">
                                <label class="text-[10px] text-slate-500 uppercase font-bold tracking-wider block mb-1">Total</label>
                                <input type="number" :value="item.total" readonly class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-slate-50 text-slate-500 cursor-not-allowed">
                            </div>
                            <div class="flex items-end shrink-0">
                                <button type="button" @click="removeItem(index)" class="w-9 h-9 sm:mt-[22px] rounded bg-red-50 text-red-600 hover:bg-red-100 flex items-center justify-center transition-colors">
                                    <i class="ph ph-trash text-lg"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                    <div x-show="items.length === 0" class="text-center text-sm text-slate-500 py-4 italic">
                        No items added yet.
                    </div>
                </div>
            </div>

            <div class="flex justify-end items-center gap-4 border-t border-slate-200 pt-4">
                <div class="flex items-center gap-2">
                    <label class="text-xs font-medium text-slate-700">Total Cartons</label>
                    <div class="w-24 px-3 py-2 border border-slate-200 rounded-lg text-sm bg-slate-50 text-slate-700 font-semibold text-right" x-text="totalCartons() + ' CT'"></div>
                </div>
                <div class="w-px h-8 bg-slate-200"></div>
                <div class="flex items-center gap-2">
                    <label class="text-xs font-medium text-slate-700">Total Bill Amount (₹)</label>
                    <input type="number" step="0.000001" name="total_amount" x-model="form.total_amount" readonly class="w-32 px-3 py-2 border border-slate-200 rounded-lg text-sm bg-slate-50 text-slate-500 cursor-not-allowed font-semibold text-right">
                </div>
                <div class="w-px h-8 bg-slate-200"></div>
                <button type="button" @click="isModalOpen = false" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-sm">Save Purchase</button>
            </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function purchaseManager() {
        const el = document.getElementById('purchasesPage');
        const products = el ? JSON.parse(el.dataset.products || '[]') : [];
        const defaultSupplier = el ? JSON.parse(el.dataset.defaultSupplier || 'null') : null;
        return {
            isModalOpen: false,
            formAction: '{{ route("app.purchases.store") }}',
            formMethod: 'POST',
            form: {
                bill_no: '',
                bill_date: new Date().toISOString().slice(0,10),
                supplier_id: null,
                supplier: '',
                vehicle_number: '',
                total_amount: 0,
            },
            products: products,
            defaultSupplier: defaultSupplier,
            items: [],
            
            openModal() {
                this.formAction = '{{ route("app.purchases.store") }}';
                this.formMethod = 'POST';
                this.form = {
                    bill_no: '',
                    bill_date: new Date().toISOString().slice(0,10),
                    supplier_id: this.defaultSupplier ? this.defaultSupplier.id : null,
                    supplier: this.defaultSupplier ? this.defaultSupplier.name : '',
                    vehicle_number: '',
                    total_amount: 0,
                };
                this.items = [{ _row_key: Date.now() + Math.random(), product_id: '', qty_ct: 0, bottles: 0, purchase_rate: '', total: 0 }];
                this.isModalOpen = true;
            },

            onVehicleNumberInput(event) {
                const el = event?.target;
                if (!(el instanceof HTMLInputElement)) {
                    return;
                }
                const raw = el.value ?? '';
                const upper = raw.toUpperCase();
                if (raw !== upper) {
                    const start = el.selectionStart;
                    const end = el.selectionEnd;
                    el.value = upper;
                    this.form.vehicle_number = upper;
                    if (typeof start === 'number' && typeof end === 'number') {
                        el.setSelectionRange(start, end);
                    }
                    return;
                }
                this.form.vehicle_number = upper;
            },

            async editPurchase(id) {
                try {
                    const res = await fetch(`/app/purchases/${id}/edit`);
                    const data = await res.json();
                    this.formAction = `/app/purchases/${id}`;
                    this.formMethod = 'PUT';
                    this.form = {
                        bill_no: data.bill_no,
                        bill_date: data.bill_date.slice(0,10),
                        supplier_id: data.supplier_id,
                        supplier: data.supplier,
                        vehicle_number: (data.vehicle_number ?? '').toUpperCase(),
                        total_amount: data.total_amount,
                    };
                    const rawItems = Array.isArray(data.purchase_items)
                        ? data.purchase_items
                        : (Array.isArray(data.purchaseItems) ? data.purchaseItems : []);

                    if (rawItems.length > 0) {
                        this.items = rawItems.map(item => ({
                            _row_key: Date.now() + Math.random(),
                            product_id: item.product_id ? String(item.product_id) : '',
                            qty_ct: item.qty_ct ?? 0,
                            bottles: item.total_units ?? 0,
                            purchase_rate: item.purchase_rate ?? '',
                            total: item.item_total ?? 0,
                        }));
                    } else if (this.items.length === 0) {
                        this.items = [{ _row_key: Date.now() + Math.random(), product_id: '', qty_ct: 0, bottles: 0, purchase_rate: '', total: 0 }];
                    }
                    this.calculateTotal();
                    this.isModalOpen = true;
                    await this.$nextTick();
                    this.items.forEach((it) => {
                        it.product_id = it.product_id ? String(it.product_id) : '';
                    });
                } catch (e) {
                    alert('Error loading purchase details.');
                }
            },
            
            addItem() {
                this.items.push({ _row_key: Date.now() + Math.random(), product_id: '', qty_ct: 0, bottles: 0, purchase_rate: '', total: 0 });
            },

            removeItem(index) {
                this.items.splice(index, 1);
                this.calculateTotal();
            },

            onProductSelect(index) {
                let item = this.items[index];
                item.purchase_rate = '';
                this.calculateLine(index);
            },

            calculateLine(index) {
                let item = this.items[index];
                item.total = ((parseFloat(item.qty_ct) || 0) * (parseFloat(item.purchase_rate) || 0)).toFixed(6);
                this.calculateTotal();
            },

            calculateTotal() {
                this.form.total_amount = this.items.reduce((sum, item) => sum + (parseFloat(item.total) || 0), 0).toFixed(6);
            },

            totalCartons() {
                const sum = this.items.reduce((acc, item) => acc + (parseFloat(item.qty_ct) || 0), 0);
                const fixed = sum.toFixed(2);
                return fixed.replace(/\.?0+$/, '');
            }
        }
    }

    document.addEventListener('click', (e) => {
        const root = document.getElementById('purchasesPage');
        if (!root) return;
        const target = e.target instanceof HTMLElement ? e.target : null;
        if (!target || !root.contains(target)) return;
        const input = target.closest('input[type="date"]');
        if (!input) return;
        if (typeof input.showPicker === 'function') {
            try { input.showPicker(); } catch (err) { }
        }
    });
</script>
@endpush
@endsection
