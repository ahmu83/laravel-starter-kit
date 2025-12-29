<?php

return [

    /*
  |--------------------------------------------------------------------------
  | Pulse (Application Monitoring)
  |--------------------------------------------------------------------------
  |
  | Laravel Pulse provides real-time application monitoring and metrics.
  |
  | enabled: Hard toggle to completely disable Pulse
  | enable_method: Request-level gating (ip:strict, auth:admin, etc.)
  |
  */

    'pulse' => [
        'enabled' => env('PULSE_ENABLED', true),
        'enable_method' => env('PULSE_ENABLE_METHOD', ''),
    ],

    /*
  |--------------------------------------------------------------------------
  | Log Viewer
  |--------------------------------------------------------------------------
  |
  | Opcodes Log Viewer provides a web interface for viewing application logs.
  |
  | enabled: Hard toggle to completely disable Log Viewer
  | enable_method: Request-level gating (ip:strict, auth:admin, etc.)
  |
  */

    'log_viewer' => [
        'enabled' => env('LOG_VIEWER_ENABLED', true),
        'enable_method' => env('LOG_VIEWER_ENABLE_METHOD', ''),
    ],

    /*
  |--------------------------------------------------------------------------
  | Debugbar
  |--------------------------------------------------------------------------
  |
  | Laravel Debugbar provides debugging information in a toolbar.
  |
  | enabled: Hard toggle to completely disable Debugbar
  | enable_method: Request-level gating (ip:strict, auth:admin, etc.)
  |
  | Note: Debugbar is conditionally enabled/disabled per-request via
  | ConditionalFeatureEnable middleware based on enable_method.
  |
  */

    'debugbar' => [
        'enabled' => env('DEBUGBAR_ENABLED', false),
        'enable_method' => env('DEBUGBAR_ENABLE_METHOD', ''),
    ],

    /*
  |--------------------------------------------------------------------------
  | App Debug Mode
  |--------------------------------------------------------------------------
  |
  | Controls whether detailed error pages (Ignition) are shown.
  |
  | enable_method: Request-level gating to show/hide detailed errors
  |
  | Note: APP_DEBUG in .env must be true for this to work. The enable_method
  | here provides an additional layer to conditionally disable debug mode
  | for certain requests even when APP_DEBUG=true.
  |
  */

    'app_debug' => [
        'enable_method' => env('APP_DEBUG_ENABLE_METHOD', ''),
    ],

    /*
  |--------------------------------------------------------------------------
  | Vantage (Queue Monitoring)
  |--------------------------------------------------------------------------
  |
  | Vantage provides queue monitoring and job tracking.
  |
  | enabled: Hard toggle to completely disable Vantage
  | enable_method: Request-level gating (ip:strict, auth:admin, etc.)
  |
  */

    'vantage' => [
        'enabled' => env('VANTAGE_ENABLED', true),
        'enable_method' => env('VANTAGE_ENABLE_METHOD', ''),
    ],

];
