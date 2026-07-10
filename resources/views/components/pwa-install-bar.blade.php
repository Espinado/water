@props([
    'appKey',
    'aboveNav' => false,
])

@php
    $config = app(\App\Services\PwaContext::class)->appConfig($appKey);
    $isManager = $appKey === 'manager';
@endphp

<div
    id="pwa-install-bar"
    hidden
    role="region"
    aria-label="{{ __('Установка приложения') }}"
    @class([
        'fixed inset-x-0 z-[45] border-t shadow-lg transition-transform duration-300 ease-out translate-y-full',
        'border-emerald-200/80 bg-emerald-50/95 backdrop-blur-sm' => ! $isManager,
        'border-red-200/80 bg-red-50/95 backdrop-blur-sm' => $isManager,
        'bottom-0 lg:bottom-0' => ! $aboveNav,
        'bottom-[calc(4.25rem+env(safe-area-inset-bottom))] lg:bottom-0' => $aboveNav,
    ])
    data-pwa-above-nav="{{ $aboveNav ? '1' : '0' }}"
>
    <div class="mx-auto flex max-w-7xl items-center gap-3 px-4 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
        <img
            src="{{ asset($config['icons'].'/icon.svg') }}"
            alt=""
            width="36"
            height="36"
            class="hidden shrink-0 rounded-xl shadow-sm ring-2 ring-white sm:block"
        >
        <p id="pwa-install-bar-message" class="min-w-0 flex-1 text-sm leading-snug text-slate-800">
            {{ __('Установите приложение — так входить удобнее') }}
        </p>
        <div class="flex shrink-0 items-center gap-2">
            <button
                id="pwa-install-bar-button"
                type="button"
                @class([
                    'inline-flex min-h-[40px] items-center justify-center rounded-xl px-4 text-sm font-bold text-white shadow-sm disabled:cursor-not-allowed disabled:opacity-70',
                    'bg-emerald-600 hover:bg-emerald-700' => ! $isManager,
                    'bg-red-600 hover:bg-red-700' => $isManager,
                ])
            >
                {{ __('Установить') }}
            </button>
            <button
                id="pwa-install-bar-dismiss"
                type="button"
                class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-slate-500 hover:bg-white/60 hover:text-slate-800"
                aria-label="{{ __('Не сейчас') }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                </svg>
            </button>
        </div>
    </div>
</div>
