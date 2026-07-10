@props(['appKey', 'withManifest' => true])

@php
    $config = app(\App\Services\PwaContext::class)->appConfig($appKey);
@endphp

@if ($withManifest)
    @php($manifestRoute = app(\App\Services\PwaContext::class)->manifestRoute($appKey))
    <link rel="manifest" href="{{ $appKey === 'manager' ? route($manifestRoute) : route($manifestRoute, $appKey) }}">
@endif
<meta name="theme-color" content="{{ $config['theme_color'] }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="{{ $config['short_name'] }}">
@if ($withManifest)
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset($config['icons'].'/icon-180.png') }}">
    <link rel="apple-touch-icon" sizes="192x192" href="{{ asset($config['icons'].'/icon-192.png') }}">
    <link rel="apple-touch-icon" sizes="512x512" href="{{ asset($config['icons'].'/icon-512.png') }}">
@endif
