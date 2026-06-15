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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'whatsapp' => [
        'url' => env('WHATSAPP_SERVICE_URL', 'http://localhost:3000'),
        'timeout' => env('WHATSAPP_TIMEOUT', 30),
    ],

    'license' => [
        'key' => env('LICENSE_KEY'),
        'school_id' => env('SCHOOL_ID'),
        'integrity_key' => env('LICENSE_INTEGRITY_KEY'),
        'rsa_public_key' => env('LICENSE_RSA_PUBLIC_KEY'),
        'vendor_phone' => env('VENDOR_PHONE', '+92 300 0000000'),
    ],

    'firebase' => [
        'api_key' => env('FIREBASE_API_KEY'),
        'project_id' => env('FIREBASE_PROJECT_ID'),
    ],

];
