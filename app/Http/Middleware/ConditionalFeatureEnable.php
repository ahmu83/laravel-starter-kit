<?php

namespace App\Http\Middleware;

use App\Services\FeatureGate;
use Closure;
use Illuminate\Http\Request;

class ConditionalFeatureEnable
{
  public function handle(Request $request, Closure $next)
  {
    $gate = app(FeatureGate::class);

    $this->maybeDisableDebugbar($request, $gate);
    $this->maybeDisableAppDebug($request, $gate);

    return $next($request);
  }

  protected function maybeDisableDebugbar(Request $request, FeatureGate $gate): void
  {
    if (! app()->bound('debugbar')) {
      return;
    }

    if (! config('features.debugbar.enabled')) {
      app('debugbar')->disable();
      return;
    }

    if (! $gate->allowed($request, (string) config('features.debugbar.enable_method'))) {
      app('debugbar')->disable();
    }
  }

  protected function maybeDisableAppDebug(Request $request, FeatureGate $gate): void
  {
    if (! config('app.debug')) {
      return;
    }

    if (! $gate->allowed($request, (string) config('features.app_debug.enable_method'))) {
      config()->set('app.debug', false);
    }
  }
}
