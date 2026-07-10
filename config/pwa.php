<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Две отдельные «аппликации» (PWA) на телефоне
    |--------------------------------------------------------------------------
    |
    | Жилец и управляющий устанавливают разные ярлыки с разными иконками
    | и стартовой страницей. Управляющий — на поддомене manager.*
    |
    */

    'apps' => [

        'resident' => [
            'id' => 'k16-resident',
            'name' => 'K16 — жилец',
            'short_name' => 'K16',
            'description' => 'Сдача показаний счётчиков воды',
            'theme_color' => '#059669',
            'background_color' => '#ecfdf5',
            'start_url' => '/app/resident/open',
            'scope' => '/',
            'icons' => 'icons/resident',
            'login_route' => 'login.resident',
            'home_route' => 'dashboard',
            'manifest_route' => 'pwa.manifest',
            'install_route' => 'pwa.install',
            'open_route' => 'pwa.open',
            'continue_route' => 'pwa.continue',
        ],

        'manager' => [
            'id' => 'k16-manager',
            'name' => 'K16 — управляющий',
            'short_name' => 'K16 Pro',
            'description' => 'Управление домами и показаниями',
            'theme_color' => '#dc2626',
            'background_color' => '#fef2f2',
            'start_url' => '/app/open',
            'scope' => '/',
            'icons' => 'icons/manager',
            'login_route' => 'login.manager',
            'home_route' => 'manager.dashboard',
            'manifest_route' => 'manager.pwa.manifest',
            'install_route' => 'manager.pwa.install',
            'open_route' => 'manager.pwa.open',
            'continue_route' => 'manager.pwa.continue',
        ],

    ],

    'cookie' => 'pwa_app',

    'cookie_days' => 365,

    /*
    |--------------------------------------------------------------------------
    | Нижняя панель «Установить приложение»
    |--------------------------------------------------------------------------
    |
    | После закрытия панели повторный показ через указанное число часов.
    | Если приложение удалено с телефона — панель показывается снова.
    |
    */

    'install_prompt_interval_hours' => 72,

    'install_prompt_delay_ms' => 2500,

];
