@extends('layouts.app')

@section('title', 'Product Groups')

@section('content')
<div x-data="productGroupManager(@js([
    'products' => $products ?? [],
    'errors' => $errors->all(),
    'old' => [
        'group_id' => old('group_id', ''),
        'name' => old('name', ''),
        'description' => old('description', ''),
        'product_ids' => old('product_ids', []),
    ],
]))" class="space-y-6">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-syne font-semibold text-lg text-slate-800">Product Groups</h3>
            <p class="text-sm text-slate-500">Create groups and map multiple products.</p>
        </div>
        <div class="w-full sm:w-auto flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
            <form method="GET" action="{{ route('app.product-groups.index') }}" class="relative w-full sm:w-80">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search group..." class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
            </form>
            <button @click="openModal()" class="w-full sm:w-auto shrink-0 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors inline-flex items-center justify-center gap-2 shadow-sm whitespace-nowrap min-w-[140px]">
                <i class="ph ph-plus text-lg"></i> Add Group
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($groups->isEmpty())
            @include('partials._empty_state', ['icon' => 'ph-folders', 'title' => 'No product groups found', 'message' => 'Create a product group to map multiple products.'])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold w-20">#</th>
                            <th class="px-6 py-3 font-semibold">Group Name</th>
                            <th class="px-6 py-3 font-semibold">Description</th>
                            <th class="px-6 py-3 font-semibold">Products</th>
                            <th class="px-6 py-3 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($groups as $group)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 text-slate-500">{{ $loop->iteration }}</td>
                            <td class="px-6 py-4 font-semibold text-slate-800">{{ $group->name }}</td>
                            <td class="px-6 py-4 text-slate-600 max-w-[420px] truncate" title="{{ $group->description ?? '' }}">{{ $group->description ?: '-' }}</td>
                            <td class="px-6 py-4">
                                @php
                                    $names = $group->products->map(function ($p) { return trim($p->name . ' ' . ($p->volume ?? '')); })->filter()->values();
                                    $title = $names->implode(', ');
                                    $display = $names->take(3)->implode(', ');
                                    if ($names->count() > 3) {
                                        $display .= ' +' . ($names->count() - 3) . ' more';
                                    }
                                @endphp
                                <div class="text-slate-700 font-semibold" title="{{ $title }}">{{ $display ?: '-' }}</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button type="button" @click="editGroup({{ $group->id }})" class="w-8 h-8 rounded text-indigo-600 hover:bg-indigo-50 inline-flex items-center justify-center transition-colors" title="Edit Group">
                                    <i class="ph ph-pencil-simple text-lg"></i>
                                </button>
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
                    <h3 class="font-syne font-semibold text-lg text-slate-800" id="modal-title" x-text="form.id ? 'Edit Product Group' : 'Add Product Group'"></h3>
                    <button @click="isModalOpen = false" type="button" class="text-slate-400 hover:text-slate-500 transition-colors">
                        <i class="ph ph-x text-xl"></i>
                    </button>
                </div>

                <div class="px-6 py-5">
                    <form :action="formAction" method="POST">
                        @csrf
                        <input type="hidden" name="group_id" :value="form.id">
                        <template x-if="formMethod === 'PUT'">
                            <input type="hidden" name="_method" value="PUT">
                        </template>

                        <div class="space-y-4">
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

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 mb-1">Group Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="name" x-model="form.name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 mb-1">Description</label>
                                    <input type="text" name="description" x-model="form.description" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Products</label>
                                <div class="space-y-2">
                                    <div class="relative">
                                        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
                                        <input type="text" x-model="productSearch" placeholder="Search product..." class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                    </div>
                                    <div class="border border-slate-200 rounded-lg overflow-hidden bg-white">
                                        <div class="max-h-[320px] overflow-y-auto divide-y divide-slate-100">
                                            <template x-for="p in filteredProducts()" :key="p.id">
                                                <label class="flex items-center gap-3 px-3 py-2 hover:bg-slate-50 cursor-pointer">
                                                    <input type="checkbox" name="product_ids[]" :value="String(p.id)" x-model="form.product_ids" class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500">
                                                    <span class="text-sm text-slate-800" x-text="p.label"></span>
                                                </label>
                                            </template>
                                            <div class="px-3 py-3 text-xs text-slate-500" x-show="filteredProducts().length === 0">No products found.</div>
                                        </div>
                                    </div>
                                    <div class="text-[11px] text-slate-500">Select products using the checkboxes.</div>
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
    function productGroupManager(initial = {}) {
        return {
            isModalOpen: false,
            formAction: '{{ route("app.product-groups.store") }}',
            formMethod: 'POST',
            products: Array.isArray(initial.products) ? initial.products : [],
            productSearch: '',
            errors: Array.isArray(initial.errors) ? initial.errors : [],
            form: {
                id: null,
                name: '',
                description: '',
                product_ids: [],
            },
            filteredProducts() {
                const q = (this.productSearch || '').toLowerCase().trim();
                if (q === '') return this.products;
                return this.products.filter(p => String(p.label || '').toLowerCase().includes(q));
            },
            init() {
                const old = initial.old || {};
                if (this.errors.length > 0) {
                    const gid = old.group_id ? String(old.group_id) : '';
                    this.formAction = gid ? `/app/product-groups/${gid}` : '{{ route("app.product-groups.store") }}';
                    this.formMethod = gid ? 'PUT' : 'POST';
                    this.form = {
                        id: gid || null,
                        name: old.name || '',
                        description: old.description || '',
                        product_ids: Array.isArray(old.product_ids) ? old.product_ids.map(String) : [],
                    };
                    this.productSearch = '';
                    this.isModalOpen = true;
                }
            },
            openModal() {
                this.formAction = '{{ route("app.product-groups.store") }}';
                this.formMethod = 'POST';
                this.form = { id: null, name: '', description: '', product_ids: [] };
                this.productSearch = '';
                this.errors = [];
                this.isModalOpen = true;
            },
            async editGroup(id) {
                try {
                    const res = await fetch(`/app/product-groups/${id}/edit`);
                    const data = await res.json();
                    this.formAction = `/app/product-groups/${id}`;
                    this.formMethod = 'PUT';
                    this.form = {
                        id: data.id,
                        name: data.name || '',
                        description: data.description || '',
                        product_ids: Array.isArray(data.product_ids) ? data.product_ids.map(String) : [],
                    };
                    this.productSearch = '';
                    this.errors = [];
                    this.isModalOpen = true;
                } catch (e) {
                    alert('Error loading group details.');
                }
            },
        }
    }
</script>
@endpush
@endsection
