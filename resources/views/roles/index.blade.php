@extends('layouts.app')

@section('title', 'Role Master')

@section('content')
<div x-data="roleManager()" class="space-y-6">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-syne font-semibold text-lg text-slate-800">Role Master</h3>
            <p class="text-sm text-slate-500">Manage roles for the system.</p>
        </div>
        <div class="w-full sm:w-auto flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
            <form method="GET" action="{{ route('app.roles.index') }}" class="relative w-full sm:w-80">
                <div class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                    <i class="ph ph-magnifying-glass text-lg"></i>
                </div>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search role..." class="h-10 w-full pl-10 pr-4 border border-slate-200 rounded-lg text-sm leading-tight focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
            </form>
            <button @click="openModal()" class="h-10 w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white px-4 rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-2 shadow-sm whitespace-nowrap">
                <i class="ph ph-plus text-lg"></i><span class="whitespace-nowrap">Add Role</span>
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($roles->isEmpty())
            @include('partials._empty_state', ['icon' => 'ph-identification-card', 'title' => 'No roles found', 'message' => 'Create a role to manage access labels.'])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold w-20">#</th>
                            <th class="px-6 py-3 font-semibold">Role Name</th>
                            <th class="px-6 py-3 font-semibold">Description</th>
                            <th class="px-6 py-3 font-semibold">Status</th>
                            <th class="px-6 py-3 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($roles as $role)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 text-slate-500">{{ $loop->iteration }}</td>
                            <td class="px-6 py-4 font-semibold text-slate-800">{{ $role->name }}</td>
                            <td class="px-6 py-4 text-slate-600 max-w-[420px] truncate" title="{{ $role->description ?? '' }}">{{ $role->description ?: '-' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-[10px] font-bold rounded uppercase tracking-wider {{ $role->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $role->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button type="button" @click="editRole({{ $role->id }})" class="w-8 h-8 rounded text-indigo-600 hover:bg-indigo-50 inline-flex items-center justify-center transition-colors" title="Edit Role">
                                        <i class="ph ph-pencil-simple text-lg"></i>
                                    </button>
                                    @php
                                        $roleName = strtolower(trim((string) $role->name));
                                        $roleNameCompact = str_replace([' ', '_', '-'], '', $roleName);
                                        $isProtected = in_array($roleNameCompact, ['admin', 'superadmin', 'salesman'], true);
                                    @endphp
                                    @if($isProtected)
                                        <button type="button" class="w-8 h-8 rounded text-slate-300 inline-flex items-center justify-center cursor-not-allowed" title="Cannot delete this role">
                                            <i class="ph ph-trash text-lg"></i>
                                        </button>
                                    @else
                                        <form action="{{ route('app.roles.destroy', $role->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this role? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-8 h-8 rounded text-red-600 hover:bg-red-50 inline-flex items-center justify-center transition-colors" title="Delete Role">
                                                <i class="ph ph-trash text-lg"></i>
                                            </button>
                                        </form>
                                    @endif
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
                class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg"
            >
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="font-syne font-semibold text-lg text-slate-800" id="modal-title" x-text="form.id ? 'Edit Role' : 'Add Role'"></h3>
                    <button @click="isModalOpen = false" type="button" class="text-slate-400 hover:text-slate-500 transition-colors">
                        <i class="ph ph-x text-xl"></i>
                    </button>
                </div>

                <div class="px-6 py-5">
                    <form :action="formAction" method="POST">
                        @csrf
                        <template x-if="formMethod === 'PUT'">
                            <input type="hidden" name="_method" value="PUT">
                        </template>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Role Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" x-model="form.name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Description</label>
                                <textarea name="description" x-model="form.description" rows="3" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
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
                                <div class="bg-slate-50 px-4 py-3 border-b border-slate-200">
                                    <div class="text-sm font-semibold text-slate-800">Permissions</div>
                                    <div class="text-xs text-slate-500">Select allowed actions per module</div>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left text-[12px] whitespace-nowrap">
                                        <thead class="bg-white text-slate-500 border-b border-slate-200">
                                            <tr>
                                                <th class="px-4 py-2 font-bold uppercase tracking-wider">Module</th>
                                                @foreach(($actions ?? ['View','Add','Edit','Delete']) as $act)
                                                    <th class="px-4 py-2 font-bold uppercase tracking-wider text-center">{{ $act }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            <template x-for="row in permissionsMatrix" :key="row.module">
                                                <tr class="hover:bg-slate-50/50">
                                                    <td class="px-4 py-2 font-semibold text-slate-800" x-text="row.module"></td>
                                                    <template x-for="a in row.actions" :key="`${row.module}-${a.action}`">
                                                        <td class="px-4 py-2 text-center">
                                                            <input type="checkbox"
                                                                :disabled="!a.code"
                                                                :name="a.code ? 'permission_codes[]' : null"
                                                                :value="a.code"
                                                                x-model="form.permission_codes"
                                                                class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 disabled:opacity-30">
                                                        </td>
                                                    </template>
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
<div id="role-permissions-matrix" class="hidden" data-json="{{ e(json_encode($permissionsMatrix ?? [])) }}"></div>
<script>
    function roleManager() {
        const permissionsMatrixEl = document.getElementById('role-permissions-matrix');
        return {
            isModalOpen: false,
            formAction: '{{ route("app.roles.store") }}',
            formMethod: 'POST',
            permissionsMatrix: JSON.parse(permissionsMatrixEl?.dataset?.json || '[]'),
            form: {
                id: null,
                name: '',
                description: '',
                is_active: true,
                permission_codes: [],
            },
            openModal() {
                this.formAction = '{{ route("app.roles.store") }}';
                this.formMethod = 'POST';
                this.form = { id: null, name: '', description: '', is_active: true, permission_codes: [] };
                this.isModalOpen = true;
            },
            async editRole(id) {
                try {
                    const res = await fetch(`/app/roles/${id}/edit`);
                    const data = await res.json();
                    this.formAction = `/app/roles/${id}`;
                    this.formMethod = 'PUT';
                    this.form = {
                        id: data.id,
                        name: data.name || '',
                        description: data.description || '',
                        is_active: !!data.is_active,
                        permission_codes: Array.isArray(data.permission_codes) ? data.permission_codes : [],
                    };
                    this.isModalOpen = true;
                } catch (e) {
                    alert('Error loading role details.');
                }
            },
        }
    }
</script>
@endpush
@endsection
