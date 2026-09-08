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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'kirimi' => [
        'endpoint' => env('WA_ENDPOINT', 'https://api.kirimi.id/v1/send-message'),
        'user_code' => env('WA_USER_CODE'),
        'device_id' => env('WA_DEVICE_ID'),
        'secret' => env('WA_SECRET'),
        'ca_cert_path' => env('WA_CURL_CAINFO'),
        'connect_timeout' => (int) env('WA_CONNECT_TIMEOUT', 15),
        'timeout' => (int) env('WA_TIMEOUT', 30),
        'delay_min_seconds' => (int) env('WA_DELAY_MIN_SECONDS', 5),
        'delay_max_seconds' => (int) env('WA_DELAY_MAX_SECONDS', 10),
        'send_start_hour' => (int) env('WA_SEND_START_HOUR', 8),
        'send_end_hour' => (int) env('WA_SEND_END_HOUR', 20),
    ],

];
