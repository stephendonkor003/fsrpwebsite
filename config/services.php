<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'microsoft_graph' => [
        'tenant_id' => env('MICROSOFT_TENANT_ID'),
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'certificate_path' => env('MICROSOFT_CERT_PATH'),
        'private_key_path' => env('MICROSOFT_PRIVATE_KEY_PATH'),
        'from_address' => env('MICROSOFT_GRAPH_FROM_ADDRESS', env('MAIL_FROM_ADDRESS')),
        'scope' => 'https://graph.microsoft.com/.default',
        'base_url' => 'https://graph.microsoft.com/v1.0',
        'connect_timeout' => (int) env('MICROSOFT_GRAPH_CONNECT_TIMEOUT', 10),
        'timeout' => (int) env('MICROSOFT_GRAPH_TIMEOUT', 30),
        'token_expiry_buffer' => (int) env('MICROSOFT_GRAPH_TOKEN_EXPIRY_BUFFER', 120),
        'max_attachment_bytes' => (int) env('MICROSOFT_GRAPH_MAX_ATTACHMENT_BYTES', 3_000_000),
        'certificate_expiry_warning_days' => (int) env('MICROSOFT_GRAPH_CERTIFICATE_EXPIRY_WARNING_DAYS', 30),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
