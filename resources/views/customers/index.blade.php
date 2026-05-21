@extends('layouts.app')

@section('title', 'Customers')

@section('content')
@php
    $enableCustomerImport = (bool) \App\Models\Setting::get('enable_customer_import', true);
    $areasJson = json_encode(
        ($areas ?? collect())->map(fn($a) => ['id' => $a->id, 'name' => $a->name])->values(),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
@endphp
<div id="customersPage" data-areas='{{ $areasJson }}' x-data="customerManager()" x-init="init()" class="space-y-6">

    <!-- Header & Search -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-4">
        <div class="relative w-full">
            <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
            <input type="text" x-model="searchQuery" @input="onSearchInput()" @keydown.enter.prevent placeholder="Search customers..." class="w-full pl-10 pr-10 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
            <button type="button" x-show="searchQuery.trim().length > 0" @click="clearSearch()" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                <i class="ph ph-x text-lg"></i>
            </button>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="inline-flex items-center gap-1 bg-slate-100 p-1 rounded-xl border border-slate-200 w-full sm:w-auto">
                <button type="button" @click="customerType = 'all'" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors" :class="customerType === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'">
                    All (<span x-text="allCount"></span>)
                </button>
                <button type="button" @click="customerType = 'b2b'" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors" :class="customerType === 'b2b' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'">
                    B2B (<span x-text="b2bCount"></span>)
                </button>
                <button type="button" @click="customerType = 'b2c'" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors" :class="customerType === 'b2c' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'">
                    B2C (<span x-text="b2cCount"></span>)
                </button>
            </div>

            <div class="flex flex-wrap items-center justify-start sm:justify-end gap-2">
                @if($enableCustomerImport)
                    <a href="{{ route('app.customers.template') }}" class="h-10 px-3 w-full sm:w-auto bg-white hover:bg-slate-50 text-slate-700 rounded-lg text-sm font-semibold transition-colors inline-flex items-center justify-center gap-2 border border-slate-200 shadow-sm">
                        <i class="ph ph-download-simple text-base"></i> Download Template
                    </a>
                    <form method="POST" action="{{ route('app.customers.upload') }}" enctype="multipart/form-data" class="w-full sm:w-auto">
                        @csrf
                        <input x-ref="customerUpload" type="file" name="file" accept=".xlsx,.csv" class="hidden" @change="$el.form.submit()">
                        <button type="button" @click="$refs.customerUpload.click()" class="h-10 px-3 w-full sm:w-auto bg-white hover:bg-slate-50 text-slate-700 rounded-lg text-sm font-semibold transition-colors inline-flex items-center justify-center gap-2 border border-slate-200 shadow-sm">
                            <i class="ph ph-upload-simple text-base"></i> Upload Excel
                        </button>
                    </form>
                    <a href="{{ route('app.customers.imports.index') }}" class="h-10 px-3 w-full sm:w-auto bg-white hover:bg-slate-50 text-slate-700 rounded-lg text-sm font-semibold transition-colors inline-flex items-center justify-center gap-2 border border-slate-200 shadow-sm">
                        <i class="ph ph-clock-counter-clockwise text-base"></i> Import History
                    </a>
                @endif
                <button @click="openModal()" class="h-10 px-4 w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition-colors inline-flex items-center justify-center gap-2 shadow-sm sm:min-w-[140px]">
                    <i class="ph ph-plus text-base"></i> Add Customer
                </button>
            </div>
        </div>
    </div>

    @if(session('import_summary'))
        <div class="bg-white border border-slate-200 rounded-xl p-4 text-sm text-slate-700">
            <div class="font-semibold mb-2">Customer import summary</div>
            <div class="flex flex-wrap gap-x-6 gap-y-1">
                <div>Imported: <span class="font-bold">{{ session('import_summary.imported') ?? ((session('import_summary.created') ?? 0) + (session('import_summary.updated') ?? 0)) }}</span></div>
                <div>Failed: <span class="font-bold text-red-700">{{ session('import_summary.failed') ?? (session('import_summary.skipped') ?? 0) }}</span></div>
                <div>Created: <span class="font-bold">{{ session('import_summary.created') ?? 0 }}</span></div>
                <div>Updated: <span class="font-bold">{{ session('import_summary.updated') ?? 0 }}</span></div>
            </div>
            @if(session('import_error_token'))
                <div class="mt-3">
                    <a href="{{ route('app.customers.upload.errors', session('import_error_token')) }}" class="inline-flex items-center gap-2 bg-white hover:bg-slate-50 text-slate-700 px-3 py-2 rounded-lg text-sm font-semibold transition-colors border border-slate-200">
                        <i class="ph ph-download-simple text-lg"></i> Download Error Excel File
                    </a>
                </div>
            @endif
        </div>
    @endif

    @if(session('import_errors'))
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700">
            <div class="font-semibold mb-2">Customer import errors</div>
            <ul class="list-disc pl-5 space-y-1">
                @foreach(session('import_errors') as $err)
                    <li>Row {{ $err['row'] }}: {{ $err['message'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <div class="max-h-[70vh] overflow-y-auto scroll-smooth" x-ref="tableScroll" @scroll.passive="onTableScroll()">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 border-b border-slate-200 sticky top-0 z-10">
                        <tr>
                            <th class="px-6 py-3 font-semibold">#</th>
                            <th class="px-6 py-3 font-semibold">Name</th>
                            <th class="px-6 py-3 font-semibold">Code</th>
                            <th class="px-6 py-3 font-semibold">GSTIN</th>
                            <th class="px-6 py-3 font-semibold">Mobile</th>
                            <th class="px-6 py-3 font-semibold">State</th>
                            <th class="px-6 py-3 font-semibold">Tax Type</th>
                            <th class="px-6 py-3 font-semibold text-right">Credit</th>
                            <th class="px-6 py-3 font-semibold text-center">Status</th>
                            <th class="px-6 py-3 font-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(c, idx) in filteredCustomers" :key="c.id">
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 text-slate-500" x-text="idx + 1"></td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-800" x-text="c.name"></div>
                                    <div class="text-xs text-slate-500 mt-1">
                                        Outstanding: ₹<span x-text="formatMoney6(c.outstanding_amount)"></span>
                                        <template x-if="(parseFloat(c.credit_limit) || 0) > 0">
                                            <span>
                                                <span class="text-slate-400">/</span> Limit: ₹<span x-text="formatMoney6(c.credit_limit)"></span>
                                            </span>
                                        </template>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-600" x-text="c.code || '-'"></td>
                                <td class="px-6 py-4 text-slate-600" x-text="c.gstin || 'Unregistered'"></td>
                                <td class="px-6 py-4 text-slate-600" x-text="c.mobile"></td>
                                <td class="px-6 py-4 text-slate-600" x-text="c.state"></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-[10px] font-bold rounded-full uppercase tracking-wider" :class="c.tax_type === 'CGST_UTGST' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'" x-text="String(c.tax_type || '').replaceAll('_', ' + ')"></span>
                                </td>
                                <td class="px-6 py-4 text-right text-slate-600">
                                    <span x-text="(parseFloat(c.credit_limit) || 0) > 0 ? ('₹' + formatMoney6(c.credit_limit)) : '-'"></span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <form method="POST" :action="`/app/customers/${c.id}/toggle`" class="inline">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                        <input type="hidden" name="_method" value="PATCH">
                                        <label class="nt-switch">
                                            <input type="checkbox" :checked="!!c.is_active" @change="$event.target.form.submit()">
                                            <span class="nt-slider"></span>
                                        </label>
                                    </form>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button" @click="editCustomer(c.id)" class="w-8 h-8 rounded text-indigo-600 hover:bg-indigo-50 flex items-center justify-center transition-colors">
                                            <i class="ph ph-pencil-simple text-lg"></i>
                                        </button>
                                        <form method="POST" :action="`/app/customers/${c.id}`" class="inline" @submit.prevent="if(confirm('Delete this customer?')) $el.submit()">
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
                        <tr x-show="loading && filteredCustomers.length === 0">
                            <td colspan="10" class="px-6 py-6 text-slate-500 text-center">Loading...</td>
                        </tr>
                        <tr x-show="!loading && filteredCustomers.length === 0">
                            <td colspan="10" class="px-6 py-6 text-slate-500 text-center">No customers found</td>
                        </tr>
                        <tr x-show="loadingMore">
                            <td colspan="10" class="px-6 py-6 text-slate-500 text-center">Loading more...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
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
                        Customer Details
                    </h3>
                    <button @click="isModalOpen = false" type="button" class="text-slate-400 hover:text-slate-500 transition-colors">
                        <i class="ph ph-x text-xl"></i>
                    </button>
                </div>
                
                <!-- Body -->
                <div class="px-6 py-5">
                    <form :action="formAction" method="POST" id="customerForm">
            @csrf
            <input type="hidden" name="_method" x-model="formMethod">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1 sm:col-span-2">
                    <label class="text-xs font-medium text-slate-700">Business Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" x-model="form.name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none" style="text-transform: uppercase;">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-medium text-slate-700">Mobile Number <span class="text-red-500">*</span></label>
                    <input type="text" name="mobile" x-model="form.mobile" required inputmode="numeric" maxlength="10" pattern="[0-9]{10}" @input="form.mobile = String(form.mobile || '').replace(/\\D/g, '').slice(0, 10)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    <div x-show="mobileInvalid" class="mt-1 text-xs font-semibold text-red-600 bg-red-50 border border-red-100 px-2 py-1 rounded-md inline-flex items-center gap-1">
                        <i class="ph ph-warning-circle"></i>
                        <span>Number invalid</span>
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-medium text-slate-700">Customer Code</label>
                    <input type="text" name="code" x-model="form.code" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-medium text-slate-700">Credit Limit</label>
                    <input type="number" step="0.000001" min="0" name="credit_limit" x-model="form.credit_limit" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none text-right">
                    <div class="text-[11px] text-slate-500" x-show="form.outstanding_amount !== undefined && form.outstanding_amount !== null">
                        Current outstanding: ₹<span x-text="format6(form.outstanding_amount)"></span>
                    </div>
                </div>
                
                <div class="space-y-1 sm:col-span-2">
                    <div class="flex justify-between items-center">
                        <label class="text-xs font-medium text-slate-700">GSTIN</label>
                        <span x-show="form.gstin.startsWith('26')" class="text-[10px] font-bold text-purple-600 bg-purple-100 px-2 py-0.5 rounded uppercase tracking-wider">UTGST Applied</span>
                    </div>
                    <input type="text" name="gstin" x-model="form.gstin" placeholder="e.g. 26ABCDE1234F1Z5" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none uppercase">
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-medium text-slate-700">PAN Number</label>
                    <input type="text" name="pan" x-model="form.pan" maxlength="10" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none uppercase">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-medium text-slate-700">Email Address</label>
                    <input type="email" name="email" x-model="form.email" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <div class="space-y-1 sm:col-span-2">
                    <label class="text-xs font-medium text-slate-700">Billing Address</label>
                    <textarea name="address" x-model="form.address" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
                </div>
                
                <div class="space-y-1">
                    <label class="text-xs font-medium text-slate-700">City</label>
                    <input type="text" name="city" x-model="form.city" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-medium text-slate-700">District</label>
                    <input type="text" name="district" x-model="form.district" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none uppercase">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-medium text-slate-700">Area</label>
                    <input type="text" name="area_name" x-model="form.area_name" list="area-list" placeholder="Select or type new area" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none" style="text-transform: uppercase;">
                    <datalist id="area-list">
                        @foreach($areas as $area)
                            <option value="{{ $area->name }}"></option>
                        @endforeach
                    </datalist>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-medium text-slate-700">State</label>
                    <input type="text" name="state" x-model="form.state" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                
                <div class="space-y-1">
                    <label class="text-xs font-medium text-slate-700">State Code</label>
                    <input type="text" name="state_code" x-model="form.state_code" placeholder="e.g. 26" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-medium text-slate-700">Place of Supply</label>
                    <input type="text" name="pos_code" x-model="form.pos_code" placeholder="e.g. 26" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-medium text-slate-700">FSSAI No.</label>
                    <input type="text" name="fssai_no" x-model="form.fssai_no" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-medium text-slate-700">PIN Code</label>
                    <input type="text" name="pin_code" x-model="form.pin_code" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" @input="form.pin_code = String(form.pin_code || '').replace(/\\D/g, '').slice(0, 6)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-medium text-slate-700">Country</label>
                    <input type="text" name="country" x-model="form.country" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none uppercase">
                </div>

            </div>
            
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="isModalOpen = false" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">Cancel</button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-sm">Save Customer</button>
            </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function customerManager() {
        const root = document.getElementById('customersPage');
        const areas = root ? JSON.parse(root.dataset.areas || '[]') : [];
        return {
            isModalOpen: false,
            searchQuery: '',
            customers: [],
            customerType: 'all',
            get allCount() {
                return this.customers.length;
            },
            get b2bCount() {
                return this.customers.filter(c => String(c?.gstin || '').trim() !== '').length;
            },
            get b2cCount() {
                return this.customers.filter(c => String(c?.gstin || '').trim() === '').length;
            },
            get filteredCustomers() {
                const type = String(this.customerType || 'all');
                if (type === 'b2b') {
                    return this.customers.filter(c => String(c?.gstin || '').trim() !== '');
                }
                if (type === 'b2c') {
                    return this.customers.filter(c => String(c?.gstin || '').trim() === '');
                }
                return this.customers;
            },
            page: 1,
            perPage: 100000,
            hasMore: true,
            loading: false,
            loadingMore: false,
            searchDebounceTimer: null,
            searchAbortController: null,
            formAction: '{{ route("app.customers.store") }}',
            formMethod: 'POST',
            areas: areas,
            form: {
                id: null, name: '', code: '', gstin: '', pan: '', mobile: '', email: '', credit_limit: '', outstanding_amount: null, address: '', city: '', district: '', area_id: '', area_name: '', state: 'Dadra and Nagar Haveli and Daman & Diu', state_code: '', pos_code: '26', pin_code: '', country: 'INDIA', fssai_no: ''
            },
            get mobileInvalid() {
                const m = String(this.form.mobile || '').trim();
                return m.length > 0 && !/^\d{10}$/.test(m);
            },

            init() {
                this.resetAndLoad();
            },

            onSearchInput() {
                if (this.searchDebounceTimer) clearTimeout(this.searchDebounceTimer);
                this.searchDebounceTimer = setTimeout(() => {
                    this.resetAndLoad();
                }, 300);
            },

            clearSearch() {
                this.searchQuery = '';
                if (this.searchAbortController) {
                    try { this.searchAbortController.abort(); } catch (e) {}
                }
                this.resetAndLoad();
            },

            async resetAndLoad() {
                if (this.searchAbortController) {
                    try { this.searchAbortController.abort(); } catch (e) {}
                }

                this.customers = [];
                this.page = 1;
                this.hasMore = true;
                this.loading = true;
                this.loadingMore = false;

                if (this.$refs.tableScroll) {
                    this.$refs.tableScroll.scrollTop = 0;
                }

                await this.loadPage(1, false);
            },

            async loadPage(page, append) {
                const q = this.searchQuery.trim();

                if (this.searchAbortController) {
                    try { this.searchAbortController.abort(); } catch (e) {}
                }
                this.searchAbortController = new AbortController();

                this.loading = !append;
                this.loadingMore = append;

                try {
                    const url = `/app/api/customers/search?q=${encodeURIComponent(q)}&page=${page}&per_page=${this.perPage}`;
                    const res = await fetch(url, { signal: this.searchAbortController.signal });
                    const json = await res.json();
                    const data = Array.isArray(json?.data) ? json.data : [];
                    const hasMore = !!json?.meta?.has_more;

                    this.customers = append ? [...this.customers, ...data] : data;
                    this.page = page;
                    this.hasMore = hasMore;
                } catch (e) {
                    if (!append) this.customers = [];
                    this.hasMore = false;
                } finally {
                    this.loading = false;
                    this.loadingMore = false;
                }
            },

            async loadMore() {
                if (!this.hasMore || this.loading || this.loadingMore) return;
                await this.loadPage(this.page + 1, true);
            },

            onTableScroll() {
                if (!this.hasMore || this.loading || this.loadingMore) return;
                const el = this.$refs.tableScroll;
                if (!el) return;
                if (el.scrollTop + el.clientHeight >= el.scrollHeight - 200) {
                    this.loadMore();
                }
            },
            
            openModal() {
                this.formAction = '{{ route("app.customers.store") }}';
                this.formMethod = 'POST';
                this.form = { id: null, name: '', code: '', gstin: '', pan: '', mobile: '', email: '', credit_limit: '', outstanding_amount: null, address: '', city: '', district: '', area_id: '', area_name: '', state: 'Dadra and Nagar Haveli and Daman & Diu', state_code: '', pos_code: '26', pin_code: '', country: 'INDIA', fssai_no: '' };
                this.isModalOpen = true;
            },
            
            async editCustomer(id) {
                try {
                    const res = await fetch(`/app/customers/${id}/edit`);
                    const data = await res.json();
                    this.formAction = `/app/customers/${id}`;
                    this.formMethod = 'PUT';
                    this.form = { ...data, mobile: String(data.mobile || ''), credit_limit: data.credit_limit ?? '', outstanding_amount: data.outstanding_amount ?? null, area_id: data.area_id || '', area_name: data.area_name || '', gstin: data.gstin || '' };
                    this.isModalOpen = true;
                } catch (e) {
                    alert('Error loading customer details.');
                }
            },

            format6(v) {
                let n = parseFloat(v);
                if (!isFinite(n)) return '0';
                let fixed = (Math.round(n * 1000000) / 1000000).toFixed(6);
                return fixed.replace(/\.?0+$/, '');
            },

            formatMoney6(v) {
                return this.format6(v);
            }
        }
    }
</script>
@endpush
@endsection
