@props(['disabled' => false])

@php
    $isPassword = ($attributes->get('type') === 'password');
@endphp

@if($isPassword)
    <div data-password-field class="relative">
        <input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm pr-10']) }}>
        <button type="button" data-password-toggle aria-label="Show password" class="absolute inset-y-0 right-0 px-3 text-gray-400 hover:text-gray-600">
            <i class="ph ph-eye"></i>
        </button>
    </div>
@else
    <input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm']) }}>
@endif
