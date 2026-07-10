<?php

$residentUrl = rtrim((string) env('APP_URL', 'http://localhost'), '/');
$parsed = parse_url($residentUrl);
$scheme = $parsed['scheme'] ?? 'http';
$host = $parsed['host'] ?? 'localhost';
$port = isset($parsed['port']) ? ':'.$parsed['port'] : '';
$managerHost = (string) env('MANAGER_HOST', 'manager.'.$host);

return [

    'resident_url' => $residentUrl,

    'manager_url' => rtrim((string) env('MANAGER_URL', "{$scheme}://{$managerHost}{$port}"), '/'),

    'session_cookies' => [
        'resident' => env('RESIDENT_SESSION_COOKIE'),
        'manager' => env('MANAGER_SESSION_COOKIE'),
    ],

];
