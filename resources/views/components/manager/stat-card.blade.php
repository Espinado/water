@props([
    'href' => null,
    'label',
    'value',
    'hint' => null,
    'tone' => 'indigo',
])

@php
    $tones = [
        'rose' => 'border-k16-danger/20 bg-k16-danger-soft text-k16-danger',
        'emerald' => 'border-k16-success/20 bg-k16-success-soft text-k16-success',
        'amber' => 'border-k16-warning/20 bg-k16-warning-soft text-k16-warning',
        'sky' => 'border-k16-accent/20 bg-k16-bg text-k16-accent',
        'violet' => 'border-k16-border bg-k16-bg text-k16-text',
        'indigo' => 'border-k16-border bg-k16-surface text-k16-text',
    ];
    $classes = $tones[$tone] ?? $tones['indigo'];
@endphp

@if ($href)
    <a
        href="{{ $href }}"
        wire:navigate
        {{ $attributes->merge(['class' => "k16-stat-card block border {$classes}"]) }}
    >
        <p class="text-base font-medium opacity-90">{{ $label }}</p>
        <p class="mt-1 text-k16-display tabular-nums">{{ $value }}</p>
        @if ($hint)
            <p class="mt-2 text-sm font-medium opacity-80">{{ $hint }}</p>
        @endif
    </a>
@else
    <div {{ $attributes->merge(['class' => "k16-stat-card border {$classes}"]) }}>
        <p class="text-base font-medium opacity-90">{{ $label }}</p>
        <p class="mt-1 text-k16-display tabular-nums">{{ $value }}</p>
        @if ($hint)
            <p class="mt-2 text-sm font-medium opacity-80">{{ $hint }}</p>
        @endif
    </div>
@endif
