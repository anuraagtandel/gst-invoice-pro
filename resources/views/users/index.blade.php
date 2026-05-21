@extends('layouts.app')

@section('title', 'Users')

@section('content')
<div x-data="userManager(@js([
    'errors' => $errors->all(),
    'roles' => ($roles ?? collect())->map(fn($r) => ['id' => $r->id, 'name' => $r->name])->values(),
    'old' => [
        'user_id' => old('user_id', ''),
        'name' => old('name', ''),
        'email' => old('email', ''),
        'role_id' => old('role_id', ''),
        'is_active' => old('is_active') ? true : false,
    ],
]))" class="space-y-6">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-syne font-semibold text-lg text-slate-800">Users</h3>
            <p class="text-sm text-slate-500">Manage system users and assign roles.</p>
        </div>
        <div class="w-full sm:w-auto flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
            <form method="GET" action="{{ route('app.users.index') }}" class="relative w-full sm:w-80">
                <div class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                    <i class="ph ph-magnifying-glass text-lg"></i>
                </div>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search user..." class="h-10 w-full pl-10 pr-4 border border-slate-200 rounded-lg text-sm leading-tight focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
            </form>
            <button @click="openModal()" class="h-10 w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white px-4 rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-2 shadow-sm whitespace-nowrap">
                <i class="ph ph-plus text-lg"></i><span class="whitespace-nowrap">Add User</span>
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($users->isEmpty())
            @include('partials._empty_state', ['icon' => 'ph-users-three', 'title' => 'No users found', 'message' => 'Create a user to allow staff access.'])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Name</th>
                            <th class="px-6 py-3 font-semibold">Email</th>
                            <th class="px-6 py-3 font-semibold">Role</th>
                            <th class="px-6 py-3 font-semibold">Status</th>
                            <th class="px-6 py-3 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($users as $user)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 font-semibold text-slate-800">{{ $user->name }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $user->email }}</td>
                            <td class="px-6 py-4 text-slate-700">{{ $user->role?->name ?? 'Admin' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-[10px] font-bold rounded uppercase tracking-wider {{ ($user->is_active ?? true) ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ ($user->is_active ?? true) ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button type="button" @click="editUser({{ $user->id }})" class="w-8 h-8 rounded text-indigo-600 hover:bg-indigo-50 inline-flex items-center justify-center transition-colors" title="Edit User">
                                    <i class="ph ph-pencil-simple text-lg"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-200">
                {{ $users->links() }}
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
                    <h3 class="font-syne font-semibold text-lg text-slate-800" id="modal-title" x-text="form.id ? 'Edit User' : 'Add User'"></h3>
                    <button @click="isModalOpen = false" type="button" class="text-slate-400 hover:text-slate-500 transition-colors">
                        <i class="ph ph-x text-xl"></i>
                    </button>
                </div>

                <div class="px-6 py-5">
                    <form :action="formAction" method="POST">
                        @csrf
                        <input type="hidden" name="user_id" :value="form.id">
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
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" x-model="form.name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Email <span class="text-red-500">*</span></label>
                                <input type="email" name="email" x-model="form.email" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Role <span class="text-red-500">*</span></label>
                                <select name="role_id" x-model="form.role_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                                    <option value="">Select role...</option>
                                    <template x-for="r in roles" :key="r.id">
                                        <option :value="String(r.id)" x-text="r.name"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 mb-1" x-text="form.id ? 'New Password' : 'Password'"></label>
                                    <div data-password-field class="relative">
                                        <input type="password" name="password" x-model="form.password" :required="!form.id" class="w-full px-3 py-2 pr-10 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                        <button type="button" data-password-toggle aria-label="Show password" class="absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-slate-600">
                                            <i class="ph ph-eye text-lg"></i>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-700 mb-1" x-text="form.id ? 'Confirm New Password' : 'Confirm Password'"></label>
                                    <div data-password-field class="relative">
                                        <input type="password" name="password_confirmation" x-model="form.password_confirmation" :required="!form.id" class="w-full px-3 py-2 pr-10 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                        <button type="button" data-password-toggle aria-label="Show password" class="absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-slate-600">
                                            <i class="ph ph-eye text-lg"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="text-[11px] text-slate-500" x-show="form.id">Leave password blank to keep the current password.</div>

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
    function userManager(initial = {}) {
        return {
            isModalOpen: false,
            formAction: '{{ route("app.users.store") }}',
            formMethod: 'POST',
            roles: Array.isArray(initial.roles) ? initial.roles : [],
            errors: Array.isArray(initial.errors) ? initial.errors : [],
            form: {
                id: null,
                name: '',
                email: '',
                role_id: '',
                password: '',
                password_confirmation: '',
                is_active: true,
            },
            init() {
                const old = initial.old || {};
                if (this.errors.length > 0) {
                    const userId = old.user_id ? String(old.user_id) : '';
                    this.formAction = userId ? `/app/users/${userId}` : '{{ route("app.users.store") }}';
                    this.formMethod = userId ? 'PUT' : 'POST';
                    this.form = {
                        id: userId || null,
                        name: old.name || '',
                        email: old.email || '',
                        role_id: old.role_id ? String(old.role_id) : '',
                        password: '',
                        password_confirmation: '',
                        is_active: !!old.is_active,
                    };
                    this.isModalOpen = true;
                }
            },
            openModal() {
                this.formAction = '{{ route("app.users.store") }}';
                this.formMethod = 'POST';
                this.form = { id: null, name: '', email: '', role_id: '', password: '', password_confirmation: '', is_active: true };
                this.errors = [];
                this.isModalOpen = true;
            },
            async editUser(id) {
                try {
                    const res = await fetch(`/app/users/${id}/edit`);
                    const data = await res.json();
                    this.formAction = `/app/users/${id}`;
                    this.formMethod = 'PUT';
                    this.form = {
                        id: data.id,
                        name: data.name || '',
                        email: data.email || '',
                        role_id: data.role_id ? String(data.role_id) : '',
                        password: '',
                        password_confirmation: '',
                        is_active: !!data.is_active,
                    };
                    this.errors = [];
                    this.isModalOpen = true;
                } catch (e) {
                    alert('Error loading user details.');
                }
            },
        }
    }
</script>
@endpush
@endsection
