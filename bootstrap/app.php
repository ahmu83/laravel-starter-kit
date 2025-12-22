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
      'log' => \App\Http\Middleware\Log::class,

      'basic.auth' => \App\Http\Middleware\BasicAuth::class,

      'verify.hmac' => \App\Http\Middleware\VerifyHmacSignature::class,
      'api.auth' => \App\Http\Middleware\ApiAuth::class,
      'webhook.auth' => \App\Http\Middleware\WebhookAuth::class,

      'signed' => \App\Http\Middleware\ValidateSignature::class,

      'sandbox.access' => \App\Http\Middleware\SandboxAccess::class,
      'toolbox.access' => \App\Http\Middleware\SandboxAccess::class,

      'wp.can' => \App\Http\Middleware\CheckWpCapability::class,
      'sync.wp.user' => \App\Http\Middleware\SyncWpUser::class,
    ]);

    /*
    |--------------------------------------------------------------------------
    | Web stack additions
    |--------------------------------------------------------------------------
    */

    // Optional: attach request tracing to all web routes
    $middleware->web(append: [
      \App\Http\Middleware\Log::class,
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
