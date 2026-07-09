<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $appConfig['name'] }}</title>

        <x-pwa-meta :app-key="$appKey" />

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-900" style="--pwa-theme: {{ $appConfig['theme_color'] }}">
        <div class="min-h-screen px-4 py-8" style="background: linear-gradient(180deg, {{ $appConfig['background_color'] }} 0%, #ffffff 45%);">
            <div class="absolute top-4 end-4">
                <x-language-switcher />
            </div>

            <div class="mx-auto flex max-w-md flex-col items-center pt-8 text-center">
                <img
                    src="{{ asset($appConfig['icons'].'/icon.svg') }}"
                    alt=""
                    width="96"
                    height="96"
                    class="rounded-3xl shadow-lg ring-4 ring-white"
                >

                <h1 class="mt-6 text-2xl font-bold text-slate-900">{{ $appConfig['name'] }}</h1>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ __($appConfig['description']) }}</p>

                <div class="mt-8 w-full rounded-2xl bg-white p-5 text-left shadow-sm ring-1 ring-slate-100">
                    <p class="text-sm font-semibold text-slate-900">{{ __('Установка на телефон') }}</p>

                    <p id="pwa-http-warning" @class(['mt-3 rounded-xl bg-amber-50 px-3 py-2 text-sm leading-relaxed text-amber-950', 'hidden' => request()->secure()])>
                        {{ __('Для автоустановки откройте сайт по HTTPS (https://water.test). Или установите через меню браузера справа в адресной строке.') }}
                    </p>

                    <ol class="mt-3 space-y-3 text-sm leading-relaxed text-slate-700">
                        <li>
                            <span class="font-semibold text-slate-900">iPhone (Safari):</span>
                            {{ __('нажмите «Поделиться» → «На экран Домой»') }}
                        </li>
                        <li>
                            <span class="font-semibold text-slate-900">Android (Chrome):</span>
                            {{ __('меню браузера → «Установить приложение» или «Добавить на главный экран»') }}
                        </li>
                    </ol>

                    <p id="pwa-install-hint" class="mt-4 hidden rounded-xl bg-sky-50 px-3 py-2 text-sm font-medium text-sky-900">
                        {{ __('Можно установить прямо сейчас — кнопка ниже.') }}
                    </p>

                    <div id="pwa-install-progress" hidden class="mt-4 space-y-2 rounded-xl bg-slate-50 px-3 py-3 ring-1 ring-slate-100">
                        <div class="h-2 overflow-hidden rounded-full bg-slate-200">
                            <div
                                data-pwa-progress-bar
                                class="h-full rounded-full transition-all duration-500 ease-out"
                                style="width: 0%; background-color: {{ $appConfig['theme_color'] }}"
                            ></div>
                        </div>
                        <p data-pwa-progress-text class="text-sm font-medium text-slate-800">{{ __('Подготовка…') }}</p>
                        <div data-pwa-progress-actions class="hidden pt-1"></div>
                    </div>

                    <button
                        id="pwa-install-button"
                        type="button"
                        class="mt-4 inline-flex min-h-[48px] w-full items-center justify-center rounded-2xl px-4 text-sm font-bold text-white shadow-md disabled:cursor-not-allowed disabled:opacity-70"
                        style="background-color: {{ $appConfig['theme_color'] }}"
                    >
                        {{ __('Установить приложение') }}
                    </button>
                </div>

                <a
                    href="{{ route('pwa.open', $appKey) }}"
                    class="mt-6 inline-flex min-h-[52px] w-full items-center justify-center rounded-2xl px-4 text-sm font-bold text-white shadow-md"
                    style="background-color: {{ $appConfig['theme_color'] }}"
                >
                    {{ __('Войти') }}
                </a>

                @if ($appKey === 'resident')
                    <p class="mt-4 text-xs text-slate-500">
                        {{ __('Вы управляющий?') }}
                        <a href="{{ route('pwa.install', 'manager') }}" class="font-semibold text-emerald-700 hover:text-emerald-900">{{ __('Приложение для управляющего') }}</a>
                    </p>
                @else
                    <p class="mt-4 text-xs text-slate-500">
                        {{ __('Вы жилец?') }}
                        <a href="{{ route('pwa.install', 'resident') }}" class="font-semibold text-sky-700 hover:text-sky-900">{{ __('Приложение для жильца') }}</a>
                    </p>
                @endif
            </div>
        </div>

        <script>
            window.__PWA_APP__ = @json($appKey);
            window.__PWA_OPEN_URL__ = @json(url(config("pwa.apps.{$appKey}.start_url")));
            window.__PWA_SECURE__ = @json(request()->secure());
            window.__PWA_LABELS__ = {
                preparing: @json(__('Подготовка…')),
                confirm: @json(__('Подтвердите установку в окне браузера')),
                installing: @json(__('Установка…')),
                done: @json(__('Приложение установлено')),
                cancelled: @json(__('Установка отменена')),
                openApp: @json(__('Открыть приложение')),
                retry: @json(__('Попробовать снова')),
                unavailable: @json(__('Автоустановка недоступна. Используйте меню браузера (иконка установки в адресной строке).')),
                needsHttps: @json(__('Для установки нужен HTTPS. Откройте :url', ['url' => 'https://'.request()->getHost().'/app/'.$appKey])),
            };
        </script>
    </body>
</html>
