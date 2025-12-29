<?php

namespace App\Http\Middleware;

use App\Services\FeatureGate;
use Closure;
use Illuminate\Http\Request;

class ConditionalFeatureEnable
{
  /**
   * Conditionally enable/disable features based on request-level gates.
   *
   * This middleware provides dynamic per-request feature toggling.
   *
   * Override Behavior:
   * - If enable_method is set → It IS the source of truth (ignores base enabled)
   * - If enable_method is empty/none → Respect base enabled config
   *
   * Examples:
   *
   * Example 1: Override disabled feature
   *   APP_DEBUG=false
   *   APP_DEBUG_ENABLE_METHOD=ip:strict
   *   Result: APP_DEBUG true for allowed IPs, false for others ✅
   *
   * Example 2: Override enabled feature
   *   DEBUGBAR_ENABLED=true
   *   DEBUGBAR_ENABLE_METHOD=deny_all
   *   Result: Debugbar disabled for everyone ✅
   *
   * Example 3: No override (respect base config)
   *   APP_DEBUG=true
   *   APP_DEBUG_ENABLE_METHOD=none
   *   Result: APP_DEBUG stays true for everyone ✅
   */
  public function handle(Request $request, Closure $next)
  {
    $gate = app(FeatureGate::class);

    $this->maybeToggleDebugbar($request, $gate);
    $this->maybeToggleAppDebug($request, $gate);

    return $next($request);
  }

  /**
   * Conditionally enable/disable Debugbar based on enable_method.
   *
   * NEW BEHAVIOR:
   * - If enable_method is set → It IS the source of truth
   * - If enable_method is empty/none → Respect base config
   *
   * CRITICAL: Also sets debugbar.enabled config to override internal checks
   */
  protected function maybeToggleDebugbar(Request $request, FeatureGate $gate): void
  {
    if (! app()->bound('debugbar')) {
      return;
    }

    $enableMethod = (string) config('features.debugbar.enable_method');

    // If enable_method is set, it IS the source of truth
    if ($this->hasEnableMethod($enableMethod)) {
      $allowed = $gate->allowed($request, $enableMethod);

      if ($allowed) {
        // Force enable debugbar (overrides APP_DEBUG and DEBUGBAR_ENABLED)
        config()->set('debugbar.enabled', true);
        app('debugbar')->enable();
      } else {
        // Force disable debugbar
        config()->set('debugbar.enabled', false);
        app('debugbar')->disable();
      }

      return;
    }

    // No enable_method → respect base config
    $baseEnabled = (bool) config('features.debugbar.enabled');

    if (! $baseEnabled) {
      config()->set('debugbar.enabled', false);
      app('debugbar')->disable();
    }
  }

  /**
   * Conditionally enable/disable APP_DEBUG based on enable_method.
   *
   * NEW BEHAVIOR:
   * - If enable_method is set → It IS the source of truth
   * - If enable_method is empty/none → Respect APP_DEBUG
   */
  protected function maybeToggleAppDebug(Request $request, FeatureGate $gate): void
  {
    $enableMethod = (string) config('features.app_debug.enable_method');

    // If enable_method is set, it IS the source of truth
    if ($this->hasEnableMethod($enableMethod)) {
      $allowed = $gate->allowed($request, $enableMethod);

      if ($allowed) {
        config()->set('app.debug', true);
      } else {
        config()->set('app.debug', false);
      }

      return;
    }

    // No enable_method → respect base APP_DEBUG
    // (nothing to do, config already has the right value)
  }

  /**
   * Check if an enable_method is actually set (not empty or "none").
   *
   * Empty string or "none" means "no override, use base config".
   * Any other value means "override base config with this gate".
   */
  protected function hasEnableMethod(string $enableMethod): bool
  {
    $normalized = strtolower(trim($enableMethod));

    // Empty or "none" means no override
    if ($normalized === '' || $normalized === 'none') {
      return false;
    }

    return true;
  }
}
