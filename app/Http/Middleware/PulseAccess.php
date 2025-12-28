<?php

namespace App\Http\Middleware;

use App\Services\FeatureGate;
use Closure;
use Illuminate\Http\Request;

class PulseAccess
{
  public function handle(Request $request, Closure $next)
  {
    if (! config('features.pulse.enabled')) {
      abort(404);
    }

    $allowed = app(FeatureGate::class)->allowed(
      $request,
      (string) config('features.pulse.enable_method')
    );

    if (! $allowed) {
      abort(404);
    }

    return $next($request);
  }
}
