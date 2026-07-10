<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php($pwaAppKey = 'manager')

        <title>{{ app(\App\Services\PwaContext::class)->appConfig('manager')['name'] }}</title>

        <x-pwa-meta app-key="manager" />
        <x-pwa-init app-key="manager" />
        <x-confirm-save-meta />

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="k16-theme font-sans antialiased">
        <div class="k16-shell min-h-screen bg-k16-bg">
            <aside class="k16-sidebar hidden w-60 shrink-0 border-r border-k16-border bg-k16-bg lg:fixed lg:inset-y-0 lg:left-0 lg:z-30 lg:flex lg:flex-col lg:overflow-y-auto">
                <div class="border-b border-k16-border px-5 py-5">
                    <p class="text-lg font-bold text-k16-text">{{ app(\App\Services\PwaContext::class)->appConfig('manager')['name'] }}</p>
                    <p class="mt-1 text-sm text-k16-text-muted">{{ __('Управление домами') }}</p>
                </div>
                <x-k16.nav variant="sidebar" class="flex-1" />
            </aside>

            <div class="k16-main flex min-h-screen min-w-0 flex-col lg:ml-60">
                <livewire:layout.manager-header />

                @if (isset($header))
                    <div class="border-b border-k16-border bg-k16-surface px-4 py-4 sm:px-6">
                        <div class="mx-auto max-w-7xl">
                            {{ $header }}
                        </div>
                    </div>
                @endif

                <main class="flex-1">
                    {{ $slot }}
                </main>
            </div>

            <x-k16.nav variant="bottom" />
        </div>

        <x-wait-overlay
            id="app-page-loading"
            color="k16"
            class="hidden"
        />

        <x-pwa-install-bar app-key="manager" :above-nav="true" />
    </body>
</html>
