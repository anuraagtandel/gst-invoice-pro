@extends('layouts.app')

@section('title', 'Schemes')

@section('content')
<div x-data="groupSchemeManager(@js([
    'groups' => ($groups ?? collect())->map(fn($g) => ['id' => $g->id, 'name' => $g->name])->values(),
    'products' => $products ?? [],
    'errors' => $errors->all(),
    'old' => [
        'scheme_id' => old('scheme_id', ''),
        'name' => old('name', ''),
        'product_group_id' => old('product_group_id', ''),
        'free_product_id' => old('free_product_id', ''),
        'is_active' => old('is_active') ? true : false,
        'slabs' => old('slabs', []),
    ],
]))" class="space-y-6">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-syne font-semibold text-lg text-slate-800">Schemes</h3>
            <p class="text-sm text-slate-500">Create schemes linked to product groups with slab configuration.</p>
        </div>
        <div class="w-full sm:w-auto flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
            <form method="GET" action="{{ route('app.group-schemes.index') }}" class="relative w-full sm:w-80">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search scheme..." class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
            </form>
            <button @click="openModal()" class="w-full sm:w-auto shrink-0 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors inline-flex items-center justify-center gap-2 shadow-sm whitespace-nowrap min-w-[150px]">
                <i class="ph ph-plus text-lg"></i> Add Scheme
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($schemes->isEmpty())
            @include('partials._empty_state', ['icon' => 'ph-ticket', 'title' => 'No schemes found', 'message' => 'Create a scheme to configure free items slabs.'])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold w-20">#</th>
                            <th class="px-6 py-3 font-semibold">Scheme Name</th>
                            <th class="px-6 py-3 font-semibold">Product Group</th>
                            <th class="px-6 py-3 font-semibold">Free Product</th>
                            <th class="px-6 py-3 font-semibold">Slabs</th>
                            <th class="px-6 py-3 font-semibold">Status</th>
                            <th class="px-6 py-3 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($schemes as $scheme)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 text-slate-500">{{ $loop->iteration }}</td>
                            <td class="px-6 py-4 font-semibold text-slate-800">{{ $scheme->name }}</td>
                            <td class="px-6 py-4 text-slate-700">{{ $scheme->productGroup?->name ?? '-' }}</td>
                            <td class="px-6 py-4 text-slate-700">{{ $scheme->freeProduct?->name ? trim($scheme->freeProduct->name . ' ' . ($scheme->freeProduct->volume ?? '')) : '-' }}</td>
                            <td class="px-6 py-4 text-slate-600">
                                @php
                                    $slabText = $scheme->slabs->map(fn($s) => "{$s->min_qty_ct}-{$s->max_qty_ct} CT → {$s->free_qty_units} UN")->implode(', ');
                                @endphp
                                <span title="{{ $slabText }}">{{ $slabText ?: '-' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-[10px] font-bold rounded uppercase tracking-wider {{ $scheme->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $scheme->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button type="button" @click="editScheme({{ $scheme->id }})" class="w-8 h-8 rounded text-indigo-600 hover:bg-indigo-50 inline-flex items-center justify-center transition-colors" title="Edit Scheme">
                                        <i class="ph ph-pencil-simple text-lg"></i>
                                    </button>
                                    <form action="{{ route('app.group-schemes.toggle', $scheme->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="w-8 h-8 rounded {{ $scheme->is_active ? 'text-amber-700 hover:bg-amber-50' : 'text-emerald-700 hover:bg-emerald-50' }} inline-flex items-center justify-center transition-colors" title="Toggle Status">
                                            <i class="ph ph-power text-lg"></i>
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
    </div>

    <div
        x-show="isModalOpen"
        class="fixed inset-0 z-[100] overflow-y-auto"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
        x-cloak
    >
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
                class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-4xl"
            >
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="font-syne font-semibold text-lg text-slate-800" id="modal-title" x-text="form.id ? 'Edit Scheme' : 'Add Scheme'"></h3>
                    <button @click="isModalOpen = false" type="button" class="text-slate-400 hover:text-slate-500 transition-colors">
                        <i class="ph ph-x text-xl"></i>
                    </button>
                </div>

                <div class="px-6 py-5">
                    <form :action="formAction" method="POST">
                        @csrf
                        <input type="hidden" name="scheme_id" :value="form.id">
                        <template x-if="formMethod === 'PUT'">
                            <input type="hidden" name="_method" value="PUT">
                        </template>

                        <div class="space-y-5">
                            <template x-if="errors.length > 0">
                                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-left">
                                    <div class="text-sm font-bold text-red-700">Please fix the following:</div>
                                    <ul class="mt-1 text-xs text-red-700 list-disc pl-5 space-y-0.5">
                                        <template x-for="(msg, i) in errors" :key="i">
                                            <li x-text="msg"></li>
                                        </template>
                                    </ul>
                                </div>
                            </template>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 mb-1">Scheme Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="name" x-model="form.name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 mb-1">Product Group <span class="text-red-500">*</span></label>
                                    <select name="product_group_id" x-model="form.product_group_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                                        <option value="">Select group...</option>
                                        <template x-for="g in groups" :key="g.id">
                                            <option :value="String(g.id)" x-text="g.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 mb-1">Free Product <span class="text-red-500">*</span></label>
                                    <select name="free_product_id" x-model="form.free_product_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                                        <option value="">Select product...</option>
                                        <template x-for="p in products" :key="p.id">
                                            <option :value="String(p.id)" x-text="p.label"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>

                            <div class="flex items-center justify-between bg-slate-50 border border-slate-100 rounded-lg px-3 py-2">
                                <div>
                                    <div class="text-sm font-semibold text-slate-800">Status</div>
                                    <div class="text-xs text-slate-500">Active / Inactive</div>
                                </div>
                                <label class="nt-switch">
                                    <input type="checkbox" name="is_active" value="1" x-model="form.is_active">
                                    <span class="nt-slider"></span>
                                </label>
                            </div>

                            <div class="border border-slate-200 rounded-lg overflow-hidden">
                                <div class="bg-slate-50 px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                                    <div>
                                        <div class="text-sm font-semibold text-slate-800">Slabs</div>
                                        <div class="text-xs text-slate-500">Min/Max in CT, free qty in Units</div>
                                    </div>
                                    <button type="button" @click="addSlab()" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">+ Add Slab</button>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left text-sm whitespace-nowrap">
                                        <thead class="bg-white text-slate-500 border-b border-slate-200">
                                            <tr>
                                                <th class="px-4 py-3 font-semibold">Min Qty (CT)</th>
                                                <th class="px-4 py-3 font-semibold">Max Qty (CT)</th>
                                                <th class="px-4 py-3 font-semibold">Free Qty (Units)</th>
                                                <th class="px-4 py-3 font-semibold w-16"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            <template x-for="(s, idx) in form.slabs" :key="idx">
                                                <tr class="hover:bg-slate-50/50">
                                                    <td class="px-4 py-3">
                                                        <input type="number" min="1" step="1" :name="`slabs[${idx}][min_qty_ct]`" x-model.number="s.min_qty_ct" class="w-32 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <input type="number" min="1" step="1" :name="`slabs[${idx}][max_qty_ct]`" x-model.number="s.max_qty_ct" class="w-32 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <input type="number" min="1" step="1" :name="`slabs[${idx}][free_qty_units]`" x-model.number="s.free_qty_units" class="w-36 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                                    </td>
                                                    <td class="px-4 py-3 text-right">
                                                        <button type="button" @click="removeSlab(idx)" class="w-8 h-8 rounded text-red-600 hover:bg-red-50 inline-flex items-center justify-center transition-colors" title="Remove">
                                                            <i class="ph ph-trash text-lg"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-slate-100">
                            <button type="button" @click="isModalOpen = false" class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">Cancel</button>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-sm" x-text="form.id ? 'Update' : 'Save'"></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function groupSchemeManager(initial = {}) {
        return {
            isModalOpen: false,
            formAction: '{{ route("app.group-schemes.store") }}',
            formMethod: 'POST',
            groups: Array.isArray(initial.groups) ? initial.groups : [],
            products: Array.isArray(initial.products) ? initial.products : [],
            errors: Array.isArray(initial.errors) ? initial.errors : [],
            form: {
                id: null,
                name: '',
                product_group_id: '',
                free_product_id: '',
                is_active: true,
                slabs: [{ min_qty_ct: 1, max_qty_ct: 1, free_qty_units: 1 }],
            },
            init() {
                const old = initial.old || {};
                if (this.errors.length > 0) {
                    const sid = old.scheme_id ? String(old.scheme_id) : '';
                    this.formAction = sid ? `/app/group-schemes/${sid}` : '{{ route("app.group-schemes.store") }}';
                    this.formMethod = sid ? 'PUT' : 'POST';
                    this.form = {
                        id: sid || null,
                        name: old.name || '',
                        product_group_id: old.product_group_id ? String(old.product_group_id) : '',
                        free_product_id: old.free_product_id ? String(old.free_product_id) : '',
                        is_active: !!old.is_active,
                        slabs: Array.isArray(old.slabs) && old.slabs.length > 0 ? old.slabs : [{ min_qty_ct: 1, max_qty_ct: 1, free_qty_units: 1 }],
                    };
                    this.isModalOpen = true;
                }
            },
            openModal() {
                this.formAction = '{{ route("app.group-schemes.store") }}';
                this.formMethod = 'POST';
                this.form = { id: null, name: '', product_group_id: '', free_product_id: '', is_active: true, slabs: [{ min_qty_ct: 1, max_qty_ct: 1, free_qty_units: 1 }] };
                this.errors = [];
                this.isModalOpen = true;
            },
            addSlab() {
                this.form.slabs.push({ min_qty_ct: 1, max_qty_ct: 1, free_qty_units: 1 });
            },
            removeSlab(idx) {
                this.form.slabs.splice(idx, 1);
                if (this.form.slabs.length === 0) {
                    this.form.slabs = [{ min_qty_ct: 1, max_qty_ct: 1, free_qty_units: 1 }];
                }
            },
            async editScheme(id) {
                try {
                    const res = await fetch(`/app/group-schemes/${id}/edit`);
                    const data = await res.json();
                    this.formAction = `/app/group-schemes/${id}`;
                    this.formMethod = 'PUT';
                    this.form = {
                        id: data.id,
                        name: data.name || '',
                        product_group_id: data.product_group_id ? String(data.product_group_id) : '',
                        free_product_id: data.free_product_id ? String(data.free_product_id) : '',
                        is_active: !!data.is_active,
                        slabs: Array.isArray(data.slabs) && data.slabs.length > 0 ? data.slabs : [{ min_qty_ct: 1, max_qty_ct: 1, free_qty_units: 1 }],
                    };
                    this.errors = [];
                    this.isModalOpen = true;
                } catch (e) {
                    alert('Error loading scheme details.');
                }
            },
        }
    }
</script>
@endpush
@endsection
