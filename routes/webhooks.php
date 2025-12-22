<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Webhook\WpUserWebhookController;

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
|
| Prefix: /webhook
| CSRF: excluded via VerifyCsrfToken (webhook/*)
| Auth: HMAC signature (verify.hmac)
|
*/

Route::prefix('webhook')->middleware(['webhook.auth'])->group(function () {

    Route::get('/test', function (Request $request) {
      return response()->json([
        'status' => 'success',
        'message' => 'Webhook received',
        'headers' => $request->headers->all(),
        'payload' => $request->all(),
        'timestamp' => now()->toISOString(),
      ]);
    })->name('webhook.test');

    /*
    |--------------------------------------------------------------------------
    | WordPress → Laravel User Sync Webhook
    |--------------------------------------------------------------------------
    */
    Route::post('/wp/user-event', [WpUserWebhookController::class, 'handle'])
      ->withoutMiddleware('webhook.auth')
      ->middleware('webhook.auth:api_secrets.webhook_wp_event')
      ->name('webhook.wp.user_event');

    // Test route (still protected by group-level secret)
    Route::get('/test', [WpUserWebhookController::class, 'test'])
      ->withoutMiddleware('webhook.auth')
      ->middleware('webhook.auth:api_secrets.webhook_wp_event')
      ->name('webhook.test');

  });
