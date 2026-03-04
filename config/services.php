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

    'warehouse' => [
        'endpoint' => env('WAREHOUSE_API'),
        'token'    => env('WAREHOUSE_TOKEN'),
    ],

    'tactical' => [
        'endpoint' => env('TACTICAL_WAREHOUSE_API'),
        'email'    => env('TACTICAL_WAREHOUSE_EMAIL'),
        'password' => env('TACTICAL_WAREHOUSE_PASSWORD'),
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
    ],

    'spapi' => [
        'key' => env('SPAPI_SELLER_ID'),
    ],
    'openweather' => [
        'key'     => env('OPENWEATHER_API_KEY'),
        'base'    => env('OPENWEATHER_BASE_URL', 'https://api.openweathermap.org/data/2.5'),
        'city'    => env('OPENWEATHER_DEFAULT_CITY', 'Chennai'),
        'country' => env('OPENWEATHER_DEFAULT_COUNTRY', 'IN'),
        'units'   => env('OPENWEATHER_UNITS', 'metric'), // metric|imperial
    ],
];
