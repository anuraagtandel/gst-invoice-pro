<div class="py-12 flex flex-col items-center justify-center text-center">
    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-400 mb-4">
        <i class="ph {{ $icon ?? 'ph-folder-open' }} text-3xl"></i>
    </div>
    <h3 class="text-sm font-semibold text-slate-800 mb-1">{{ $title ?? 'No records found' }}</h3>
    <p class="text-sm text-slate-500 max-w-sm">{{ $message ?? 'There are no records to display here at the moment.' }}</p>
    {{ $slot ?? '' }}
</div>