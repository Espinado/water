@props([
    'label' => null,
])

<div
    x-data="{ open: false }"
    class="relative"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    @k16-menu-close.window="open = false"
>
    <button
        type="button"
        @click="open = ! open"
        {{ $attributes->class(['k16-btn-secondary']) }}
        aria-haspopup="menu"
        :aria-expanded="open"
    >
        {{ $label ?? __('Ещё') }}
        <svg class="ms-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition
        role="menu"
        class="absolute end-0 z-50 mt-2 min-w-[14rem] overflow-hidden rounded-2xl border border-k16-border bg-k16-surface py-1 shadow-lg"
        style="display: none;"
    >
        {{ $slot }}
    </div>
</div>
