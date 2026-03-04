<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
     * خدمة النفاذ الوطني (للأفراد)
     */
    'nafath' => [
        'client_id' => env('NAFATH_CLIENT_ID'),
        'client_secret' => env('NAFATH_CLIENT_SECRET'),
        'redirect' => env('NAFATH_REDIRECT_URI'),
        'auth_base' => env('NAFATH_AUTH_BASE', 'https://nafath.sa'),
        'api_base' => env('NAFATH_API_BASE', 'https://api.nafath.sa'),
    ],

    /*
     * خدمة توثيق (للمنشآت)
     */
    'tawtheeq' => [
        'client_id' => env('TAWTHEEQ_CLIENT_ID'),
        'client_secret' => env('TAWTHEEQ_CLIENT_SECRET'),
        'redirect' => env('TAWTHEEQ_REDIRECT_URI'),
        'auth_base' => env('TAWTHEEQ_AUTH_BASE', 'https://tawtheeq.sa'),
        'api_base' => env('TAWTHEEQ_API_BASE', 'https://api.tawtheeq.sa'),
    ],

    /*
     * خدمة Lean (Open Banking)
     */
    'lean' => [
        'app_id' => env('LEAN_APP_ID'),
        'app_secret' => env('LEAN_APP_SECRET'),
        'webhook_secret' => env('LEAN_WEBHOOK_SECRET'),
        'redirect_url' => env('LEAN_REDIRECT_URL'),
        'base_url' => env('LEAN_BASE_URL', 'https://api.lean.dev'),
        'connect_url' => env('LEAN_CONNECT_URL', 'https://connect.lean.dev'),
    ],

    /*
     * خدمة سداد (Sadad)
     */
    'sadad' => [
        'merchant_id' => env('SADAD_MERCHANT_ID'),
        'merchant_secret' => env('SADAD_MERCHANT_SECRET'),
        'access_token' => env('SADAD_ACCESS_TOKEN'), // قد يكون مخزناً
        'base_url' => env('SADAD_BASE_URL', 'https://api.sadad.sa'),
    ],

];
