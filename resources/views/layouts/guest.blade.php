<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@isset($pwaAppKey){{ config("pwa.apps.{$pwaAppKey}.name") }}@else{{ config('app.name', 'Laravel') }}@endisset</title>

        @isset($pwaAppKey)
            <x-pwa-meta :app-key="$pwaAppKey" />
        @endisset

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="min-h-screen flex flex-col justify-center items-center px-4 py-8">
            <div class="absolute top-4 end-4">
                <x-language-switcher />
            </div>
            <div class="mb-4">
                <a href="{{ route('dashboard') }}" wire:navigate>
                    <x-application-logo class="w-16 h-16 sm:w-20 sm:h-20 fill-current text-indigo-600" />
                </a>
            </div>

            <div class="w-full max-w-md px-6 py-6 app-card">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
