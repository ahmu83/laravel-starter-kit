<?php

return [
    // Default API KEYS specified in middlewares
    'default' => env('API_KEY_DEFAULT'),
    'webhook_default' => env('API_KEY_WEBHOOK_DEFAULT'),

    /**
     * Additional API keys as needed
     */
    'webhook_wp_event' => env('API_KEY_WEBHOOK_WP_EVENT'),
    // 'api_users_get'    => env('API_KEY_GET_USERS'),
    // 'api_users_create' => env('API_KEY_CREATE_USERS'),
];
