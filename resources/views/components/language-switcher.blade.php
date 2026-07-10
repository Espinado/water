@props(['inline' => false])

@php
    $supported = config('locales.supported', []);
    $current = app()->getLocale();
    $currentMeta = $supported[$current] ?? reset($supported);
    $isManagerApp = app(\App\Services\AppHost::class)->isManager();
    $activeBorder = $isManagerApp ? 'border-red-300 bg-red-50 font-semibold text-red-700' : 'border-emerald-300 bg-emerald-50 font-semibold text-emerald-700';
    $activeItem = $isManagerApp ? 'bg-red-50 font-semibold text-red-700' : 'bg-emerald-50 font-semibold text-emerald-700';
@endphp

@if ($inline)
    <div {{ $attributes->merge(['class' => 'flex flex-wrap gap-2']) }}>
        @foreach ($supported as $code => $meta)
            <a
                href="{{ url('locale/'.$code) }}"
                @class([
                    'inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm',
                    $activeBorder => $code === $current,
                    'border-gray-200 bg-white text-gray-700' => $code !== $current,
                ])
            >
                <span class="text-base leading-none">{{ $meta['flag'] }}</span>
                <span>{{ $meta['native'] }}</span>
            </a>
        @endforeach
    </div>
@else
    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
        <button
            type="button"
            @click="open = ! open"
            {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none']) }}
        >
            <span class="text-base leading-none">{{ $currentMeta['flag'] ?? '' }}</span>
            <span>{{ $currentMeta['native'] ?? strtoupper($current) }}</span>
            <svg class="h-4 w-4 fill-current text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>

        <div
            x-show="open"
            x-transition
            style="display: none;"
            class="absolute end-0 z-50 mt-2 w-44 overflow-hidden rounded-xl border border-gray-100 bg-white shadow-lg"
        >
            @foreach ($supported as $code => $meta)
                <a
                    href="{{ url('locale/'.$code) }}"
                    @class([
                        'flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-gray-50',
                        $activeItem => $code === $current,
                        'text-gray-700' => $code !== $current,
                    ])
                >
                    <span class="text-base leading-none">{{ $meta['flag'] }}</span>
                    <span>{{ $meta['native'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
@endif
