<?php

namespace App\Http\Middleware;

use App\Services\FeatureGate;
use Closure;
use Illuminate\Http\Request;

class VantageAccess
{
  /**
   * Restrict access to Vantage (queue monitoring) routes.
   *
   * Uses the unified feature gate system for consistent access control.
   *
   * Behavior:
   * 1. Check if Vantage is enabled (hard toggle)
   * 2. Apply request-level gating via enable_method config
   *
   * Configuration:
   * - config/features.php: vantage.enabled, vantage.enable_method
   * - .env: VANTAGE_ENABLED, VANTAGE_ENABLE_METHOD
   *
   * Enable method options:
   * - empty/none: allow all
   * - deny_all: block everyone
   * - ip:strict: require exact IP match
   * - ip:class: require CIDR/class IP match
   * - auth: require any authenticated user
   * - auth:admin: require WordPress admin
   * - Comma-separated for AND logic: ip:class,auth:admin
   */
  public function handle(Request $request, Closure $next)
  {
    // Hard toggle - if disabled, return 404 to hide existence
    if (! config('features.vantage.enabled')) {
      abort(404);
    }

    // Request-level gating via FeatureGate service
    $allowed = app(FeatureGate::class)->allowed(
      $request,
      (string) config('features.vantage.enable_method')
    );

    if (! $allowed) {
      abort(404);
    }

    return $next($request);
  }
}
