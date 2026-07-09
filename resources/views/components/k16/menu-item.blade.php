@props([
    'href' => null,
    'action' => null,
    'actionParam' => null,
    'danger' => false,
])

@php
    $classes = $danger
        ? 'block w-full px-4 py-3 text-start text-base font-semibold text-k16-danger hover:bg-k16-danger-soft'
        : 'block w-full px-4 py-3 text-start text-base font-semibold text-k16-text hover:bg-k16-bg';
@endphp

@if ($href)
    <a
        href="{{ $href }}"
        wire:navigate
        role="menuitem"
        @click="$dispatch('k16-menu-close')"
        {{ $attributes->class([$classes]) }}
    >
        {{ $slot }}
    </a>
@elseif ($action)
    <button
        type="button"
        role="menuitem"
        wire:click="{{ $action }}{{ $actionParam !== null ? '('.$actionParam.')' : '' }}"
        @click="$dispatch('k16-menu-close')"
        {{ $attributes->class([$classes]) }}
    >
        {{ $slot }}
    </button>
@else
    <button type="button" role="menuitem" {{ $attributes->class([$classes]) }}>
        {{ $slot }}
    </button>
@endif
