<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php($pwaAppKey = $pwaAppKey ?? app(\App\Services\PwaContext::class)->appKey())

        <title>@isset($pwaAppKey){{ config("pwa.apps.{$pwaAppKey}.name") }}@else{{ config('app.name', 'Laravel') }}@endisset</title>

        @isset($pwaAppKey)
            <x-pwa-meta :app-key="$pwaAppKey" />
        @endisset

        <x-confirm-save-meta />

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-900">
        <div class="app-shell">
            <livewire:layout.navigation />

            <!-- Page Heading -->
            @if (isset($header))
                <header class="mx-3 mt-3 sm:mx-6">
                    <div class="app-card max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="pb-6">
                {{ $slot }}
            </main>
        </div>

        <x-wait-overlay
            id="app-page-loading"
            :color="($pwaAppKey ?? '') === 'manager' ? 'emerald' : 'sky'"
            class="flex"
        />
    </body>
</html>
