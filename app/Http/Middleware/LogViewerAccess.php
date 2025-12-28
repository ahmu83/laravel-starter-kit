<?php

namespace App\Http\Middleware;

use App\Services\FeatureGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogViewerAccess
{
  public function handle(Request $request, Closure $next): Response
  {
    if (! config('features.log_viewer.enabled')) {
      abort(404);
    }

    $allowed = app(FeatureGate::class)->allowed(
      $request,
      (string) config('features.log_viewer.enable_method')
    );

    if (! $allowed) {
      abort(403);
    }

    return $next($request);
  }
}
