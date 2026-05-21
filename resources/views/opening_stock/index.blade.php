@extends('layouts.app')

@section('title', 'Opening Stock')

@section('content')
<div class="space-y-6" x-data="inventoryManager()">

    <div class="flex justify-between items-center bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h3 class="font-syne font-semibold text-lg text-slate-800">Opening Stock Management</h3>
            <p class="text-sm text-slate-500">Set initial stock balances before tracking purchases and sales.</p>
        </div>

        <button type="button" @click="openModal($event)" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2 shadow-sm">
            <i class="ph ph-plus text-lg"></i> Add Inventory
        </button>
    </div>

    <!-- Table Form -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($products->isEmpty())
            @include('partials._empty_state', ['icon' => 'ph-package', 'title' => 'No products available', 'message' => 'Please add products first before managing stock.'])
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 font-semibold">#</th>
                            <th class="px-6 py-3 font-semibold">Product Name</th>
                            <th class="px-6 py-3 font-semibold text-center">Pack Size</th>
                            <th class="px-6 py-3 font-semibold text-center">Opening Stock</th>
                            <th class="px-6 py-3 font-semibold text-center">Current Stock</th>
                            <th class="px-6 py-3 font-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($products as $index => $product)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 text-slate-500">{{ $loop->iteration }}</td>
                            <td class="px-6 py-4 font-medium text-slate-800">
                                {{ $product->name }} {{ $product->volume }}
                            </td>
                            <td class="px-6 py-4 text-center text-slate-600">{{ $product->pack_size }}</td>
                            <td class="px-6 py-4 text-center font-medium text-slate-800">
                                @if($product->openingStock)
                                    {{ floor((($product->openingStock->opening_ct * $product->pack_size) + $product->openingStock->opening_un) / $product->pack_size) }} CT & {{ (($product->openingStock->opening_ct * $product->pack_size) + $product->openingStock->opening_un) % $product->pack_size }} UN
                                @else
                                    0 CT & 0 UN
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-indigo-600">
                                {{ $product->formatted_stock }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button 
                                    @click="openModal($event)" 
                                    data-product-id="{{ $product->id }}" 
                                    data-product-name="{{ $product->name }} {{ $product->volume }}"
                                    data-pack-size="{{ $product->pack_size }}"
                                    class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded-md text-xs font-medium hover:bg-indigo-200 transition-colors">Add Inventory</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Modal -->
    <div x-show="isModalOpen" class="fixed inset-0 z-[60] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
            <!-- Backdrop -->
            <div x-show="isModalOpen" 
                x-transition:enter="ease-out duration-300" 
                x-transition:enter-start="opacity-0" 
                x-transition:enter-end="opacity-100" 
                x-transition:leave="ease-in duration-200" 
                x-transition:leave-start="opacity-100" 
                x-transition:leave-end="opacity-0" 
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" 
                @click="isModalOpen = false"></div>

            <!-- Modal panel -->
            <div x-show="isModalOpen" 
                x-transition:enter="ease-out duration-300" 
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                x-transition:leave="ease-in duration-200" 
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                class="relative inline-block align-middle bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full border border-slate-100">
                
                <div class="absolute top-4 right-4">
                    <button @click="isModalOpen = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="ph ph-x text-xl"></i>
                    </button>
                </div>

                <form action="{{ route('app.opening-stock.add') }}" method="POST" class="p-6">
                    @csrf
                    <div class="mb-6">
                        <h3 class="text-xl font-syne font-bold text-slate-800" id="modal-title" x-text="productId ? 'Add Inventory for ' + productName : 'Add Inventory'"></h3>
                        <p class="text-sm text-slate-500 mt-1">Fill in the details below to update opening stock.</p>
                    </div>

                    <div class="space-y-5">
                        <div x-show="!productId">
                            <label for="product_id_modal" class="block text-sm font-semibold text-slate-700 mb-1.5">Product Name</label>
                            <select name="product_id" id="product_id_modal" x-model="selectedProductId" @change="updateProductDetails()" 
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all sm:text-sm">
                                <option value="">Select a product</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" data-pack-size="{{ $product->pack_size }}">{{ $product->name }} {{ $product->volume }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <input type="hidden" name="product_id" :value="productId || selectedProductId">
                        
                        <div>
                            <label for="cartons" class="block text-sm font-semibold text-slate-700 mb-1.5">Opening Cartons</label>
                            <div class="relative">
                                <input type="number" name="opening_ct" x-model.number="cartons" @input="calculateBottles" 
                                    placeholder="Enter number of cartons"
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all sm:text-sm pr-12">
                                <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400 text-xs font-medium uppercase">
                                    CT
                                </div>
                            </div>
                        </div>

                        <div class="bg-indigo-50 rounded-xl p-4 flex items-center justify-between border border-indigo-100">
                            <div>
                                <p class="text-xs font-semibold text-indigo-600 uppercase tracking-wider">Calculated Total</p>
                                <p class="text-2xl font-bold text-indigo-900 mt-0.5" x-text="bottles"></p>
                            </div>
                            <div class="bg-indigo-100 p-2 rounded-lg">
                                <i class="ph ph-package text-indigo-600 text-2xl"></i>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-bold text-indigo-400 uppercase">Bottles</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex gap-3">
                        <button type="button" @click="isModalOpen = false" 
                            class="flex-1 px-4 py-2.5 border border-slate-200 text-slate-600 rounded-xl text-sm font-semibold hover:bg-slate-50 transition-all">
                            Cancel
                        </button>
                        <button type="submit" 
                            class="flex-1 px-4 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 shadow-md shadow-indigo-200 transition-all">
                            Set Stock Balance
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function inventoryManager() {
        return {
            isModalOpen: false,
            formAction: '{{ route("app.opening-stock.add") }}',
            productId: null,
            productName: '',
            packSize: 1,
            cartons: 0,
            bottles: 0,
            selectedProductId: null,
            openModal(event) {
                //alert();
                if (event.currentTarget.dataset.productId) {
                    this.productId = event.currentTarget.dataset.productId;
                    this.productName = event.currentTarget.dataset.productName;
                    this.packSize = event.currentTarget.dataset.packSize;
                } else {
                    this.productId = null;
                    this.productName = '';
                    this.packSize = 1;
                }
                this.cartons = 0;
                this.bottles = 0;
                this.selectedProductId = null;
                this.isModalOpen = true;
            },
            calculateBottles() {
                this.bottles = this.cartons * this.packSize;
            },
            updateProductDetails() {
                if (this.selectedProductId) {
                    const select = document.getElementById('product_id_modal');
                    if (select.selectedIndex > 0) {
                        const selectedOption = select.options[select.selectedIndex];
                        this.packSize = selectedOption.dataset.packSize || 1;
                    } else {
                        this.packSize = 1;
                    }
                } else {
                    this.packSize = 1;
                }
                this.calculateBottles();
            }
        }
    }
</script>
@endpush

@endsection