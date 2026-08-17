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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        'webapp_url' => env('TELEGRAM_WEBAPP_URL', env('APP_URL')),
    ],

    'rabbitmq' => [
        'host' => env('RABBIT_HOST', 'rabbitmq'),
        'port' => env('RABBITMQ_PORT', 5672),
        'user' => env('RABBITMQ_USER', 'fastsearch'),
        'password' => env('RABBITMQ_PASSWORD', 'secret_password'),
    ],

    'ai' => [
        'api_key' => env('AI_API_KEY'),
        'api_keys' => env('AI_API_KEYS') ? array_map('trim', explode(',', env('AI_API_KEYS'))) : [],
    ],

    'lavatop' => [
        'api_key' => env('LAVA_TOP_API_KEY'),
        'base_url' => env('LAVA_TOP_BASE_URL', 'https://gate.lava.top/api/v3'),
        'webhook_api_key' => env('LAVA_TOP_WEBHOOK_API_KEY'),
    ],

];
