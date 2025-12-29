<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Vendor Tools Routes (/vendor-tools/*)
|--------------------------------------------------------------------------
|
| Third-party monitoring and debugging tools.
| Each tool uses its own feature gate middleware.
|
*/

Route::middleware(['web'])
  ->prefix('vendor-tools')
  ->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Pulse (Application Monitoring)
    |--------------------------------------------------------------------------
    */
    // Pulse registers its own routes automatically via config/pulse.php
    // Access controlled by pulse.access middleware

    /*
    |--------------------------------------------------------------------------
    | Log Viewer
    |--------------------------------------------------------------------------
    */
    // Log Viewer registers its own routes automatically via config/log-viewer.php
    // Access controlled by logviewer.access middleware

    /*
    |--------------------------------------------------------------------------
    | Vantage (Queue Monitoring)
    |--------------------------------------------------------------------------
    */
    // Route::middleware(['vantage.access'])
    //   ->prefix('queues')
    //   ->group(function () {
    //     require base_path('routes/vendor/vantage.php');
    //   });

  });
