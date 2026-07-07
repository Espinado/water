@props([
    'active' => 'all',
])

<div {{ $attributes->merge(['class' => 'flex flex-wrap gap-2']) }} role="group" aria-label="{{ __('Фильтр') }}">
    @php
        $chips = [
            'debt' => ['label' => __('Долг'), 'active' => 'bg-rose-600 text-white shadow-md shadow-rose-200', 'idle' => 'bg-rose-50 text-rose-800 ring-1 ring-rose-200 hover:bg-rose-100'],
            'submitted' => ['label' => __('Сданы'), 'active' => 'bg-emerald-600 text-white shadow-md shadow-emerald-200', 'idle' => 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200 hover:bg-emerald-100'],
            'all' => ['label' => __('Все'), 'active' => 'bg-indigo-600 text-white shadow-md shadow-indigo-200', 'idle' => 'bg-indigo-50 text-indigo-800 ring-1 ring-indigo-200 hover:bg-indigo-100'],
            'no_login' => ['label' => __('Не входили'), 'active' => 'bg-amber-600 text-white shadow-md shadow-amber-200', 'idle' => 'bg-amber-50 text-amber-900 ring-1 ring-amber-200 hover:bg-amber-100'],
        ];
    @endphp

    @foreach ($chips as $key => $chip)
        <button
            type="button"
            wire:click="$set('statusFilter', '{{ $key }}')"
            @class([
                'manager-filter-chip rounded-full px-4 py-2 text-sm font-semibold transition min-h-[40px]',
                $chip['active'] => $active === $key,
                $chip['idle'] => $active !== $key,
            ])
        >
            {{ $chip['label'] }}
        </button>
    @endforeach
</div>
