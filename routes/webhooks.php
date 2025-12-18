<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
|
| Webhook endpoints for third-party services (Stripe, GitHub, etc.)
| These routes are excluded from CSRF protection in VerifyCsrfToken middleware.
|
| Prefix: /webhooks
| Middleware: web
| Note: Always verify webhook signatures/secrets for security!
|
*/

Route::middleware(['web'])->prefix('webhooks')->group(function () {

  /*
  |--------------------------------------------------------------------------
  | Webhook Test Endpoint
  |--------------------------------------------------------------------------
  */

  Route::get('/test', function (Request $request) {
    return response()->json([
      'status' => 'success',
      'message' => 'Webhook received',
      'headers' => $request->headers->all(),
      'payload' => $request->all(),
      'timestamp' => now()->toISOString(),
    ]);
  })->name('webhook.test');

});


