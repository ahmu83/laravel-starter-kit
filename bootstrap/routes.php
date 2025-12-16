<?php

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rate limiting (replacement for RouteServiceProvider::boot)
|--------------------------------------------------------------------------
*/
RateLimiter::for('api', function (Request $request) {
  return Limit::perMinute(60)->by(
    $request->user()?->id ?: $request->ip()
  );
});

/*
|--------------------------------------------------------------------------
| Auth routes (/account/*)
|--------------------------------------------------------------------------
|
| routes/auth.php MUST NOT prefix itself with 'account'
| Prefix + middleware are applied here centrally.
|
*/
Route::middleware('web')
  ->prefix('account')
  ->group(base_path('routes/auth.php'));

/*
|--------------------------------------------------------------------------
| Extra web route files
|--------------------------------------------------------------------------
*/
Route::middleware('web')
  ->group(base_path('routes/web-redirects.php'));

/*
|--------------------------------------------------------------------------
| Sandbox routes
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'sandbox', 'basic.auth'])
  ->prefix('sandbox')
  ->name('sandbox.')
  ->group(base_path('routes/sandbox.php'));

/*
|--------------------------------------------------------------------------
| Toolbox routes
|--------------------------------------------------------------------------
*/
Route::middleware(['web'])
  ->prefix('toolbox')
  ->name('toolbox.')
  ->group(base_path('routes/toolbox.php'));


