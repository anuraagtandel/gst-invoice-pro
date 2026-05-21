<div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
    <div class="flex items-start justify-between">
        <div>
            <p class="text-sm font-medium text-slate-500 mb-1">{{ $label }}</p>
            <h3 class="font-syne text-2xl font-bold text-slate-800">{{ $value }}</h3>
        </div>
        <div class="w-10 h-10 rounded-lg {{ $colorClass ?? 'bg-indigo-50 text-indigo-600' }} flex items-center justify-center">
            <i class="ph {{ $icon }} text-xl"></i>
        </div>
    </div>
</div>