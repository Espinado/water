<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI-зрение для чтения показаний счётчиков
    |--------------------------------------------------------------------------
    |
    | Мультимодальная модель (по умолчанию Google Gemini) получает фото табло
    | и возвращает показание напрямую. Это надёжнее эвристического разбора
    | текста от Google Cloud Vision, поэтому используется первым; при ошибке
    | или отсутствии ключа система откатывается на старый путь (Cloud Vision).
    |
    | Ключ Gemini бесплатно берётся в Google AI Studio:
    |   https://aistudio.google.com/apikey
    | и кладётся в .env как GEMINI_API_KEY (в код/git не коммитить).
    |
    */

    'enabled' => filter_var(
        env('AI_VISION_ENABLED', false),
        FILTER_VALIDATE_BOOLEAN,
    ),

    'provider' => env('AI_VISION_PROVIDER', 'gemini'),

    'timeout' => (int) env('AI_VISION_TIMEOUT', 30),

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY', ''),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'endpoint' => rtrim(
            env('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta'),
            '/',
        ),
    ],

];
