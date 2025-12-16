<?php

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rate limiting api routes
|--------------------------------------------------------------------------
*/
RateLimiter::for('api', function (Request $request) {
  return Limit::perMinute(60)->by(
    $request->user()?->id ?: $request->ip()
  );
});

/*
|--------------------------------------------------------------------------
| Route files
|--------------------------------------------------------------------------
| Each file is responsible for its own prefix/name/middleware grouping.
|--------------------------------------------------------------------------
*/
require base_path('routes/web-redirects.php');
require base_path('routes/auth.php');
require base_path('routes/toolbox.php');
require base_path('routes/sandbox.php');

