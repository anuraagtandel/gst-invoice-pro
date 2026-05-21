@extends('layouts.app')

@section('title', 'Products')

@section('content')
@php
    $productsPayload = $products->getCollection()->map(function ($p) {
        return [
            'id' => (int) $p->id,
            'name' => $p->name,
            'product_code' => $p->product_code,
            'brand' => $p->brand,
            'category' => $p->category,
            'description_label' => $p->description_label,
            'hsn_code' => $p->hsn_code,
            'mrp' => (float) $p->mrp,
            'base_price' => (float) $p->base_price,
            'unit_type' => $p->unit_type,
            'pack_type' => $p->pack_type,
            'pack_size' => $p->pack_size,
            'gst_rate' => (float) $p->gst_rate,
        ];
    })->values();
    $productsJson = json_encode($productsPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $defaultsPayload = [
        'brand' => $brands->first()->name ?? '',
        'volume' => $volumes->first()->name ?? '',
        'pack_type' => $packTypes->first()->code ?? '',
        'pack_size' => (string) ($packSizes->first()->units_per_ct ?? ''),
        'category' => $categories->first()->name ?? '',
    ];
    $defaultsJson = json_encode($defaultsPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
@endphp

<div id="productsPage" data-products='{{ $productsJson }}' data-defaults='{{ $defaultsJson }}' data-initial-q="{{ e($q ?? '') }}" data-first-item="{{ (int) ($products->firstItem() ?? 1) - 1 }}" x-data="productManager()" class="space-y-6">

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @include('partials._stats_card', ['label' => 'Total Products', 'value' => $products->total(), 'icon' => 'ph-package', 'colorClass' => 'bg-indigo-50 text-indigo-600'])
        @include('partials._stats_card', ['label' => 'HSN Codes', 'value' => \App\Models\Product::distinct('hsn_code')->count('hsn_code'), 'icon' => 'ph-barcode', 'colorClass' => 'bg-purple-50 text-purple-600'])
        @include('partials._stats_card', ['label' => 'Categories', 'value' => \App\Models\Category::count(), 'icon' => 'ph-tag', 'colorClass' => 'bg-amber-50 text-amber-600'])
    </div>

    <!-- Header & Search -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('app.products.index') }}" class="w-full sm:w-auto flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
            <div class="relative w-full sm:w-96">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
                <input type="text" name="q" x-model="liveQuery" @input="onLiveInput" placeholder="Search products..." class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
            </div>
            <button type="submit" class="w-full sm:w-auto shrink-0 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors inline-flex items-center justify-center gap-2 shadow-sm whitespace-nowrap min-w-[110px]">
                Search
            </button>
        </form>
        <button @click="openModal()" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-2 shadow-sm">
            <i class="ph ph-plus text-lg"></i> Add Product
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($products->isEmpty())
            @include('partials._empty_state', ['icon' => 'ph-package', 'title' => 'No products found', 'message' => 'Add your first product to get started.'])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Sr No.</th>
                            <th class="px-6 py-3 font-semibold">Description Label</th>
                            <th class="px-6 py-3 font-semibold">HSN</th>
                            <th class="px-6 py-3 font-semibold text-right">MRP</th>
                            <th class="px-6 py-3 font-semibold text-right">Base Price</th>
                            <th class="px-6 py-3 font-semibold text-center">Unit</th>
                            <th class="px-6 py-3 font-semibold text-center">Pack</th>
                            <th class="px-6 py-3 font-semibold text-center">GST%</th>
                            <th class="px-6 py-3 font-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(product, idx) in filteredProducts()" :key="product.id">
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 text-slate-500" x-text="firstItem + idx + 1"></td>
                                <td class="px-6 py-4">
                                    <p class="font-medium text-slate-800" x-text="product.description_label"></p>
                                </td>
                                <td class="px-6 py-4 text-slate-600" x-text="product.hsn_code || '-'"></td>
                                <td class="px-6 py-4 text-right font-medium text-slate-800">₹<span x-text="formatNumber2(product.mrp)"></span></td>
                                <td class="px-6 py-4 text-right text-slate-600">₹<span x-text="formatNumber6(product.base_price)"></span></td>
                                <td class="px-6 py-4 text-center text-slate-600">
                                    <span x-text="product.unit_type"></span>
                                    <template x-if="product.pack_type">
                                        <span class="text-[10px] bg-slate-100 px-1 py-0.5 rounded" x-text="product.pack_type"></span>
                                    </template>
                                </td>
                                <td class="px-6 py-4 text-center text-slate-600" x-text="product.pack_size"></td>
                                <td class="px-6 py-4 text-center text-slate-600"><span x-text="parseInt(product.gst_rate || 0)"></span>%</td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button" @click="editProduct(product.id)" class="w-8 h-8 rounded text-indigo-600 hover:bg-indigo-50 flex items-center justify-center transition-colors">
                                            <i class="ph ph-pencil-simple text-lg"></i>
                                        </button>
                                        <form :action="`/app/products/${product.id}`" method="POST" class="inline" onsubmit="return confirm('Delete this product?');">
                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                            <input type="hidden" name="_method" value="DELETE">
                                            <button type="submit" class="w-8 h-8 rounded text-red-600 hover:bg-red-50 flex items-center justify-center transition-colors">
                                                <i class="ph ph-trash text-lg"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredProducts().length === 0">
                            <td colspan="9" class="px-6 py-6 text-slate-500 text-center">No products found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-200">
                {{ $products->links() }}
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

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div 
                x-show="isModalOpen"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl"
            >
                <!-- Header -->
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="font-syne font-semibold text-lg text-slate-800" id="modal-title">
                        Product Details
                    </h3>
                    <button @click="isModalOpen = false" type="button" class="text-slate-400 hover:text-slate-500 transition-colors">
                        <i class="ph ph-x text-xl"></i>
                    </button>
                </div>
                
                <!-- Body -->
                <div class="px-6 py-5">
                    <form :action="formAction" method="POST" id="productForm">
                        @csrf
                        <template x-if="formMethod === 'PUT'">
                            <input type="hidden" name="_method" value="PUT">
                        </template>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Name -->
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Product Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" x-model="form.name" required autocomplete="off" autocorrect="off" autocapitalize="characters" spellcheck="false" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none" style="text-transform: uppercase;">
                            </div>
                            <!-- Product Code -->
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Product Code <span class="text-red-500">*</span></label>
                                <input type="text" name="product_code" x-model="form.product_code" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none" style="text-transform: uppercase;">
                            </div>
                            <!-- Brand -->
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Brand <span class="text-red-500">*</span></label>
                                <select name="brand" x-model="form.brand" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                    @foreach($brands as $b)
                                        <option value="{{ $b->name }}">{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Volume -->
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Volume <span class="text-red-500">*</span></label>
                                <select name="volume" x-model="form.volume" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                    @foreach($volumes as $v)
                                        <option value="{{ $v->name }}">{{ $v->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Pack Type -->
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Pack Type</label>
                                <select name="pack_type" x-model="form.pack_type" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                    @foreach($packTypes as $pack)
                                        <option value="{{ $pack->code }}">{{ $pack->code }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Pack Size -->
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Pack Size <span class="text-red-500">*</span><span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">carton size</span></label>
                                <select name="pack_size" x-model="form.pack_size" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                                    @foreach($packSizes as $ps)
                                        <option value="{{ $ps->units_per_ct }}">{{ $ps->units_per_ct }} units/CT</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-slate-700 mb-1">Opening Cartons</label>
                                <input type="number" name="opening_stock_ct" x-model="form.opening_stock_ct" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                <input type="hidden" name="opening_stock_un" value="0">
                            </div>
                            <!-- HSN Code -->
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">HSN Code <span class="text-red-500">*</span></label>
                                <input type="text" name="hsn_code" x-model="form.hsn_code" list="hsn-list" required inputmode="numeric" maxlength="8" pattern="[0-9]{8}" @input="form.hsn_code = String(form.hsn_code || '').replace(/\\D/g, '').slice(0, 8)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                <div x-show="hsnInvalid" class="mt-1 text-xs font-semibold text-red-600 bg-red-50 border border-red-100 px-2 py-1 rounded-md inline-flex items-center gap-1">
                                    <i class="ph ph-warning-circle"></i>
                                    <span>HSN invalid</span>
                                </div>
                                <datalist id="hsn-list">
                                    @foreach($hsnCodes as $hsn)
                                        <option value="{{ $hsn->code }}">{{ $hsn->description }}</option>
                                    @endforeach
                                </datalist>
                            </div>
                            <!-- Category -->
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Category</label>
                                <select name="category" x-model="form.category" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                    @foreach($categories as $category)
                                        <option value="{{ $category->name }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- MRP -->
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">MRP <span class="text-red-500">*</span><span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">per unit</span></label>
                                <input type="number" step="0.01" name="mrp" x-model="form.mrp" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>
                            <!-- Base Price -->
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Base Price <span class="text-red-500">*</span><span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">per unit</span></label>
                                <input type="number" step="0.000001" name="base_price" x-model="form.base_price" @input="calcTrade" @blur="form.base_price = normalize6(form.base_price); calcTrade()" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>
                            <!-- Trade Price -->
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Trade Price (Incl. GST) (₹)</label>
                                <input type="number" step="0.000001" name="trade_price" x-model="form.trade_price" readonly class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-slate-50 text-slate-600">
                            </div>
                            <!-- GST Rate -->
                            <div class="col-span-1 md:col-span-2">
                                <label class="block text-xs font-medium text-slate-700 mb-2">GST Rate (%) <span class="text-red-500">*</span></label>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="rate in [0, 5, 6, 9, 12, 18, 28, 40]" :key="rate">
                                        <label class="cursor-pointer">
                                            <input type="radio" name="gst_rate" :value="rate" x-model="form.gst_rate" @change="calcTrade" class="peer sr-only">
                                            <div class="px-4 py-2 rounded-lg border border-slate-200 text-sm font-medium text-slate-600 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 transition-colors">
                                                <span x-text="rate + '%'"></span>
                                            </div>
                                        </label>
                                    </template>
                                </div>
                            </div>

                        </div>

                        <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-slate-100">
                            <button type="button" @click="isModalOpen = false" class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">Cancel</button>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-sm">Save Product</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function productManager() {
        const el = document.getElementById('productsPage');
        const products = el ? JSON.parse(el.dataset.products || '[]') : [];
        const defaults = el ? JSON.parse(el.dataset.defaults || '{}') : {};
        const initialQ = el ? (el.dataset.initialQ || '') : '';
        const firstItem = el ? (parseInt(el.dataset.firstItem || '0', 10) || 0) : 0;
        return {
            liveQuery: initialQ,
            debouncedQuery: initialQ,
            _searchTimer: null,
            firstItem: firstItem,
            products: products,
            isModalOpen: false,
            formAction: '{{ route("app.products.store") }}',
            formMethod: 'POST',
            form: {
                id: null, product_code: '', name: '', brand: defaults.brand || '', volume: defaults.volume || '', unit_type: 'CT', pack_type: defaults.pack_type || '', pack_size: defaults.pack_size || '', opening_stock_ct: 0, opening_stock_un: 0, hsn_code: '', category: defaults.category || '', mrp: '', base_price: '', trade_price: '', gst_rate: 12
            },

            get hsnInvalid() {
                const v = String(this.form.hsn_code || '').trim();
                return v.length > 0 && v.length !== 8;
            },
            
            openModal() {
                this.formAction = '{{ route("app.products.store") }}';
                this.formMethod = 'POST';
                this.form = { id: null, product_code: '', name: '', brand: defaults.brand || '', volume: defaults.volume || '', unit_type: 'CT', pack_type: defaults.pack_type || '', pack_size: defaults.pack_size || '', opening_stock_ct: 0, opening_stock_un: 0, hsn_code: '', category: defaults.category || '', mrp: '', base_price: '', trade_price: '', gst_rate: 12 };
                this.calcTrade();
                this.isModalOpen = true;
            },
            
            async editProduct(id) {
                try {
                    const res = await fetch(`/app/products/${id}/edit`);
                    const data = await res.json();
                    this.formAction = `/app/products/${id}`;
                    this.formMethod = 'PUT';
                    this.form = { ...data };
                    this.calcTrade();
                    this.isModalOpen = true;
                } catch (e) {
                    alert('Error loading product details.');
                }
            },

            onLiveInput() {
                if (this._searchTimer) {
                    clearTimeout(this._searchTimer);
                }
                this._searchTimer = setTimeout(() => {
                    this.debouncedQuery = String(this.liveQuery || '');
                }, 300);
            },

            filteredProducts() {
                const q = String(this.debouncedQuery || '').toLowerCase().trim();
                if (q === '') {
                    return this.products;
                }
                const tokens = q.split(/\s+/).filter(Boolean);
                const scored = [];
                for (const p of this.products) {
                    const hay = [
                        p.name,
                        p.product_code,
                        p.brand,
                        p.category,
                        p.hsn_code,
                    ].join(' ').toLowerCase();
                    if (!tokens.every(t => hay.includes(t))) {
                        continue;
                    }
                    let score = 0;
                    const name = String(p.name || '').toLowerCase();
                    const code = String(p.product_code || '').toLowerCase();
                    if (name.startsWith(q)) score += 50;
                    if (code.startsWith(q)) score += 40;
                    if (hay.includes(q)) score += 10;
                    scored.push({ p, score });
                }
                scored.sort((a, b) => b.score - a.score || String(a.p.name || '').localeCompare(String(b.p.name || '')));
                return scored.map(x => x.p);
            },

            formatNumber2(v) {
                const n = parseFloat(v);
                if (!isFinite(n)) return '0.00';
                return n.toFixed(2);
            },

            formatNumber6(v) {
                const n = parseFloat(v);
                if (!isFinite(n)) return '0';
                const fixed = (Math.round(n * 1000000) / 1000000).toFixed(6);
                return fixed.replace(/\.?0+$/, '');
            },
            
            calcTrade() {
                let base = parseFloat(this.form.base_price) || 0;
                let gst = parseFloat(this.form.gst_rate) || 0;
                if(base >= 0) {
                    let trade = base * (1 + (gst/100));
                    this.form.trade_price = this.format6(trade);
                }
            },

            normalize6(value) {
                let n = parseFloat(value);
                if (!isFinite(n)) return '';
                return this.format6(n);
            },

            format6(value) {
                let n = parseFloat(value);
                if (!isFinite(n)) return '';
                let fixed = (Math.round(n * 1000000) / 1000000).toFixed(6);
                return fixed.replace(/\.?0+$/, '');
            }
        }
    }
</script>
@endpush
@endsection
