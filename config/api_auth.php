<?php

return [

  /*
  |--------------------------------------------------------------------------
  | API Keys
  |--------------------------------------------------------------------------
  |
  | API keys for authenticating API requests via the ApiAuth middleware.
  |
  | Generate secure keys using:
  | php -r "echo bin2hex(random_bytes(32));"
  |
  | Configure in .env:
  | API_KEY_1=a1b2c3d4e5f6...
  | API_KEY_2=x9y8z7w6v5u4...
  |
  */

  'keys'   => array_filter([
    env('API_KEY_1'),
    env('API_KEY_2'),
    env('API_KEY_3'),
  ]),

  /*
  |--------------------------------------------------------------------------
  | Header Name
  |--------------------------------------------------------------------------
  |
  | The HTTP header name to look for the API key.
  | Default: X-API-KEY
  |
  | You can customize this via .env:
  | API_AUTH_HEADER=X-Custom-Key
  |
  */

  'header' => env('API_AUTH_HEADER', 'X-API-KEY'),

];
