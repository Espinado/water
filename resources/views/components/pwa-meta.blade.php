@props(['appKey'])

@php
    $config = config("pwa.apps.{$appKey}");
@endphp

<link rel="manifest" href="{{ route('pwa.manifest', $appKey) }}">
<meta name="theme-color" content="{{ $config['theme_color'] }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="{{ $config['short_name'] }}">
<link rel="apple-touch-icon" href="{{ asset($config['icons'].'/icon.svg') }}">
