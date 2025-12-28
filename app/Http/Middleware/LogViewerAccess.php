<?php

namespace App\Http\Middleware;

use App\Services\FeatureGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogViewerAccess
{
  /**
   * Restrict access to Log Viewer routes.
   *
   * Override Behavior:
   * - If enable_method is set → It OVERRIDES the base enabled config
   * - If enable_method is empty/none → Respect base enabled config
   *
   * This allows you to do:
   *   LOG_VIEWER_ENABLED=false
   *   LOG_VIEWER_ENABLE_METHOD=ip:strict
   *   Result: Log Viewer is enabled ONLY for allowed IPs (override)
   */
  public function handle(Request $request, Closure $next): Response
  {
    $baseEnabled = (bool) config('features.log_viewer.enabled');
    $enableMethod = (string) config('features.log_viewer.enable_method');

    // Check if override is active (enable_method is set)
    if ($this->hasOverride($enableMethod)) {
      // Override mode: enable_method decides access
      $allowed = app(FeatureGate::class)->allowed($request, $enableMethod);

      if (! $allowed) {
        abort(403);
      }

      return $next($request);
    }

    // No override: respect base enabled config
    if (! $baseEnabled) {
      abort(404);
    }

    return $next($request);
  }

  /**
   * Check if enable_method is acting as an override.
   *
   * Empty or "none" means no override (use base config).
   * Any other value means override is active.
   */
  protected function hasOverride(string $enableMethod): bool
  {
    $normalized = strtolower(trim($enableMethod));
    return $normalized !== '' && $normalized !== 'none';
  }
}
