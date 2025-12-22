<?php

return [
  // Default secret if none is specified in middleware
  'default'          => env('SECRET_DEFAULT'),

  // Route-group level secrets
  'webhook_routes'   => env('SECRET_WEBHOOK_ROUTES'),

  // Integration-specific secrets (optional overrides)
  'api_default'      => env('SECRET_API_DEFAULT'),
  'webhook_default' => env('SECRET_WEBHOOK_DEFAULT'),

  'webhook_wp_event' => env('SECRET_WEBHOOK_WP_EVENT'),
  // 'api_users_get'    => env('API_SECRET_API_GET_USERS'),
  // 'api_users_create' => env('API_SECRET_API_CREATE_USERS'),
];




