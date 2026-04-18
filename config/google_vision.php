<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Cloud Vision (счётчики)
    |--------------------------------------------------------------------------
    |
    | JSON ключ из GCP (IAM → Service accounts → Keys) храните в
    | storage/app/private/ — каталог не отдаётся через веб и игнорируется git.
    |
    | В .env задайте GOOGLE_APPLICATION_CREDENTIALS абсолютным путём к файлу,
    | например: /home/.../storage/app/private/vision-key.json
    | или C:\...\storage\app\private\vision-key.json
    |
    */

    'enabled' => filter_var(
        env('GOOGLE_VISION_ENABLED', false),
        FILTER_VALIDATE_BOOLEAN,
    ),

    'credentials_path' => env('GOOGLE_APPLICATION_CREDENTIALS', ''),

    'transport' => env('GOOGLE_VISION_TRANSPORT', 'rest'),

];
