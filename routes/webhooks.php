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
| Auth: api.auth (X-API-KEY)
|
*/

Route::prefix('webhook')->group(function () {

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
    ->middleware(['api.auth'])
    ->name('webhook.wp.user_event');

  Route::get('/wp/user-event', [WpUserWebhookController::class, 'test'])
    ->name('webhook.wp.user_event.test');

});
