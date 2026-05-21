@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@push('styles')
<style>
    .dash-filter-group {
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 4px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(226, 232, 240, 1);
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
    }

    .dash-filter-btn {
        height: 40px;
        width: 112px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 600;
        color: rgba(51, 65, 85, 1);
        background: transparent;
        transition: background-color 120ms ease, color 120ms ease;
        user-select: none;
    }

    .dash-filter-btn:hover {
        background: rgba(241, 245, 249, 1);
    }

    .dash-filter-btn--active {
        background: rgba(15, 23, 42, 1);
        color: rgba(255, 255, 255, 1);
    }
</style>
@endpush

@php
    $dashInitPayload = [
        'range' => $payload['range'] ?? [],
        'kpi_sections' => $payload['kpi_sections'] ?? [],
        'top_products' => $payload['top_products'] ?? [],
        'products_has_more' => $payload['products_has_more'] ?? false,
        'products_next_offset' => $payload['products_next_offset'] ?? 0,
        'scheme_items' => $payload['scheme_items'] ?? [],
        'stock' => $payload['stock'] ?? [],
        'salesman_report' => $payload['salesman_report'] ?? [],
    ];
    $dashInitJson = json_encode($dashInitPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
@endphp

<div id="dashboardPage" data-init='{{ $dashInitJson }}' x-data="dashboard()" x-init="init()" class="space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="min-w-0">
            <h1 class="font-syne font-semibold text-xl text-slate-900">Dashboard</h1>
            <div class="mt-1 text-sm text-slate-500" x-text="rangeLabel()"></div>
        </div>

        <div class="w-full lg:w-auto flex flex-col sm:flex-row gap-3 sm:items-end">
            <div class="dash-filter-group">
                <button type="button" @click="setPreset('today')" class="dash-filter-btn" :class="preset==='today' ? 'dash-filter-btn--active' : ''">Today</button>
                <button type="button" @click="setPreset('week')" class="dash-filter-btn" :class="preset==='week' ? 'dash-filter-btn--active' : ''">This Week</button>
                <button type="button" @click="setPreset('month')" class="dash-filter-btn" :class="preset==='month' ? 'dash-filter-btn--active' : ''">This Month</button>
                <button type="button" @click="setPreset('custom')" class="dash-filter-btn" :class="preset==='custom' ? 'dash-filter-btn--active' : ''">Custom</button>
            </div>

            <div x-show="preset==='custom'" class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                <div class="flex flex-col gap-1 w-full sm:w-auto">
                    <label class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">From</label>
                    <input type="date" x-model="fromDate" @focus="openDatePicker($event.target)" @click="openDatePicker($event.target)" class="px-3 py-2 rounded-xl border border-slate-200 bg-white text-sm focus:ring-2 focus:ring-indigo-500 outline-none cursor-pointer">
                </div>
                <div class="flex flex-col gap-1 w-full sm:w-auto">
                    <label class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">To</label>
                    <input type="date" x-model="toDate" @focus="openDatePicker($event.target)" @click="openDatePicker($event.target)" class="px-3 py-2 rounded-xl border border-slate-200 bg-white text-sm focus:ring-2 focus:ring-indigo-500 outline-none cursor-pointer">
                </div>
                <button type="button" @click="applyCustom()" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-sm">Apply</button>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <template x-for="section in kpiSections" :key="section.title">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-syne font-semibold text-slate-900" x-text="section.title"></h2>
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider" x-text="presetLabel()"></div>
                </div>
                <div class="grid gap-3" :class="kpiGridClass(section.items?.length || 0)">
                    <template x-for="card in section.items" :key="card.title">
                        <div class="relative overflow-hidden rounded-[12px] border border-slate-200/60 bg-white/65 shadow-[0_14px_40px_-28px_rgba(15,23,42,0.45)] backdrop-blur">
                            <div class="absolute inset-0 opacity-20" :class="toneGradient(card.tone)"></div>
                            <div class="absolute -top-10 -right-10 h-24 w-24 rounded-full blur-2xl opacity-30" :class="toneBlob(card.tone)"></div>
                            <div class="relative p-4">
                                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500" x-text="card.title"></div>
                                <div class="mt-1 font-syne font-bold text-xl text-slate-900" x-text="formatValue(card)"></div>
                                <div class="mt-1 text-[11px] text-slate-500" x-text="presetLabel()"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <div class="space-y-6">
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200">
                <h3 class="font-syne font-semibold text-slate-900">All Products</h3>
                <p class="text-xs text-slate-500 mt-1">Quantity sold & revenue in selected range</p>
            </div>
            <div class="p-5">
                <template x-if="topProducts.length === 0">
                    <div class="text-sm text-slate-500">No products found.</div>
                </template>
                <div x-show="topProducts.length > 0" class="space-y-3">
                    <div class="max-h-[420px] overflow-y-auto" x-ref="productsScroll">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm whitespace-nowrap">
                                <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">Product</th>
                                        <th class="px-4 py-3 font-semibold text-right">Qty</th>
                                        <th class="px-4 py-3 font-semibold text-right">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="p in topProducts" :key="p.product_name">
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-4 py-3 font-semibold text-slate-800 truncate" x-text="p.product_name"></td>
                                            <td class="px-4 py-3 text-right text-slate-700 font-semibold" x-text="formatCtUn(p.qty_ct, p.qty_un)"></td>
                                            <td class="px-4 py-3 text-right font-bold text-slate-900">₹ <span x-text="formatNumber2(p.revenue)"></span></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div class="px-4 py-3" x-show="productsLoading">
                            <div class="text-xs text-slate-500 text-center">Loading…</div>
                        </div>
                        <div class="px-4 py-3" x-show="productsHasMore && !productsLoading">
                            <div class="text-center">
                                <button type="button" @click="loadMoreProducts()" class="px-3 py-2 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200">Load more</button>
                            </div>
                        </div>
                        <div class="px-4 py-3" x-show="!productsHasMore && !productsLoading && topProducts.length > 0">
                            <div class="text-xs text-slate-500 text-center">No more products.</div>
                        </div>
                        <div x-ref="productsSentinel" class="h-1"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200">
                    <h3 class="font-syne font-semibold text-slate-900">Scheme Free Items</h3>
                    <p class="text-xs text-slate-500 mt-1">Products & quantity given free in selected range</p>
                </div>
                <div class="p-5">
                    <template x-if="schemeItems.length === 0">
                        <div class="text-sm text-slate-500">No scheme items in selected range.</div>
                    </template>
                    <div class="max-h-[320px] overflow-y-auto pr-1 space-y-3" x-show="schemeItems.length > 0">
                        <template x-for="it in schemeItems" :key="String(it.product_id || '') + '|' + it.product_name">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-slate-800 truncate" x-text="`${it.product_name}${it.volume ? ' ' + it.volume : ''}`"></div>
                                    <div class="mt-1 text-[11px] text-slate-500">Qty: <span class="font-semibold text-slate-700" x-text="formatUnitsAsCtUn(it.units, it.pack_size)"></span></div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="text-xs text-slate-500">Units</div>
                                    <div class="text-sm font-bold text-amber-700" x-text="formatQty(it.units)"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Salesman-wise Sales Report -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h3 class="font-syne font-semibold text-slate-900">Salesman-wise Sales Report</h3>
                <p class="text-xs text-slate-500 mt-1">Performance breakdown by salesman</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider" x-text="presetLabel()"></span>
            </div>
        </div>
        <div class="p-5">
            <template x-if="salesmanReport.length === 0">
                <div class="text-sm text-slate-500 text-center py-8">No salesman data available for the selected range.</div>
            </template>
            
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6" x-show="salesmanReport.length > 0">
                <template x-for="s in salesmanReport" :key="s.name">
                    <div class="flex flex-col border border-slate-200 rounded-xl overflow-hidden bg-slate-50/30 hover:border-indigo-200 transition-colors" x-data="{ expanded: false }">
                        <!-- Card Header / Summary -->
                        <div class="p-4 bg-white border-b border-slate-100">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs" x-text="s.rank"></div>
                                    <h4 class="font-bold text-slate-900 truncate max-w-[150px]" x-text="s.name"></h4>
                                    <template x-if="s.is_top">
                                        <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-[10px] font-bold uppercase tracking-wider">Top Performer</span>
                                    </template>
                                </div>
                                <button @click="expanded = !expanded" class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 transition-colors">
                                    <i class="ph" :class="expanded ? 'ph-caret-up' : 'ph-caret-down'"></i>
                                </button>
                            </div>
                            
                            <div class="grid grid-cols-3 gap-2">
                                <div class="p-2 rounded-lg bg-slate-50 border border-slate-100">
                                    <div class="text-[10px] text-slate-500 uppercase font-bold tracking-tight">Sales</div>
                                    <div class="text-sm font-bold text-slate-900">₹<span x-text="formatNumber2(s.total_sales)"></span></div>
                                </div>
                                <div class="p-2 rounded-lg bg-slate-50 border border-slate-100">
                                    <div class="text-[10px] text-slate-500 uppercase font-bold tracking-tight">Qty</div>
                                    <div class="text-sm font-bold text-slate-900" x-text="formatCtUn(s.total_qty_ct, s.total_qty_un)"></div>
                                </div>
                                <div class="p-2 rounded-lg bg-slate-50 border border-slate-100">
                                    <div class="text-[10px] text-slate-500 uppercase font-bold tracking-tight">Scheme</div>
                                    <div class="text-sm font-bold text-amber-700">₹<span x-text="formatNumber2(s.total_scheme_value)"></span></div>
                                </div>
                            </div>
                            
                            <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500 px-1">
                                <span>Total Invoices: <span class="font-bold text-slate-700" x-text="s.total_invoices"></span></span>
                                <span class="cursor-pointer text-indigo-600 font-semibold hover:underline" @click="expanded = !expanded" x-text="expanded ? 'Hide Details' : 'Show Details'"></span>
                            </div>
                        </div>
                        
                        <!-- Expandable Details -->
                        <div x-show="expanded" x-cloak class="bg-white border-t border-slate-50 overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-[11px] border-collapse">
                                    <thead>
                                        <tr class="bg-slate-50 text-slate-500 border-b border-slate-100">
                                            <th class="px-3 py-2 font-bold uppercase tracking-wider">Product</th>
                                            <th class="px-2 py-2 font-bold uppercase tracking-wider text-center">Sold (CT/UN)</th>
                                            <th class="px-2 py-2 font-bold uppercase tracking-wider text-right">Amount</th>
                                            <th class="px-3 py-2 font-bold uppercase tracking-wider">Scheme</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        <template x-for="p in s.products" :key="p.name">
                                            <tr class="hover:bg-slate-50/50">
                                                <td class="px-3 py-2">
                                                    <div class="font-bold text-slate-800" x-text="p.name"></div>
                                                </td>
                                                <td class="px-2 py-2 text-center text-slate-700 font-medium" x-text="formatCtUn(p.qty_ct, p.qty_un)"></td>
                                                <td class="px-2 py-2 text-right text-slate-900 font-bold">₹<span x-text="formatNumber2(p.total_amount)"></span></td>
                                                <td class="px-3 py-2">
                                                    <template x-if="(p.schemes || []).length > 0">
                                                        <div class="flex flex-col gap-1">
                                                            <template x-for="sc in p.schemes" :key="sc.name">
                                                                <div class="flex flex-col">
                                                                    <div class="text-[10px] font-bold text-amber-700" x-text="sc.name"></div>
                                                                    <div class="text-slate-500 italic">Free: <span x-text="formatCtUn(sc.free_ct, sc.free_un)"></span> (₹<span x-text="formatNumber2(sc.scheme_value)"></span>)</div>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </template>
                                                    <template x-if="(p.schemes || []).length === 0">
                                                        <span class="text-slate-300">—</span>
                                                    </template>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function dashboard() {
        const el = document.getElementById('dashboardPage');
        const init = el ? JSON.parse(el.dataset.init || '{}') : {};
        const range = init.range || {};
        const initTop = Array.isArray(init.top_products) ? init.top_products : [];
        return {
            preset: range.preset || 'today',
            fromDate: range.from || '',
            toDate: range.to || '',
            kpiSections: init.kpi_sections || [],
            topProducts: initTop,
            productsHasMore: !!init.products_has_more,
            productsNextOffset: Number(init.products_next_offset ?? initTop.length) || initTop.length,
            productsLimit: 7,
            productsLoading: false,
            _productsObserver: null,
            schemeItems: init.scheme_items || [],
            stock: init.stock || {},
            salesmanReport: init.salesman_report || [],
            loading: false,
            init() {
                this.kpiSections = this.filterDashboardSections(this.kpiSections);
                this.setupProductsObserver();
            },
            filterDashboardSections(sections) {
                const titleBlock = ['purchase', 'receivable', 'stock', 'inventory'];
                const cardBlock = [
                    'total purchase', 'total purchase orders', 'purchase quantity',
                    'total outstanding', 'overdue amount', 'payments received',
                    'total stock value',
                    'total schemes applied', 'total free items given (qty)',
                ];

                const safeSections = Array.isArray(sections) ? sections : [];
                const out = [];
                for (const s of safeSections) {
                    const st = String(s?.title || '');
                    const stLower = st.toLowerCase();
                    if (titleBlock.some(k => stLower.includes(k))) {
                        continue;
                    }
                    const items = Array.isArray(s?.items) ? s.items : [];
                    const keptItems = items.filter(it => {
                        const t = String(it?.title || '').toLowerCase();
                        return !cardBlock.some(k => t.includes(k));
                    });
                    if (keptItems.length === 0) continue;
                    out.push({ ...s, items: keptItems });
                }
                return out;
            },
            kpiGridClass(n) {
                if (n <= 1) return 'grid-cols-1';
                if (n === 2) return 'grid-cols-1 sm:grid-cols-2';
                if (n === 3) return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3';
                return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4';
            },
            rangeLabel() {
                return `${this.presetLabel()} • ${this.formatDate(this.fromDate)} – ${this.formatDate(this.toDate)}`;
            },
            presetLabel() {
                if (this.preset === 'today') return 'Today';
                if (this.preset === 'week') return 'This Week';
                if (this.preset === 'month') return 'This Month';
                return 'Custom';
            },
            setPreset(p) {
                this.preset = p;
                if (p !== 'custom') this.fetchData();
            },
            applyCustom() {
                this.fetchData();
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
            async fetchData() {
                this.loading = true;
                try {
                    const qs = new URLSearchParams();
                    qs.set('range', this.preset);
                    if (this.preset === 'custom') {
                        if (this.fromDate) qs.set('from_date', this.fromDate);
                        if (this.toDate) qs.set('to_date', this.toDate);
                    }
                    const res = await fetch(`/app/api/dashboard?${qs.toString()}`);
                    const json = await res.json();
                    this.fromDate = json.range.from;
                    this.toDate = json.range.to;
                    this.kpiSections = this.filterDashboardSections(json.kpi_sections || []);
                    this.topProducts = Array.isArray(json.top_products) ? json.top_products : [];
                    this.productsHasMore = !!json.products_has_more;
                    this.productsNextOffset = Number(json.products_next_offset ?? this.topProducts.length) || this.topProducts.length;
                    this.schemeItems = json.scheme_items || [];
                    this.stock = json.stock;
                    this.salesmanReport = json.salesman_report || [];
                    await this.$nextTick();
                    this.setupProductsObserver();
                } catch (e) {
                } finally {
                    this.loading = false;
                }
            },
            setupProductsObserver() {
                if (!this.$refs.productsScroll || !this.$refs.productsSentinel) return;
                if (this._productsObserver) {
                    this._productsObserver.disconnect();
                }
                this._productsObserver = new IntersectionObserver((entries) => {
                    for (const e of entries) {
                        if (e.isIntersecting) {
                            this.loadMoreProducts();
                        }
                    }
                }, { root: this.$refs.productsScroll, rootMargin: '200px 0px', threshold: 0 });
                this._productsObserver.observe(this.$refs.productsSentinel);
            },
            async loadMoreProducts() {
                if (this.productsLoading) return;
                if (!this.productsHasMore) return;
                this.productsLoading = true;
                try {
                    const qs = new URLSearchParams();
                    qs.set('range', this.preset);
                    if (this.preset === 'custom') {
                        if (this.fromDate) qs.set('from_date', this.fromDate);
                        if (this.toDate) qs.set('to_date', this.toDate);
                    }
                    qs.set('offset', String(this.productsNextOffset || 0));
                    qs.set('limit', String(this.productsLimit || 20));
                    const res = await fetch(`/app/api/dashboard/products?${qs.toString()}`);
                    const json = await res.json();
                    const items = Array.isArray(json.items) ? json.items : [];
                    const existing = new Set((this.topProducts || []).map(x => String(x?.product_name || '')));
                    const uniqueItems = items.filter(x => !existing.has(String(x?.product_name || '')));
                    this.topProducts = this.topProducts.concat(uniqueItems);
                    this.productsHasMore = !!json.has_more;
                    this.productsNextOffset = Number(json.next_offset ?? this.topProducts.length) || this.topProducts.length;
                } catch (e) {
                    this.productsHasMore = false;
                } finally {
                    this.productsLoading = false;
                }
            },
            formatIndian(value, maxDecimals = 2) {
                const n = Number(value);
                if (!isFinite(n)) return '0';
                const isInt = Math.abs(n - Math.round(n)) < 1e-9;
                const decimals = isInt ? 0 : Math.max(0, Math.min(maxDecimals, 6));
                try {
                    return n.toLocaleString('en-IN', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: decimals,
                    });
                } catch (e) {
                    const fixed = decimals === 0 ? String(Math.round(n)) : n.toFixed(decimals);
                    return fixed;
                }
            },
            formatRupee(value) {
                return '₹ ' + this.formatIndian(value, 2);
            },
            formatQty(value) {
                return this.formatIndian(value, 2);
            },
            formatCtUn(ctValue, unValue) {
                return `${this.formatIndian(ctValue, 0)} CT / ${this.formatIndian(unValue, 0)} UN`;
            },
            formatCount(value) {
                const n = Number(value);
                if (!isFinite(n)) return '0';
                return Math.round(n).toLocaleString('en-IN');
            },
            formatValue(card) {
                if (card.kind === 'currency') return this.formatRupee(card.value);
                if (card.kind === 'count') return this.formatCount(card.value);
                if (card.kind === 'qty') return this.formatIndian(card.value, 0);
                if (card.kind === 'ctun') return this.formatCtUn(card.value?.ct, card.value?.un);
                if (card.kind === 'ct_only') return this.formatIndian(card.value?.ct, 0) + ' CT';
                return String(card.value ?? '');
            },
            formatNumber2(value) {
                return this.formatIndian(value, 2);
            },
            formatDate(value) {
                if (!value) return '';
                const parts = String(value).split('-');
                if (parts.length !== 3) return String(value);
                const d = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
                return d.toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' });
            },
            trendBadge(kpi) {
                const pct = kpi.change_pct;
                if (pct === null || pct === undefined || !isFinite(Number(pct))) {
                    return '<span class="text-slate-500">—</span>';
                }
                const up = Number(pct) >= 0;
                const arrow = up ? '↑' : '↓';
                const cls = up ? 'text-emerald-700' : 'text-red-700';
                return `<span class="${cls}">${arrow} ${Math.abs(Number(pct)).toFixed(1)}%</span>`;
            },
            topProductPct(p) {
                const max = Math.max(...this.topProducts.map(x => Number(x.revenue) || 0), 0.0001);
                return Math.min(100, ((Number(p.revenue) || 0) / max) * 100);
            },
            formatStock(p) {
                const units = parseInt(p.current_units || 0, 10) || 0;
                const pack = parseInt(p.pack_size || 1, 10) || 1;
                const ct = Math.floor(units / pack);
                const un = units % pack;
                return `${this.formatIndian(ct, 0)} CT & ${this.formatIndian(un, 0)} UN`;
            },
            formatUnitsAsCtUn(unitsValue, packSizeValue) {
                const units = parseInt(unitsValue || 0, 10) || 0;
                const pack = parseInt(packSizeValue || 1, 10) || 1;
                const ct = Math.floor(units / pack);
                const un = units % pack;
                return `${this.formatIndian(ct, 0)} CT & ${this.formatIndian(un, 0)} UN`;
            },
            statusClass(status) {
                if (status === 'Paid') return 'bg-emerald-50 text-emerald-700 border border-emerald-100';
                if (status === 'Partial') return 'bg-amber-50 text-amber-800 border border-amber-100';
                return 'bg-red-50 text-red-700 border border-red-100';
            },
            toneGradient(tone) {
                if (tone === 'blue') return 'bg-gradient-to-br from-blue-600/40 via-blue-500/10 to-transparent';
                if (tone === 'purple') return 'bg-gradient-to-br from-violet-600/40 via-violet-500/10 to-transparent';
                if (tone === 'red') return 'bg-gradient-to-br from-rose-600/40 via-rose-500/10 to-transparent';
                if (tone === 'orange') return 'bg-gradient-to-br from-amber-600/40 via-amber-500/10 to-transparent';
                if (tone === 'yellow') return 'bg-gradient-to-br from-yellow-500/35 via-yellow-400/10 to-transparent';
                return 'bg-gradient-to-br from-slate-500/20 to-transparent';
            },
            toneBlob(tone) {
                if (tone === 'blue') return 'bg-blue-500';
                if (tone === 'purple') return 'bg-violet-500';
                if (tone === 'red') return 'bg-rose-500';
                if (tone === 'orange') return 'bg-amber-500';
                if (tone === 'yellow') return 'bg-yellow-400';
                return 'bg-slate-400';
            }
        }
    }
</script>
@endpush
@endsection
