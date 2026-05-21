@extends('layouts.app')

@section('content')
<div
    id="inventoryPage"
    class="container mx-auto px-4 sm:px-6 lg:px-8 py-8"
    data-initial-items='@json($initial_items ?? [])'
    data-initial-q='@json($initial_q ?? "")'
    data-initial-limit='@json($initial_limit ?? 50)'
    x-data="inventoryList()"
    x-init="init()"
>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-syne font-semibold text-lg text-slate-800">Inventory</h3>
            <p class="text-sm text-slate-500">Live stock from opening stock, purchases, and sales.</p>
        </div>
        <div class="w-full sm:w-96">
            <div class="relative">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
                <input type="text" x-model="q" @input="onSearchInput()" placeholder="Search products..." class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
            </div>
            <div class="mt-1 text-[11px] text-slate-500" x-text="statusText()"></div>
        </div>
    </div>

    <!-- Inventory Table -->
    <div class="mt-8 bg-white rounded-xl shadow-sm border border-slate-200">
        <div class="p-0">
            <div x-show="flashMessage" x-cloak class="px-6 pt-4">
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg text-sm font-semibold" x-text="flashMessage"></div>
            </div>
            <template x-if="items.length === 0 && !loading">
                <div class="p-8">
                    <template x-if="(q || '').trim() === ''">
                        <div>
                            @include('partials._empty_state', [
                                'icon' => 'ph-package',
                                'title' => 'No products yet',
                                'message' => 'Create your first product to see it here.'
                            ])
                        </div>
                    </template>
                    <template x-if="(q || '').trim() !== ''">
                        <div>
                            @include('partials._empty_state', [
                                'icon' => 'ph-package',
                                'title' => 'No products found',
                                'message' => 'Try adjusting your search.'
                            ])
                        </div>
                    </template>
                </div>
            </template>

            <div class="overflow-x-auto" x-show="items.length > 0">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Product Name</th>
                            <th class="px-6 py-3 font-semibold">Product Code</th>
                            <th class="px-6 py-3 font-semibold">Pack Size</th>
                            <th class="px-6 py-3 font-semibold">Current Stock</th>
                            <th class="px-6 py-3 font-semibold text-center">Adjust</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="p in items" :key="p.id">
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 font-medium text-slate-800">
                                    <a class="text-indigo-700 hover:text-indigo-900" :href="`/app/inventory/${p.id}`" x-text="p.name"></a>
                                </td>
                                <td class="px-6 py-4 text-slate-600" x-text="p.product_code || '-'"></td>
                                <td class="px-6 py-4 text-slate-600" x-text="`${p.pack_size} units/ct`"></td>
                                <td class="px-6 py-4 font-bold text-slate-800" x-text="formatStock(p.current_units, p.pack_size)"></td>
                                <td class="px-6 py-4 text-center">
                                    <button type="button" class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition-colors" @click="openAdjustModal(p)">
                                        <i class="ph ph-pencil-simple text-lg"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-slate-200 flex items-center justify-between" x-show="items.length > 0">
                <div class="text-sm text-slate-500">
                    <span x-text="`Loaded ${items.length}${hasMore ? '+' : ''}`"></span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="text-xs text-slate-500" x-show="loading">Loading…</div>
                    <button type="button" class="px-3 py-2 rounded-lg bg-slate-100 text-slate-600 text-xs font-semibold hover:bg-slate-200" x-show="hasMore && !loading" @click="loadMore()">Load More</button>
                </div>
            </div>

            <div x-ref="sentinel" class="h-4"></div>
        </div>
    </div>

    <div
        x-show="adjustModalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center"
        @keydown.escape.window="closeAdjustModal"
    >
        <div class="absolute inset-0 bg-slate-900/50" @click="closeAdjustModal"></div>
        <div class="relative w-[min(520px,95vw)] rounded-2xl bg-white shadow-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-900">Adjust Stock</div>
                <button type="button" class="w-9 h-9 inline-flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100" @click="closeAdjustModal">
                    <i class="ph ph-x text-lg"></i>
                </button>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Product</div>
                    <div class="mt-1 text-sm font-semibold text-slate-900" x-text="adjustProduct?.name || ''"></div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Current Stock</div>
                    <div class="mt-1 text-sm text-slate-800" x-text="adjustProduct ? formatStock(adjustProduct.current_units, adjustProduct.pack_size) : ''"></div>
                </div>

                <div>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">New Stock</div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">CT</label>
                            <input type="number" min="0" inputmode="numeric" x-model="adjustNewCt" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">UN</label>
                            <input type="number" min="0" inputmode="numeric" x-model="adjustNewUn" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>
                </div>

                <div x-show="adjustError" class="text-sm text-red-600 font-semibold" x-text="adjustError"></div>

                <div class="pt-2 flex items-center justify-end gap-2">
                    <button type="button" class="h-10 px-4 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold" @click="closeAdjustModal">Cancel</button>
                    <button type="button" class="h-10 px-4 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold disabled:bg-indigo-400" :disabled="adjustSaving" @click="saveAdjustment">
                        <span x-text="adjustSaving ? 'Saving…' : 'Save'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function inventoryList() {
        const root = document.getElementById('inventoryPage');
        let initialItems = [];
        let initialQ = '';
        let initialLimit = 50;
        if (root) {
            try { initialItems = JSON.parse(root.dataset.initialItems || '[]'); } catch (e) { initialItems = []; }
            try { initialQ = JSON.parse(root.dataset.initialQ || '\"\"'); } catch (e) { initialQ = ''; }
            try { initialLimit = JSON.parse(root.dataset.initialLimit || '50'); } catch (e) { initialLimit = 50; }
        }
        return {
            items: Array.isArray(initialItems) ? initialItems : [],
            q: String(initialQ || ''),
            offset: 0,
            limit: (parseInt(initialLimit || 50, 10) || 50),
            loading: false,
            hasMore: true,
            flashMessage: '',
            adjustModalOpen: false,
            adjustSaving: false,
            adjustError: '',
            adjustProduct: null,
            adjustNewCt: 0,
            adjustNewUn: 0,
            _searchTimer: null,
            _observer: null,
            init() {
                this.offset = this.items.length;
                this.hasMore = this.items.length >= this.limit;
                this.setupObserver();
            },
            openAdjustModal(p) {
                this.adjustProduct = p;
                const pack = parseInt(p?.pack_size || 1, 10) || 1;
                const units = parseInt(p?.current_units || 0, 10) || 0;
                this.adjustNewCt = Math.floor(units / pack);
                this.adjustNewUn = units % pack;
                this.adjustError = '';
                this.adjustModalOpen = true;
            },
            closeAdjustModal() {
                this.adjustModalOpen = false;
                this.adjustSaving = false;
                this.adjustError = '';
            },
            async saveAdjustment() {
                if (!this.adjustProduct) return;
                const newCt = parseInt(this.adjustNewCt || 0, 10);
                const newUn = parseInt(this.adjustNewUn || 0, 10);
                if (!isFinite(newCt) || newCt < 0 || !isFinite(newUn) || newUn < 0) {
                    this.adjustError = 'Please enter valid quantities.';
                    return;
                }

                this.adjustSaving = true;
                this.adjustError = '';
                try {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    const res = await fetch(`/app/api/inventory/products/${this.adjustProduct.id}/adjust`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            ...(token ? { 'X-CSRF-TOKEN': token } : {}),
                        },
                        body: JSON.stringify({ new_ct: newCt, new_un: newUn }),
                    });
                    const json = await res.json().catch(() => ({}));
                    if (!res.ok || !json?.ok) {
                        this.adjustError = (json && (json.message || json.error)) ? (json.message || json.error) : 'Failed to update stock.';
                        return;
                    }
                    const updatedUnits = parseInt(json?.item?.current_units || 0, 10) || 0;
                    this.adjustProduct.current_units = updatedUnits;
                    this.flashMessage = 'Stock updated successfully';
                    this.closeAdjustModal();
                    setTimeout(() => {
                        if (this.flashMessage === 'Stock updated successfully') {
                            this.flashMessage = '';
                        }
                    }, 2500);
                } catch (e) {
                    this.adjustError = 'Failed to update stock.';
                } finally {
                    this.adjustSaving = false;
                }
            },
            setupObserver() {
                if (!this.$refs.sentinel) return;
                if (this._observer) {
                    this._observer.disconnect();
                }
                this._observer = new IntersectionObserver((entries) => {
                    for (const e of entries) {
                        if (e.isIntersecting) {
                            this.loadMore();
                        }
                    }
                }, { root: null, rootMargin: '200px 0px', threshold: 0 });
                this._observer.observe(this.$refs.sentinel);
            },
            statusText() {
                if (this.loading) return 'Loading…';
                if ((this.q || '').trim() !== '') return `Showing results for “${this.q}”`;
                return 'Scroll to load more products.';
            },
            onSearchInput() {
                if (this._searchTimer) clearTimeout(this._searchTimer);
                this._searchTimer = setTimeout(() => this.runSearch(), 250);
            },
            async runSearch() {
                this.items = [];
                this.offset = 0;
                this.hasMore = true;
                await this.loadMore(true);
            },
            async loadMore(force = false) {
                if (this.loading) return;
                if (!this.hasMore && !force) return;
                this.loading = true;
                try {
                    const qs = new URLSearchParams();
                    qs.set('offset', String(this.offset));
                    qs.set('limit', String(this.limit));
                    if ((this.q || '').trim() !== '') qs.set('q', this.q.trim());
                    const res = await fetch(`/app/api/inventory/products?${qs.toString()}`);
                    const json = await res.json();
                    const newItems = Array.isArray(json.items) ? json.items : [];
                    this.items = this.items.concat(newItems);
                    this.offset = Number(json.next_offset ?? this.items.length) || this.items.length;
                    this.hasMore = !!json.has_more;
                } catch (e) {
                    this.hasMore = false;
                } finally {
                    this.loading = false;
                }
            },
            formatStock(unitsValue, packSizeValue) {
                const units = parseInt(unitsValue || 0, 10) || 0;
                const pack = parseInt(packSizeValue || 1, 10) || 1;
                const ct = Math.floor(units / pack);
                const un = units % pack;
                return `${ct} CT & ${un} UN`;
            }
        }
    }
</script>
@endpush
@endsection
