<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
  ->withRouting(
    web: __DIR__ . '/../routes/web.php',
    api: __DIR__ . '/../routes/api.php',
    commands: __DIR__ . '/../routes/console.php',
    channels: __DIR__ . '/../routes/channels.php',
    health: '/up',
    then: function () {
      require base_path('bootstrap/routes.php');
    }
  )
  ->withMiddleware(function (Middleware $middleware): void {

    /*
     |--------------------------------------------------------------------------
     | Middleware aliases
     |--------------------------------------------------------------------------
     |
     | Aliases can be used on routes/groups like:
     | Route::middleware(['web', 'sandbox'])->group(...)
     |
     */

    $middleware->alias([
      'log' => \App\Http\Middleware\Log::class,
      'basic.auth' => \App\Http\Middleware\BasicAuth::class,
      'sandbox' => \App\Http\Middleware\SandboxMiddleware::class,
    ]);

    /*
     |--------------------------------------------------------------------------
     | Middleware groups
     |--------------------------------------------------------------------------
     |
     | Define custom groups similar to the old Http\Kernel.php groups.
     | This allows you to attach a "sandbox" group without repeating
     | multiple middleware names everywhere.
     |
     */

    // $middleware->group('sandbox', [
    //   'sandbox',
    //   'basic.auth',
    // ]);

    /*
     |--------------------------------------------------------------------------
     | Web stack additions
     |--------------------------------------------------------------------------
     |
     | Ensure our custom CSRF middleware (with webhook exemptions)
     | is part of the web middleware stack.
     |
     | Note: If Laravel already includes CSRF by default in your stack,
     | you can keep this, but avoid registering duplicate CSRF middleware
     | if you later customize the full web stack.
     |
     */

    $middleware->web(append: [
      // Optional: add request tracing on all web routes
      // Comment out if you don't want this globally.
      \App\Http\Middleware\Log::class,

      \App\Http\Middleware\VerifyCsrfToken::class,
    ]);

  })
  ->withExceptions(function (Exceptions $exceptions): void {
    //
  })
  ->create();



