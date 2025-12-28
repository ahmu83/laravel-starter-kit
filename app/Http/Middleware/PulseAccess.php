<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PulseAccess
{
  public function handle(Request $request, Closure $next)
  {
    if (! filter_var(env('PULSE_ENABLED', false), FILTER_VALIDATE_BOOLEAN)) {
      abort(404);
    }

    // Reuse your ConditionalFeatureEnable logic via env methods
    // Easiest: copy the isFeatureAllowed() method into a small shared service
    // For now: quick inline call by using the same parsing logic

    $raw = (string) env('PULSE_ENABLE_METHOD', '');
    $allowed = app(\App\Services\FeatureGate::class)->allowed($request, $raw);

    if (! $allowed) {
      abort(404);
    }

    return $next($request);
  }
}
