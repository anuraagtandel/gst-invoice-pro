@extends('layouts.app')

@section('title', 'Categories')

@section('content')
<div x-data="categoryManager()" class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-syne font-semibold text-lg text-slate-800">Product Categories</h3>
            <p class="text-sm text-slate-500">Manage your product organization levels.</p>
        </div>
        <button @click="openModal()" class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-2 shadow-sm">
            <i class="ph ph-plus text-lg"></i> Add Category
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($categories->isEmpty())
            @include('partials._empty_state', ['icon' => 'ph-tag', 'title' => 'No categories found', 'message' => 'Add your first category to organize your products.'])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold w-20">#</th>
                            <th class="px-6 py-3 font-semibold">Category Name</th>
                            <th class="px-6 py-3 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($categories as $category)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 text-slate-500">{{ $loop->iteration }}</td>
                            <td class="px-6 py-4 font-medium text-slate-800 w-full">{{ $category->name }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="editCategory({{ $category->toJson() }})" class="w-8 h-8 rounded text-indigo-600 hover:bg-indigo-50 flex items-center justify-center transition-colors">
                                        <i class="ph ph-pencil-simple text-lg"></i>
                                    </button>
                                    <form action="{{ route('app.categories.destroy', $category->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this category?');">
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
                class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md"
            >
                <!-- Header -->
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="font-syne font-semibold text-lg text-slate-800" id="modal-title" x-text="form.id ? 'Edit Category' : 'Add Category'">
                    </h3>
                    <button @click="isModalOpen = false" type="button" class="text-slate-400 hover:text-slate-500 transition-colors">
                        <i class="ph ph-x text-xl"></i>
                    </button>
                </div>
                
                <!-- Body -->
                <div class="px-6 py-5">
                    <form :action="formAction" method="POST" id="categoryForm">
                        @csrf
                        <template x-if="formMethod === 'PUT'">
                            <input type="hidden" name="_method" value="PUT">
                        </template>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Category Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" x-model="form.name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
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
    function categoryManager() {
        return {
            isModalOpen: false,
            formAction: '{{ route("app.categories.store") }}',
            formMethod: 'POST',
            form: {
                id: null,
                name: '',
                is_active: true
            },
            
            openModal() {
                this.formAction = '{{ route("app.categories.store") }}';
                this.formMethod = 'POST';
                this.form = { id: null, name: '', is_active: true };
                this.isModalOpen = true;
            },
            
            editCategory(category) {
                this.formAction = `/app/categories/${category.id}`;
                this.formMethod = 'PUT';
                this.form = { ...category, is_active: category.is_active == 1 };
                this.isModalOpen = true;
            }
        }
    }
</script>
@endpush
@endsection