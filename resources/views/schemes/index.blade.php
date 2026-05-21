@extends('layouts.app')

@section('title', 'Schemes')

@section('content')
<div x-data="schemeManager()" class="space-y-6">

    <div class="flex justify-between items-center bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-syne font-semibold text-lg text-slate-800">Promotional Schemes</h3>
            <p class="text-sm text-slate-500">Manage free goods slabs for products.</p>
        </div>
        <button @click="openModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2 shadow-sm">
            <i class="ph ph-plus text-lg"></i> Add Scheme
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($schemes->isEmpty())
            @include('partials._empty_state', ['icon' => 'ph-ticket', 'title' => 'No active schemes', 'message' => 'Create a scheme to automate free goods calculation during billing.'])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Scheme Name</th>
                            <th class="px-6 py-3 font-semibold">Product</th>
                            <th class="px-6 py-3 font-semibold">Slabs (Buy → Free)</th>
                            <th class="px-6 py-3 font-semibold">Validity</th>
                            <th class="px-6 py-3 font-semibold text-center">Status</th>
                            <th class="px-6 py-3 font-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($schemes as $scheme)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 font-medium text-slate-800">{{ $scheme->name }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $scheme->product->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    @foreach($scheme->schemeSlabs as $slab)
                                            <span class="inline-flex items-center gap-2 text-xs bg-slate-100 text-slate-700 px-2 py-1 rounded">
                                                Buy <b>{{ $slab->min_qty }}{{ $slab->max_qty ? ' - ' . $slab->max_qty : '+' }}</b> <i class="ph ph-arrow-right text-slate-400"></i> Get <b>{{ $slab->free_qty }}</b> {{ $slab->freeProduct ? $slab->freeProduct->name : 'Free' }}
                                            </span>
                                        @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-600 text-xs">
                                @if($scheme->valid_from || $scheme->valid_to)
                                    <div>From: <span class="font-medium">{{ $scheme->valid_from ? $scheme->valid_from->format('d/m/y') : 'Any' }}</span></div>
                                    <div>To: <span class="font-medium">{{ $scheme->valid_to ? $scheme->valid_to->format('d/m/y') : 'Any' }}</span></div>
                                @else
                                    <span class="text-slate-400 italic">No expiry</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <form action="{{ route('app.schemes.toggle', $scheme->id) }}" method="POST" class="inline">
                                    @csrf @method('PATCH')
                                    <label class="nt-switch">
                                        <input type="checkbox" onChange="this.form.submit()" {{ $scheme->is_active ? 'checked' : '' }}>
                                        <span class="nt-slider"></span>
                                    </label>
                                </form>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button @click="editScheme({{ $scheme->id }})" class="w-8 h-8 rounded text-indigo-600 hover:bg-indigo-50 flex items-center justify-center transition-colors">
                                        <i class="ph ph-pencil-simple text-lg"></i>
                                    </button>
                                    <form action="{{ route('app.schemes.destroy', $scheme->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this scheme?');">
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
                {{ $schemes->links() }}
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
                class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-4xl"
            >
                <!-- Header -->
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="font-syne font-semibold text-lg text-slate-800" id="modal-title">
                        Scheme Details
                    </h3>
                    <button @click="isModalOpen = false" type="button" class="text-slate-400 hover:text-slate-500 transition-colors">
                        <i class="ph ph-x text-xl"></i>
                    </button>
                </div>
                
                <!-- Body -->
                <div class="px-6 py-5">
                    <form :action="formAction" method="POST">
                        @csrf
                        <input type="hidden" name="_method" x-model="formMethod">
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-slate-700">Scheme Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" x-model="form.name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-slate-700">Applicable Product <span class="text-red-500">*</span></label>
                                <select name="product_id" x-model="form.product_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                    <option value="">Select Product...</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }} {{ $p->volume }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-slate-700">Valid From <span class="text-red-500">*</span></label>
                                <input x-ref="validFrom" type="date" name="valid_from" x-model="form.valid_from" required :min="today" @click="$refs.validFrom?.showPicker?.()" @change="if(form.valid_to && form.valid_to < form.valid_from) form.valid_to = form.valid_from" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none cursor-pointer">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-slate-700">Valid To <span class="text-red-500">*</span></label>
                                <input x-ref="validTo" type="date" name="valid_to" x-model="form.valid_to" required :min="form.valid_from || today" @click="$refs.validTo?.showPicker?.()" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none cursor-pointer">
                            </div>
                        </div>

                        <!-- Dynamic Slabs -->
                        <div class="border border-slate-200 rounded-lg overflow-hidden mb-6">
                            <div class="bg-slate-50 px-4 py-2 border-b border-slate-200 flex justify-between items-center">
                                <h4 class="text-sm font-semibold text-slate-800">Scheme Slabs</h4>
                                <button type="button" @click="addSlab()" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                                    <i class="ph ph-plus"></i> Add Slab Row
                                </button>
                            </div>
                            <div class="p-4 space-y-3">
                                <template x-for="(slab, index) in form.slabs" :key="index">
                                    <div class="flex items-center gap-3 bg-white p-3 rounded-lg border border-slate-100 shadow-sm">
                                        <div class="w-24 flex-none">
                                            <label class="text-[10px] text-slate-500 uppercase font-bold tracking-wider block mb-1">Buy Min</label>
                                            <input type="number" x-model="slab.min_qty" :name="`slabs[${index}][min_qty]`" required min="1" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                        </div>
                                        <div class="text-slate-400 mt-5 flex-none"><i class="ph ph-minus"></i></div>
                                        <div class="w-24 flex-none">
                                            <label class="text-[10px] text-slate-500 uppercase font-bold tracking-wider block mb-1">Buy Max</label>
                                            <input type="number" x-model="slab.max_qty" :name="`slabs[${index}][max_qty]`" min="1" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                        </div>
                                        <div class="text-slate-400 mt-5 flex-none"><i class="ph ph-arrow-right"></i></div>
                                        <div class="w-32 flex-none">
                                            <label class="text-[10px] text-slate-500 uppercase font-bold tracking-wider block mb-1 text-center">Free Qty</label>
                                            <input type="number" x-model="slab.free_qty" :name="`slabs[${index}][free_qty]`" required min="1" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none text-center">
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <label class="text-[10px] text-slate-500 uppercase font-bold tracking-wider block mb-1">Free Product Selection</label>
                                            <select x-model="slab.free_product_id" :name="`slabs[${index}][free_product_id]`" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white truncate">
                                                <option value="">Select Free Product...</option>
                                                @foreach($products as $p)
                                                    <option value="{{ $p->id }}">{{ $p->name }} {{ $p->volume }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mt-5 flex-none">
                                            <button type="button" @click="removeSlab(index)" class="w-9 h-9 rounded bg-red-50 text-red-600 hover:bg-red-100 flex items-center justify-center transition-colors">
                                                <i class="ph ph-trash text-lg"></i>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="form.slabs.length === 0" class="text-center text-sm text-slate-500 py-2 italic">
                                    No slabs added. Click "Add Slab Row" to begin.
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="isModalOpen = false" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">Cancel</button>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-sm">Save Scheme</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function schemeManager() {
        return {
            isModalOpen: false,
            today: new Date().toISOString().slice(0, 10),
            formAction: '{{ route("app.schemes.store") }}',
            formMethod: 'POST',
            form: {
                id: null, name: '', product_id: '', valid_from: '', valid_to: '',
                slabs: [{ min_qty: '', max_qty: '', free_qty: '', free_product_id: '' }]
            },
            
            openModal() {
                this.formAction = '{{ route("app.schemes.store") }}';
                this.formMethod = 'POST';
                this.form = { id: null, name: '', product_id: '', valid_from: this.today, valid_to: this.today, slabs: [] };
                this.addSlab(); // Start with one empty slab
                this.isModalOpen = true;
            },
            
            async editScheme(id) {
                try {
                    const res = await fetch(`/app/schemes/${id}`);
                    const data = await res.json();
                    this.formAction = `/app/schemes/${id}`;
                    this.formMethod = 'PUT';
                    
                    // Format dates for input[type=date]
                    let vFrom = data.valid_from ? data.valid_from.split('T')[0] : '';
                    let vTo = data.valid_to ? data.valid_to.split('T')[0] : '';
                    
                    this.form = {
                        id: data.id,
                        name: data.name,
                        product_id: data.product_id,
                        valid_from: vFrom,
                        valid_to: vTo,
                        slabs: data.scheme_slabs.map(slab => ({
                            min_qty: slab.min_qty,
                            max_qty: slab.max_qty,
                            free_qty: slab.free_qty,
                            free_product_id: slab.free_product_id || ''
                        }))
                    };
                    this.isModalOpen = true;
                } catch (e) {
                    alert('Error loading scheme details.');
                }
            },
            
            addSlab() {
                this.form.slabs.push({ min_qty: '', max_qty: '', free_qty: '', free_product_id: '' });
            },
            
            removeSlab(index) {
                this.form.slabs.splice(index, 1);
            }
        }
    }
</script>
@endpush
@endsection
