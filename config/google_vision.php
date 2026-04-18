<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Cloud Vision (счётчики)
    |--------------------------------------------------------------------------
    |
    | Укажите путь к JSON ключу сервисного аккаунта (IAM → Service accounts).
    | Переменная GOOGLE_APPLICATION_CREDENTIALS — стандарт для клиентов Google.
    |
    */

    'enabled' => env('GOOGLE_VISION_ENABLED', false),

    'credentials_path' => env('GOOGLE_APPLICATION_CREDENTIALS', ''),

    'transport' => env('GOOGLE_VISION_TRANSPORT', 'rest'),

];
