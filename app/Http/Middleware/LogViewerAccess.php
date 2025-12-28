<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogViewerAccess
{
  public function handle(Request $request, Closure $next): Response
  {
    // Global hard toggle
    if (! filter_var(env('LOG_VIEWER_ENABLED', false), FILTER_VALIDATE_BOOLEAN)) {
      abort(404);
    }

    /** @var \App\Http\Middleware\ConditionalFeatureEnable $gate */
    $gate = app(ConditionalFeatureEnable::class);

    $allowed = $gate->isFeatureAllowed(
      $request,
      (string) env('LOG_VIEWER_ENABLE_METHOD', '')
    );

    if (! $allowed) {
      abort(403);
    }

    return $next($request);
  }
}
