@props([
    'target' => null,
    'color' => 'indigo',
])

@php
    $spinnerColor = match ($color) {
        'emerald' => 'text-emerald-600',
        'sky', 'k16' => 'text-k16-accent',
        default => 'text-indigo-600',
    };
@endphp

<div
    @if ($target)
        wire:loading.flex
        wire:target="{{ $target }}"
    @endif
    {{ $attributes->class([
        'fixed inset-0 z-[100] flex-col items-center justify-center gap-5 bg-white/90 px-6 backdrop-blur-sm',
    ]) }}
>
    <svg class="h-28 w-28 animate-spin sm:h-24 sm:w-24 {{ $spinnerColor }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
        <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
        <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    <p class="text-center text-k16-title font-bold text-k16-text">{{ __('Пожалуйста, подождите') }}</p>
</div>
