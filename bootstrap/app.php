<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
  ->withRouting(
    web: base_path('routes/web.php'),
    api: base_path('routes/api.php'),
    commands: base_path('routes/console.php'),
    channels: base_path('routes/channels.php'),
    health: '/up',
    then: function () {
      require base_path('bootstrap/routes.php');
    }
  )
  ->withMiddleware(function (Middleware $middleware): void {

    $middleware->alias([
      /**
       * Utility / infrastructure
       */
      'log' => \App\Http\Middleware\Log::class,
      'basic.auth' => \App\Http\Middleware\BasicAuth::class,

      /**
       * API key–based authentication (static secret)
       */
      'api.auth' => \App\Http\Middleware\ApiAuth::class,
      'webhook.auth' => \App\Http\Middleware\WebhookAuth::class,

      /**
       * HMAC signature–based authentication (signed body)
       */
      'api.signature' => \App\Http\Middleware\VerifyApiSignature::class,
      'webhook.signature' => \App\Http\Middleware\VerifyWebhookSignature::class,

      /**
       * Sandbox / tooling
       */
      'sandbox.access' => \App\Http\Middleware\SandboxAccess::class,
      'toolbox.access' => \App\Http\Middleware\SandboxAccess::class,

      /**
       * WordPress integration
       */
      // 'wp.can' => \App\Http\Middleware\CheckWpCapability::class,
      'sync.wp.user' => \App\Http\Middleware\SyncWpUser::class,

      /**
       * WordPress roles / capabilities (single-check semantics)
       *
       * Route::get('/admin', function () {
       *   // ...
       * })->middleware(['auth', 'has.wp.role:administrator']);
       *
       * Route::get('/settings', function () {
       *   // ...
       * })->middleware(['auth', 'has.wp.capability:manage_options']);
       *
       * For "admin OR editor" or multiple caps, stack middleware
       * or implement explicit logic in your controller/policy.
       */
      'has.wp.role'       => \App\Http\Middleware\HasWpRole::class,
      'has.wp.capability' => \App\Http\Middleware\HasWpCapability::class,

    ]);

    /*
    |--------------------------------------------------------------------------
    | Web stack additions
    |--------------------------------------------------------------------------
    */

    // Optional: attach request tracing to all web routes
    $middleware->web(append: [
      \App\Http\Middleware\Log::class,
      \App\Http\Middleware\SyncWordPressAuth::class,
    ]);

    // Replace the default CSRF middleware with your customized one
    $middleware->replace(
      \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
      \App\Http\Middleware\VerifyCsrfToken::class
    );

  })
  ->withExceptions(function (Exceptions $exceptions): void {
    //
  })
  ->create();
