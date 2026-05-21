@extends('layouts.app')

@section('title', 'Suppliers')

@section('content')
@php
    $highlightId = session('highlight_id');
    $supplierInitPayload = [
        'has_errors' => $errors->any(),
        'old' => [
            'id' => (string) old('id', ''),
            '_method' => (string) old('_method', 'POST'),
            'name' => (string) old('name', ''),
            'gstin' => (string) old('gstin', ''),
            'pan' => (string) old('pan', ''),
            'mobile' => (string) old('mobile', ''),
            'email' => (string) old('email', ''),
            'address' => (string) old('address', ''),
            'city' => (string) old('city', ''),
            'state' => (string) old('state', 'Daman'),
            'state_code' => (string) old('state_code', ''),
            'pos_code' => (string) old('pos_code', ''),
            'is_active' => (bool) old('is_active', 1),
        ],
    ];
    $supplierInitJson = json_encode($supplierInitPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
@endphp
<div id="suppliersPage" data-init='{{ $supplierInitJson }}' x-data="supplierManager()" x-init="init()" class="space-y-6">

    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl flex items-center gap-3">
            <i class="ph ph-check-circle text-lg"></i>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl flex items-center gap-3">
            <i class="ph ph-warning-circle text-lg"></i>
            <span class="text-sm font-medium">{{ session('error') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl">
            <div class="flex items-center gap-3">
                <i class="ph ph-warning-circle text-lg"></i>
                <span class="text-sm font-medium">Please fix the highlighted errors and try again.</span>
            </div>
            <div class="mt-2 text-sm">
                <ul class="list-disc pl-6 space-y-1">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-syne font-semibold text-lg text-slate-800">Suppliers</h3>
            <p class="text-sm text-slate-500">Manage supplier details for purchases.</p>
        </div>
        <div class="w-full sm:w-auto flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
            <form method="GET" action="{{ route('app.suppliers.index') }}" class="relative w-full sm:w-80">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search supplier..." class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
            </form>
            <button @click="openModal()" class="w-full sm:w-auto shrink-0 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors inline-flex items-center justify-center gap-2 shadow-sm whitespace-nowrap min-w-[160px]">
                <i class="ph ph-plus text-lg"></i> Add Supplier
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($suppliers->isEmpty())
            @include('partials._empty_state', ['icon' => 'ph-truck', 'title' => 'No suppliers found', 'message' => 'Add your first supplier to record purchases.'])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold">#</th>
                            <th class="px-6 py-3 font-semibold">Name</th>
                            <th class="px-6 py-3 font-semibold">GSTIN</th>
                            <th class="px-6 py-3 font-semibold">Mobile</th>
                            <th class="px-6 py-3 font-semibold">State</th>
                            <th class="px-6 py-3 font-semibold text-center">Status</th>
                            <th class="px-6 py-3 font-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($suppliers as $supplier)
                        <tr class="hover:bg-slate-50/50 transition-colors {{ $highlightId == $supplier->id ? 'bg-emerald-50' : '' }}">
                            <td class="px-6 py-4 text-slate-500">{{ $loop->iteration + $suppliers->firstItem() - 1 }}</td>
                            <td class="px-6 py-4 font-medium text-slate-800">{{ $supplier->name }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $supplier->gstin ?? '-' }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $supplier->mobile ?? '-' }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $supplier->state ?? '-' }}</td>
                            <td class="px-6 py-4 text-center">
                                <form action="{{ route('app.suppliers.toggle', $supplier->id) }}" method="POST" class="inline">
                                    @csrf @method('PATCH')
                                    <label class="nt-switch">
                                        <input type="checkbox" onChange="this.form.submit()" {{ $supplier->is_active ? 'checked' : '' }}>
                                        <span class="nt-slider"></span>
                                    </label>
                                </form>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button @click="editSupplier({{ $supplier->id }})" class="w-8 h-8 rounded text-indigo-600 hover:bg-indigo-50 flex items-center justify-center transition-colors">
                                        <i class="ph ph-pencil-simple text-lg"></i>
                                    </button>
                                    <form action="{{ route('app.suppliers.destroy', $supplier->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this supplier?');">
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
                {{ $suppliers->links() }}
            </div>
        @endif
    </div>

    <div x-show="isModalOpen" class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
        <div x-show="isModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="isModalOpen = false"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div x-show="isModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl">
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="font-syne font-semibold text-lg text-slate-800" id="modal-title">Supplier Details</h3>
                    <button @click="isModalOpen = false" type="button" class="text-slate-400 hover:text-slate-500 transition-colors">
                        <i class="ph ph-x text-xl"></i>
                    </button>
                </div>

                <div class="px-6 py-5">
                    <form :action="formAction" method="POST" id="supplierForm">
                        @csrf
                        <input type="hidden" name="_method" x-model="formMethod">
                        <input type="hidden" name="id" :value="form.id">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1 sm:col-span-2">
                                <label class="text-xs font-medium text-slate-700">Supplier Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" x-model="form.name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-slate-700">GSTIN</label>
                                <input type="text" name="gstin" x-model="form.gstin" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm uppercase focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-slate-700">PAN</label>
                                <input type="text" name="pan" x-model="form.pan" maxlength="10" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm uppercase focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-slate-700">Mobile</label>
                                <input type="text" name="mobile" x-model="form.mobile" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>
                            <div class="space-y-1 sm:col-span-2">
                                <label class="text-xs font-medium text-slate-700">Email</label>
                                <input type="email" name="email" x-model="form.email" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>
                            <div class="space-y-1 sm:col-span-2">
                                <label class="text-xs font-medium text-slate-700">Address</label>
                                <textarea name="address" x-model="form.address" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-slate-700">City</label>
                                <input type="text" name="city" x-model="form.city" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-slate-700">State</label>
                                <input type="text" name="state" x-model="form.state" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-slate-700">State Code</label>
                                <input type="text" name="state_code" x-model="form.state_code" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-slate-700">POS Code</label>
                                <input type="text" name="pos_code" x-model="form.pos_code" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>

                            <div class="space-y-1 sm:col-span-2 flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-200 mt-2">
                                <span class="text-sm font-semibold text-slate-700">Active Supplier</span>
                                <label class="nt-switch">
                                    <input type="checkbox" x-model="form.is_active">
                                    <span class="nt-slider"></span>
                                </label>
                                <input type="hidden" name="is_active" :value="form.is_active ? 1 : 0">
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-3">
                            <button type="button" @click="isModalOpen = false" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">Cancel</button>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-sm">Save Supplier</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function supplierManager() {
        const el = document.getElementById('suppliersPage');
        const init = el ? JSON.parse(el.dataset.init || '{}') : {};
        return {
            isModalOpen: false,
            formAction: '{{ route("app.suppliers.store") }}',
            formMethod: 'POST',
            form: {
                id: null, name: '', gstin: '', pan: '', mobile: '', email: '', address: '', city: '', state: 'Daman', state_code: '', pos_code: '', is_active: true
            },
            openModal() {
                this.formAction = '{{ route("app.suppliers.store") }}';
                this.formMethod = 'POST';
                this.form = { id: null, name: '', gstin: '', pan: '', mobile: '', email: '', address: '', city: '', state: 'Daman', state_code: '', pos_code: '', is_active: true };
                this.isModalOpen = true;
            },
            init() {
                const hasErrors = !!init.has_errors;
                if (!hasErrors) return;

                const oldId = String(init.old?.id || '');
                const oldMethod = String(init.old?._method || 'POST');

                if (oldId) {
                    this.formAction = `/app/suppliers/${oldId}`;
                    this.formMethod = oldMethod || 'PUT';
                } else {
                    this.formAction = '{{ route("app.suppliers.store") }}';
                    this.formMethod = 'POST';
                }

                this.form = {
                    id: oldId || null,
                    name: String(init.old?.name || ''),
                    gstin: String(init.old?.gstin || ''),
                    pan: String(init.old?.pan || ''),
                    mobile: String(init.old?.mobile || ''),
                    email: String(init.old?.email || ''),
                    address: String(init.old?.address || ''),
                    city: String(init.old?.city || ''),
                    state: String(init.old?.state || 'Daman'),
                    state_code: String(init.old?.state_code || ''),
                    pos_code: String(init.old?.pos_code || ''),
                    is_active: !!init.old?.is_active
                };

                this.isModalOpen = true;
            },
            async editSupplier(id) {
                try {
                    const res = await fetch(`/app/suppliers/${id}/edit`);
                    const data = await res.json();
                    this.formAction = `/app/suppliers/${id}`;
                    this.formMethod = 'PUT';
                    this.form = {
                        id: data.id,
                        name: data.name || '',
                        gstin: data.gstin || '',
                        pan: data.pan || '',
                        mobile: data.mobile || '',
                        email: data.email || '',
                        address: data.address || '',
                        city: data.city || '',
                        state: data.state || 'Daman',
                        state_code: data.state_code || '',
                        pos_code: data.pos_code || '',
                        is_active: !!data.is_active
                    };
                    this.isModalOpen = true;
                } catch (e) {
                    alert('Error loading supplier data');
                }
            }
        }
    }
</script>
@endpush
@endsection
