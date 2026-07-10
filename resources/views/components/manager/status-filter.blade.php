@props([
    'active' => 'all',
])

<div {{ $attributes->merge(['class' => 'flex flex-wrap gap-2']) }} role="group" aria-label="{{ __('Фильтр') }}">
    @php
        $chips = [
            'all' => ['label' => __('Все'), 'active' => 'bg-k16-accent text-white', 'idle' => 'border border-k16-border bg-k16-surface text-k16-text hover:border-k16-accent/40'],
            'debt' => ['label' => __('Долг'), 'active' => 'bg-k16-danger text-white', 'idle' => 'border border-k16-danger/30 bg-k16-danger-soft text-k16-danger'],
            'submitted' => ['label' => __('Сданы'), 'active' => 'bg-k16-success text-white', 'idle' => 'border border-k16-success/30 bg-k16-success-soft text-k16-success'],
            'no_login' => ['label' => __('Не входили'), 'active' => 'bg-k16-warning text-white', 'idle' => 'border border-k16-warning/30 bg-k16-warning-soft text-k16-warning'],
            'no_resident' => ['label' => __('Без жильца'), 'active' => 'bg-violet-600 text-white', 'idle' => 'border border-violet-300 bg-violet-50 text-violet-700'],
        ];
    @endphp

    @foreach ($chips as $key => $chip)
        <button
            type="button"
            wire:click="$set('statusFilter', '{{ $key }}')"
            @class([
                'k16-filter-chip',
                $chip['active'] => $active === $key,
                $chip['idle'] => $active !== $key,
            ])
        >
            {{ $chip['label'] }}
        </button>
    @endforeach
</div>
