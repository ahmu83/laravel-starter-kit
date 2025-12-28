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
   * Behavior:
   * - If enable_method is set, it OVERRIDES the base enabled config
   * - Feature is enabled/disabled based on gate check (IP, auth, etc.)
   * - If enable_method is empty/none, base config wins
   *
   * Examples:
   *
   * Example 1: Override disabled feature
   *   APP_DEBUG=false
   *   APP_DEBUG_ENABLE_METHOD=ip:strict
   *   Result: APP_DEBUG becomes true for allowed IPs, false for others
   *
   * Example 2: Override enabled feature
   *   DEBUGBAR_ENABLED=true
   *   DEBUGBAR_ENABLE_METHOD=auth:admin
   *   Result: Debugbar enabled only for admins, disabled for others
   *
   * Example 3: No override (respect base config)
   *   APP_DEBUG=true
   *   APP_DEBUG_ENABLE_METHOD=none
   *   Result: APP_DEBUG stays true for everyone
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
   * Behavior:
   * - If enable_method is set → Override base config (enable or disable)
   * - If enable_method is empty/none → Respect base config
   * - If base enabled=false and no enable_method → Disabled
   */
  protected function maybeToggleDebugbar(Request $request, FeatureGate $gate): void
  {
    if (! app()->bound('debugbar')) {
      return;
    }

    $baseEnabled = (bool) config('features.debugbar.enabled');
    $enableMethod = (string) config('features.debugbar.enable_method');

    // If enable_method is set, it OVERRIDES base config
    if ($this->hasEnableMethod($enableMethod)) {
      $allowed = $gate->allowed($request, $enableMethod);

      if ($allowed) {
        app('debugbar')->enable();
      } else {
        app('debugbar')->disable();
      }

      return;
    }

    // No enable_method → respect base config
    if (! $baseEnabled) {
      app('debugbar')->disable();
    }
  }

  /**
   * Conditionally enable/disable APP_DEBUG based on enable_method.
   *
   * Behavior:
   * - If enable_method is set → Override APP_DEBUG (enable or disable)
   * - If enable_method is empty/none → Respect APP_DEBUG
   * - If APP_DEBUG=false and no enable_method → Disabled
   *
   * Critical: This runs per-request, so APP_DEBUG becomes dynamic!
   */
  protected function maybeToggleAppDebug(Request $request, FeatureGate $gate): void
  {
    $baseEnabled = (bool) config('app.debug');
    $enableMethod = (string) config('features.app_debug.enable_method');

    // If enable_method is set, it OVERRIDES APP_DEBUG
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
