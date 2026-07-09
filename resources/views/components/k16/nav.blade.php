@props([
    'variant' => 'bottom',
])

@php
    $items = [
        [
            'route' => 'manager.dashboard',
            'active' => request()->routeIs('manager.dashboard'),
            'label' => __('Главная'),
            'icon' => 'home',
        ],
        [
            'route' => 'manager.apartments',
            'active' => request()->routeIs('manager.apartments'),
            'label' => __('Жильцы'),
            'icon' => 'users',
        ],
        [
            'route' => 'manager.readings',
            'active' => request()->routeIs('manager.readings*'),
            'label' => __('Показания'),
            'icon' => 'meter',
        ],
        [
            'route' => 'manager.setup',
            'active' => request()->routeIs('manager.setup'),
            'label' => __('Дома'),
            'icon' => 'building',
        ],
        [
            'route' => 'manager.suppliers',
            'active' => request()->routeIs('manager.suppliers'),
            'label' => __('Поставщики'),
            'icon' => 'supplier',
        ],
    ];
@endphp

@if ($variant === 'sidebar')
    <nav {{ $attributes->merge(['class' => 'flex flex-col gap-1 p-4']) }} aria-label="{{ __('Разделы') }}">
        @foreach ($items as $item)
            <a
                href="{{ route($item['route']) }}"
                wire:navigate
                @class([
                    'k16-sidebar-link',
                    'k16-sidebar-link-active' => $item['active'],
                    'k16-sidebar-link-idle' => ! $item['active'],
                ])
            >
                @include('components.k16.icons.'.$item['icon'], ['class' => 'h-5 w-5 shrink-0'])
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>
@else
    <nav {{ $attributes->merge(['class' => 'fixed inset-x-0 bottom-0 z-40 border-t border-k16-border bg-k16-surface lg:hidden']) }} aria-label="{{ __('Разделы') }}">
        <div class="mx-auto grid max-w-lg grid-cols-5 gap-1 px-2 pb-[max(0.5rem,env(safe-area-inset-bottom))] pt-2">
            @foreach ($items as $item)
                <a
                    href="{{ route($item['route']) }}"
                    wire:navigate
                    @class([
                        'k16-nav-link',
                        'k16-nav-link-active' => $item['active'],
                        'k16-nav-link-idle' => ! $item['active'],
                    ])
                >
                    @include('components.k16.icons.'.$item['icon'], ['class' => 'h-6 w-6'])
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    </nav>
@endif
