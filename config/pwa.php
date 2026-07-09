<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Две отдельные «аппликации» (PWA) на телефоне
    |--------------------------------------------------------------------------
    |
    | Жилец и управляющий устанавливают разные ярлыки с разными иконками
    | и стартовой страницей. Страницы установки: /app/resident и /app/manager
    |
    */

    'apps' => [

        'resident' => [
            'id' => 'k16-resident',
            'name' => 'K16 — жилец',
            'short_name' => 'K16',
            'description' => 'Сдача показаний счётчиков воды',
            'theme_color' => '#0284c7',
            'background_color' => '#f0f9ff',
            'start_url' => '/dashboard',
            'scope' => '/',
            'icons' => 'icons/resident',
            'login_route' => 'login.resident',
            'home_route' => 'dashboard',
        ],

        'manager' => [
            'id' => 'k16-manager',
            'name' => 'K16 — управляющий',
            'short_name' => 'K16 Pro',
            'description' => 'Управление домами и показаниями',
            'theme_color' => '#059669',
            'background_color' => '#ecfdf5',
            'start_url' => '/manager',
            'scope' => '/',
            'icons' => 'icons/manager',
            'login_route' => 'login.manager',
            'home_route' => 'manager.dashboard',
        ],

    ],

    'cookie' => 'pwa_app',

    'cookie_days' => 365,

];
