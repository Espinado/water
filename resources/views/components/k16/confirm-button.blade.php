@props([
    'wireMethod',
    'wireParam' => null,
    'title',
    'text' => null,
    'confirmText' => null,
    'cancelText' => null,
    'tone' => 'default',
])

@php
    $confirmClass = match ($tone) {
        'danger' => 'k16-btn-danger',
        'success' => 'bg-k16-success text-white hover:opacity-90 k16-btn',
        default => 'k16-btn-primary',
    };
@endphp

<div x-data="{ open: false }" class="contents">
    <button type="button" {{ $attributes }} @click="open = true; $dispatch('k16-menu-close')">
        {{ $slot }}
    </button>

    <div
        x-show="open"
        x-cloak
        class="fixed inset-0 z-[70] flex items-end justify-center bg-black/40 p-4 sm:items-center"
        style="display: none;"
        @keydown.escape.window="open = false"
    >
        <div
            x-show="open"
            x-transition
            class="w-full max-w-md rounded-2xl bg-k16-surface p-6"
            @click.outside="open = false"
            role="dialog"
            aria-modal="true"
            :aria-label="@js($title)"
        >
            <h2 class="text-k16-lead font-bold text-k16-text">{{ $title }}</h2>
            @if ($text)
                <p class="mt-3 text-k16-body text-k16-text-muted">{{ $text }}</p>
            @endif

            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                <button type="button" class="k16-btn-secondary w-full" @click="open = false">
                    {{ $cancelText ?? __('Отмена') }}
                </button>
                <button
                    type="button"
                    class="{{ $confirmClass }} w-full"
                    @click="open = false; $wire.call(@js($wireMethod), @js($wireParam))"
                >
                    {{ $confirmText ?? __('Подтвердить') }}
                </button>
            </div>
        </div>
    </div>
</div>
