<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit;

return Application::configure(basePath: dirname(__DIR__))
  ->withRouting(
    web: __DIR__ . '/../routes/web.php',
    api: __DIR__ . '/../routes/api.php',
    commands: __DIR__ . '/../routes/console.php',
    channels: __DIR__ . '/../routes/channels.php',
    health: '/up',
    then: function () {
      /*
       |-------------------------------------------------------------
       | Rate limiting (optional, but matches old RouteServiceProvider)
       |-------------------------------------------------------------
       */
      RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
      });

      /*
       |-------------------------------------------------------------
       | Auth routes under /account (Breeze routes/auth.php)
       |-------------------------------------------------------------
       |
       | Your routes/auth.php currently already prefixes with 'account'
       | in the file (from our earlier update). If you keep that prefix
       | inside auth.php, DO NOT prefix here or you’ll get /account/account/*
       |
       | So you have two choices:
       |  A) Remove prefix('account') from routes/auth.php and add it here (recommended)
       |  B) Keep prefix in routes/auth.php and just group it with web middleware here
       */

      // Recommended: prefix here (ONLY if you remove Route::prefix('account') from routes/auth.php)
      Route::middleware('web')
        ->prefix('account')
        ->group(base_path('routes/auth.php'));

      /*
       |-------------------------------------------------------------
       | Extra web route files with centralized middleware/prefix/name
       |-------------------------------------------------------------
       */
      Route::middleware('web')
        ->group(base_path('routes/web-redirects.php'));

      Route::middleware(['web', 'sandbox'])
        ->prefix('sandbox')
        ->name('sandbox.')
        ->group(base_path('routes/sandbox.php'));
    }
  )
  ->withMiddleware(function (Middleware $middleware): void {
    //
  })
  ->withExceptions(function (Exceptions $exceptions): void {
    //
  })
  ->create();
