@extends('layouts.app')

@section('title', 'Masters')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

    <!-- HSN Codes -->
    <div class="order-1 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col h-full">
        <div class="bg-slate-50 px-4 py-3 border-b border-slate-200 flex justify-between items-center">
            <h3 class="font-syne font-semibold text-slate-800">HSN Codes</h3>
        </div>
        <div class="p-4 flex-1 overflow-y-auto max-h-[320px]">
            <ul class="divide-y divide-slate-100 mb-4">
                @foreach($hsnCodes as $hsn)
                <li class="py-2 flex justify-between items-center">
                    <div>
                        <span class="font-medium text-slate-800">{{ $hsn->code }}</span>
                        <span class="text-xs text-slate-500 block">{{ $hsn->description }}</span>
                    </div>
                    <form action="{{ route('app.masters.hsn.destroy', $hsn->id) }}" method="POST" onsubmit="return confirm('Delete this HSN Code?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-slate-400 hover:text-red-500 transition-colors"><i class="ph ph-trash"></i></button>
                    </form>
                </li>
                @endforeach
            </ul>
        </div>
        <div class="p-4 border-t border-slate-100 bg-slate-50 mt-auto">
            <form x-data="{ code: '' }" action="{{ route('app.masters.hsn.store') }}" method="POST" class="flex flex-col gap-2">
                @csrf
                <div class="flex gap-2">
                    <div class="w-1/3">
                        <input type="text" name="code" required placeholder="HSN Code" x-model="code" inputmode="numeric" maxlength="8" pattern="[0-9]{8}" @input="code = (code || '').replace(/\\D/g, '').slice(0, 8)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <div x-show="code.length > 0 && code.length !== 8" class="mt-1 text-xs font-semibold text-red-600 bg-red-50 border border-red-100 px-2 py-1 rounded-md inline-flex items-center gap-1">
                            <i class="ph ph-warning-circle"></i>
                            <span>HSN invalid</span>
                        </div>
                    </div>
                    <input type="text" name="description" placeholder="Description" class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none" style="text-transform: uppercase;">
                </div>
                <button type="submit" :disabled="code.length > 0 && code.length !== 8" class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-300 disabled:text-slate-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">Add HSN Code</button>
            </form>
        </div>
    </div>

    <!-- Pack Types -->
    <div class="order-4 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col h-full">
        <div class="bg-slate-50 px-4 py-3 border-b border-slate-200 flex justify-between items-center">
            <h3 class="font-syne font-semibold text-slate-800">Pack Types</h3>
        </div>
        <div class="p-4 flex-1 overflow-y-auto max-h-[320px]">
            <ul class="divide-y divide-slate-100 mb-4">
                @foreach($packTypes as $pack)
                <li class="py-2 flex justify-between items-center">
                    <span class="font-medium text-slate-800">{{ $pack->code }}</span>
                    <form action="{{ route('app.masters.pack.destroy', $pack->id) }}" method="POST" onsubmit="return confirm('Delete this Pack Type?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-slate-400 hover:text-red-500 transition-colors"><i class="ph ph-trash"></i></button>
                    </form>
                </li>
                @endforeach
            </ul>
        </div>
        <div class="p-4 border-t border-slate-100 bg-slate-50 mt-auto">
            <form action="{{ route('app.masters.pack.store') }}" method="POST" class="flex gap-2">
                @csrf
                <input type="text" name="code" required placeholder="e.g. BOX, JAR" class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none" style="text-transform: uppercase;">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">Add</button>
            </form>
        </div>
    </div>

    <!-- Categories -->
    <div class="order-2 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col h-full">
        <div class="bg-slate-50 px-4 py-3 border-b border-slate-200 flex justify-between items-center">
            <h3 class="font-syne font-semibold text-slate-800">Categories</h3>
        </div>
        <div class="p-4 flex-1 overflow-y-auto max-h-[320px]">
            <ul class="divide-y divide-slate-100 mb-4">
                @foreach($categories as $category)
                <li class="py-2 flex justify-between items-center">
                    <div>
                        <span class="font-medium text-slate-800">{{ $category->name }}</span>
                    </div>
                    <form action="{{ route('app.masters.category.destroy', $category->id) }}" method="POST" onsubmit="return confirm('Delete this Category?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-slate-400 hover:text-red-500 transition-colors"><i class="ph ph-trash"></i></button>
                    </form>
                </li>
                @endforeach
            </ul>
        </div>
        <div class="p-4 border-t border-slate-100 bg-slate-50 mt-auto">
            <form action="{{ route('app.masters.category.store') }}" method="POST" class="flex gap-2">
                @csrf
                <input type="text" name="name" required placeholder="Category name" class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none" style="text-transform: uppercase;">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">Add</button>
            </form>
        </div>
    </div>

    <!-- Brands -->
    <div class="order-3 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col h-full">
        <div class="bg-slate-50 px-4 py-3 border-b border-slate-200 flex justify-between items-center">
            <h3 class="font-syne font-semibold text-slate-800">Brands</h3>
        </div>
        <div class="p-4 flex-1 overflow-y-auto max-h-[320px]">
            <ul class="divide-y divide-slate-100 mb-4">
                @foreach($brands as $brand)
                <li class="py-2 flex justify-between items-center">
                    <div>
                        <span class="font-medium text-slate-800">{{ $brand->name }}</span>
                    </div>
                    <form action="{{ route('app.masters.brand.destroy', $brand->id) }}" method="POST" onsubmit="return confirm('Delete this Brand?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-slate-400 hover:text-red-500 transition-colors"><i class="ph ph-trash"></i></button>
                    </form>
                </li>
                @endforeach
            </ul>
        </div>
        <div class="p-4 border-t border-slate-100 bg-slate-50 mt-auto">
            <form action="{{ route('app.masters.brand.store') }}" method="POST" class="flex gap-2">
                @csrf
                <input type="text" name="name" required placeholder="Brand name" class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none" style="text-transform: uppercase;">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">Add</button>
            </form>
        </div>
    </div>

    <!-- Volumes -->
    <div class="order-5 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col h-full">
        <div class="bg-slate-50 px-4 py-3 border-b border-slate-200 flex justify-between items-center">
            <h3 class="font-syne font-semibold text-slate-800">Volumes</h3>
        </div>
        <div class="p-4 flex-1 overflow-y-auto max-h-[320px]">
            <ul class="divide-y divide-slate-100 mb-4">
                @foreach($volumes as $volume)
                <li class="py-2 flex justify-between items-center">
                    <div>
                        <span class="font-medium text-slate-800">{{ $volume->name }}</span>
                    </div>
                    <form action="{{ route('app.masters.volume.destroy', $volume->id) }}" method="POST" onsubmit="return confirm('Delete this Volume?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-slate-400 hover:text-red-500 transition-colors"><i class="ph ph-trash"></i></button>
                    </form>
                </li>
                @endforeach
            </ul>
        </div>
        <div class="p-4 border-t border-slate-100 bg-slate-50 mt-auto">
            <form action="{{ route('app.masters.volume.store') }}" method="POST" class="flex gap-2">
                @csrf
                <input type="text" name="name" required placeholder="e.g. 500ML" class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none" style="text-transform: uppercase;">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">Add</button>
            </form>
        </div>
    </div>

    <!-- Areas -->
    <div class="order-6 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col h-full">
        <div class="bg-slate-50 px-4 py-3 border-b border-slate-200 flex justify-between items-center">
            <h3 class="font-syne font-semibold text-slate-800">Areas</h3>
        </div>
        <div class="p-4 flex-1 overflow-y-auto max-h-[320px]">
            <ul class="divide-y divide-slate-100 mb-4">
                @foreach($areas as $area)
                <li class="py-2 flex justify-between items-center">
                    <div>
                        <span class="font-medium text-slate-800">{{ $area->name }}</span>
                    </div>
                    <form action="{{ route('app.masters.area.destroy', $area->id) }}" method="POST" onsubmit="return confirm('Delete this Area?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-slate-400 hover:text-red-500 transition-colors"><i class="ph ph-trash"></i></button>
                    </form>
                </li>
                @endforeach
            </ul>
        </div>
        <div class="p-4 border-t border-slate-100 bg-slate-50 mt-auto">
            <form action="{{ route('app.masters.area.store') }}" method="POST" class="flex gap-2">
                @csrf
                <input type="text" name="name" required placeholder="Area name" class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none" style="text-transform: uppercase;">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">Add</button>
            </form>
        </div>
    </div>

    <!-- Salesmen -->
    <div class="order-7 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col h-full" x-data="salesmanMaster()">
        <div class="bg-slate-50 px-4 py-3 border-b border-slate-200 flex justify-between items-center">
            <h3 class="font-syne font-semibold text-slate-800">Salesmen</h3>
            <button type="button" @click="openCreate()" class="text-indigo-600 hover:text-indigo-700 text-sm font-semibold">Add</button>
        </div>
        <div class="p-4 flex-1 overflow-y-auto max-h-[320px]">
            <ul class="divide-y divide-slate-100 mb-4">
                @foreach($salesmen as $s)
                <li class="py-2 flex justify-between items-center gap-3">
                    <div class="min-w-0">
                        <div class="font-medium text-slate-800 truncate">{{ $s->name }}</div>
                        <div class="text-[11px] text-slate-500 mt-0.5 truncate">
                            {{ $s->mobile ?? '-' }}
                            <span class="text-slate-300">•</span>
                            {{ $s->email ?? '-' }}
                            <span class="text-slate-300">•</span>
                            {{ $s->area?->name ?? '-' }}
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <form action="{{ route('app.masters.salesmen.destroy', $s->id) }}" method="POST" onsubmit="return confirm('Delete this Salesman?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-slate-400 hover:text-red-500 transition-colors"><i class="ph ph-trash"></i></button>
                        </form>
                    </div>
                </li>
                @endforeach
            </ul>
        </div>

        <div x-show="isOpen" class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/50" style="display: none;">
            <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl overflow-hidden" @click.away="close()">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="font-syne font-semibold text-slate-800">Add Salesman</h3>
                    <button type="button" @click="close()" class="text-slate-400 hover:text-slate-600"><i class="ph ph-x text-xl"></i></button>
                </div>
                <form method="POST" action="{{ route('app.masters.salesmen.store') }}" class="p-6 space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1 md:col-span-2">
                            <label class="text-xs font-medium text-slate-700">Salesman Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" x-model="form.name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">Mobile Number</label>
                            <input type="text" name="mobile" x-model="form.mobile" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-medium text-slate-700">Email</label>
                            <input type="email" name="email" x-model="form.email" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="space-y-1 md:col-span-2">
                            <label class="text-xs font-medium text-slate-700">Assigned Area</label>
                            <select name="area_id" x-model="form.area_id" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                                <option value="">None</option>
                                @foreach($areas as $a)
                                    <option value="{{ $a->id }}">{{ $a->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="close()" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Unit Types -->
    <div class="order-8 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col h-full">
        <div class="bg-slate-50 px-4 py-3 border-b border-slate-200 flex justify-between items-center">
            <h3 class="font-syne font-semibold text-slate-800">Unit Types</h3>
        </div>
        <div class="p-4 flex-1 overflow-y-auto max-h-[320px]">
            <ul class="divide-y divide-slate-100 mb-4">
                @foreach($unitTypes as $unit)
                <li class="py-2 flex justify-between items-center">
                    <span class="font-medium text-slate-800">{{ $unit->code }}</span>
                    <form action="{{ route('app.masters.unit.destroy', $unit->id) }}" method="POST" onsubmit="return confirm('Delete this Unit Type?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-slate-400 hover:text-red-500 transition-colors"><i class="ph ph-trash"></i></button>
                    </form>
                </li>
                @endforeach
            </ul>
        </div>
        <div class="p-4 border-t border-slate-100 bg-slate-50 mt-auto">
            <form action="{{ route('app.masters.unit.store') }}" method="POST" class="flex gap-2">
                @csrf
                <input type="text" name="code" required placeholder="e.g. KG, LTR" class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none" style="text-transform: uppercase;">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">Add</button>
            </form>
        </div>
    </div>

    <!-- Pack Sizes -->
    <div class="order-9 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col h-full">
        <div class="bg-slate-50 px-4 py-3 border-b border-slate-200 flex justify-between items-center">
            <h3 class="font-syne font-semibold text-slate-800">Pack Sizes (Units/CT)</h3>
        </div>
        <div class="p-4 flex-1 overflow-y-auto max-h-[320px]">
            <ul class="divide-y divide-slate-100 mb-4">
                @foreach($packSizes as $packSize)
                <li class="py-2 flex justify-between items-center">
                    <div>
                        <span class="font-medium text-slate-800">{{ $packSize->units_per_ct }}</span>
                    </div>
                    <form action="{{ route('app.masters.pack_size.destroy', $packSize->id) }}" method="POST" onsubmit="return confirm('Delete this Pack Size?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-slate-400 hover:text-red-500 transition-colors"><i class="ph ph-trash"></i></button>
                    </form>
                </li>
                @endforeach
            </ul>
        </div>
        <div class="p-4 border-t border-slate-100 bg-slate-50 mt-auto">
            <form action="{{ route('app.masters.pack_size.store') }}" method="POST" class="flex gap-2">
                @csrf
                <input type="number" name="units_per_ct" min="1" required placeholder="e.g. 24" class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">Add</button>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function salesmanMaster() {
        return {
            isOpen: false,
            form: { name: '', mobile: '', email: '', area_id: '' },
            openCreate() {
                this.form = { name: '', mobile: '', email: '', area_id: '' };
                this.isOpen = true;
            },
            close() {
                this.isOpen = false;
            }
        }
    }
</script>
@endpush
