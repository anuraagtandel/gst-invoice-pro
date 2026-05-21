<div 
    x-show="{{ $model }}" 
    class="fixed inset-0 z-[100] overflow-y-auto" 
    aria-labelledby="modal-title" 
    role="dialog" 
    aria-modal="true"
    x-cloak
>
    <!-- Backdrop -->
    <div 
        x-show="{{ $model }}"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" 
        @click="{{ $model }} = false"
    ></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div 
            x-show="{{ $model }}"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full {{ $maxWidth ?? 'sm:max-w-2xl' }}"
        >
            <!-- Header -->
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-syne font-semibold text-lg text-slate-800" id="modal-title">
                    {{ $title }}
                </h3>
                <button @click="{{ $model }} = false" type="button" class="text-slate-400 hover:text-slate-500 transition-colors">
                    <i class="ph ph-x text-xl"></i>
                </button>
            </div>
            
            <!-- Body -->
            <div class="px-6 py-5">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>