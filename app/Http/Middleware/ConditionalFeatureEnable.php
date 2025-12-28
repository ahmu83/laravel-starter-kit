<?php

namespace App\Http\Middleware;

use App\Services\FeatureGate;
use Closure;
use Illuminate\Http\Request;

class ConditionalFeatureEnable
{
  public function handle(Request $request, Closure $next)
  {
    $this->maybeDisableDebugbar($request);
    $this->maybeDisableAppDebug($request);

    return $next($request);
  }

  protected function maybeDisableDebugbar(Request $request): void
  {
    if (! app()->bound('debugbar')) {
      return;
    }

    if (! filter_var(env('DEBUGBAR_ENABLED', false), FILTER_VALIDATE_BOOLEAN)) {
      app('debugbar')->disable();
      return;
    }

    $allowed = app(FeatureGate::class)->allowed(
      $request,
      (string) env('DEBUGBAR_ENABLE_METHOD', '')
    );

    if (! $allowed) {
      app('debugbar')->disable();
    }
  }

  protected function maybeDisableAppDebug(Request $request): void
  {
    // Global off => do nothing
    if (! config('app.debug')) {
      return;
    }

    $allowed = app(FeatureGate::class)->allowed(
      $request,
      (string) env('APP_DEBUG_ENABLE_METHOD', '')
    );

    if (! $allowed) {
      config()->set('app.debug', false);
    }
  }
}
