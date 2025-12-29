<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enable/Disable Package
    |--------------------------------------------------------------------------
    */
    'enabled' => env('VANTAGE_ENABLED', true),

    'store_payload' => env('VANTAGE_STORE_PAYLOAD', true),

    'redact_keys' => [
        'password', 'token', 'authorization', 'secret', 'api_key',
        'apikey', 'access_token', 'refresh_token', 'private_key',
        'card_number', 'cvv', 'ssn', 'credit_card',
    ],

    'retention_days' => env('VANTAGE_RETENTION_DAYS', 14),

    'notify_on_failure' => true,
    'notification_channels' => ['mail'],
    'notify' => [
        'email' => env('VANTAGE_NOTIFY_EMAIL', null),
        'slack_webhook' => env('VANTAGE_SLACK_WEBHOOK', null),
    ],

    'tagging' => [
        'enabled' => true,
        'auto_tags' => [
            'environment' => false,
            'queue_name' => true,
            'hour' => false,
        ],
        'max_tags_per_job' => 20,
        'sanitize' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Web Routes - STANDARD APPROACH
    |--------------------------------------------------------------------------
    |
    | Let Vantage auto-register its routes at the configured prefix.
    | This is the recommended approach from the official package.
    |
    */
    'routes' => env('VANTAGE_ROUTES', true),

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | This determines where Vantage will be accessible.
    | Default: 'vantage' → accessible at /vantage
    | Custom: 'vendor-tools/queues' → accessible at /vendor-tools/queues
    |
    */
    'route_prefix' => env('VANTAGE_ROUTE_PREFIX', 'vantage'),

    /*
    |--------------------------------------------------------------------------
    | Route Middleware - CUSTOM ACCESS CONTROL
    |--------------------------------------------------------------------------
    |
    | Apply custom middleware to all Vantage routes.
    | This is where we integrate with our feature gating system.
    |
    */
    'route_middleware' => [
        'web',
        'vantage.access', // ← Our custom feature gate middleware
    ],

    'telemetry' => [
        'enabled' => env('VANTAGE_TELEMETRY_ENABLED', true),
        'sample_rate' => (float) env('VANTAGE_TELEMETRY_SAMPLE_RATE', 1.0),
        'capture_cpu' => env('VANTAGE_TELEMETRY_CPU', true),
    ],

    'database_connection' => env('VANTAGE_DATABASE_CONNECTION', null),

    /*
    |--------------------------------------------------------------------------
    | Authentication - DISABLED
    |--------------------------------------------------------------------------
    |
    | We disable Vantage's built-in auth because we use our own
    | vantage.access middleware for access control.
    |
    */
    'auth' => [
        'enabled' => true, // ← Use vantage.access middleware instead
    ],

    'logging' => [
        'enabled' => env('VANTAGE_LOGGING_ENABLED', true),
    ],
];
