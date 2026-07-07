@props([
    'href' => null,
    'label',
    'value',
    'hint' => null,
    'tone' => 'indigo',
])

@php
    $tones = [
        'rose' => 'border-rose-200 bg-gradient-to-br from-rose-50 to-white text-rose-950 ring-rose-100',
        'emerald' => 'border-emerald-200 bg-gradient-to-br from-emerald-50 to-white text-emerald-950 ring-emerald-100',
        'amber' => 'border-amber-200 bg-gradient-to-br from-amber-50 to-white text-amber-950 ring-amber-100',
        'sky' => 'border-sky-200 bg-gradient-to-br from-sky-50 to-white text-sky-950 ring-sky-100',
        'violet' => 'border-violet-200 bg-gradient-to-br from-violet-50 to-white text-violet-950 ring-violet-100',
        'indigo' => 'border-indigo-200 bg-gradient-to-br from-indigo-50 to-white text-indigo-950 ring-indigo-100',
    ];
    $classes = $tones[$tone] ?? $tones['indigo'];
@endphp

@if ($href)
    <a
        href="{{ $href }}"
        wire:navigate
        {{ $attributes->merge(['class' => "manager-stat-card block rounded-2xl border p-4 shadow-sm ring-1 transition hover:-translate-y-0.5 hover:shadow-md sm:p-5 {$classes}"]) }}
    >
        <p class="text-sm font-medium opacity-80">{{ $label }}</p>
        <p class="mt-1 text-3xl font-bold tabular-nums tracking-tight sm:text-4xl">{{ $value }}</p>
        @if ($hint)
            <p class="mt-2 text-xs font-medium opacity-70">{{ $hint }}</p>
        @endif
    </a>
@else
    <div {{ $attributes->merge(['class' => "manager-stat-card rounded-2xl border p-4 shadow-sm ring-1 sm:p-5 {$classes}"]) }}>
        <p class="text-sm font-medium opacity-80">{{ $label }}</p>
        <p class="mt-1 text-3xl font-bold tabular-nums tracking-tight sm:text-4xl">{{ $value }}</p>
        @if ($hint)
            <p class="mt-2 text-xs font-medium opacity-70">{{ $hint }}</p>
        @endif
    </div>
@endif
