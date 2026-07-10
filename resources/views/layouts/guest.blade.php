<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php($pwaAppKey = $pwaAppKey ?? app(\App\Services\AppHost::class)->forRequest())

        <title>@isset($pwaAppKey){{ app(\App\Services\PwaContext::class)->appConfig($pwaAppKey)['name'] }}@else{{ config('app.name', 'Laravel') }}@endisset</title>

        @isset($pwaAppKey)
            <x-pwa-meta :app-key="$pwaAppKey" />
            <x-pwa-init :app-key="$pwaAppKey" />
        @endisset

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body @class([
        'font-sans text-slate-900 antialiased',
        'k16-theme' => ($pwaAppKey ?? '') === 'manager',
    ])>
        <div class="min-h-screen flex flex-col justify-center items-center px-4 py-8">
            <div class="absolute top-4 end-4">
                <x-language-switcher />
            </div>
            <div class="mb-4">
                @php($appHost = app(\App\Services\AppHost::class))
                <a href="{{ $appHost->absoluteUrl($appHost->forRequest(), auth()->check() ? '/dashboard' : '/login') }}">
                    <x-application-logo @class([
                        'w-16 h-16 sm:w-20 sm:h-20 fill-current',
                        'text-red-600' => ($pwaAppKey ?? '') === 'manager',
                        'text-emerald-600' => ($pwaAppKey ?? '') !== 'manager',
                    ]) />
                </a>
            </div>

            <div class="w-full max-w-md px-6 py-6 app-card">
                {{ $slot }}
            </div>
        </div>

        <x-wait-overlay
            id="app-page-loading"
            :color="($pwaAppKey ?? '') === 'manager' ? 'red' : 'emerald'"
            class="hidden"
        />

        @isset($pwaAppKey)
            <x-pwa-install-bar :app-key="$pwaAppKey" />
        @endisset
    </body>
</html>
