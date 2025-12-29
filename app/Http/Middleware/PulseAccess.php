<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\FeatureGate;

class PulseAccess {
    /**
     * Restrict access to Pulse routes.
     *
     * Override Behavior:
     * - If enable_method is set → It IS the source of truth (ignores base enabled)
     * - If enable_method is empty/none → Respect base enabled config
     *
     * Examples:
     *   PULSE_ENABLED=false
     *   PULSE_ENABLE_METHOD=ip:strict
     *   Result: Pulse is enabled ONLY for allowed IPs ✅ (base ENABLED ignored)
     *
     *   PULSE_ENABLED=true
     *   PULSE_ENABLE_METHOD=deny_all
     *   Result: Pulse is disabled for everyone ✅ (base ENABLED ignored)
     */
    public function handle(Request $request, Closure $next) {
        $enableMethod = (string) config('features.pulse.enable_method');

        // Check if override is active (enable_method is set)
        if ($this->hasOverride($enableMethod)) {
            // Override mode: enable_method IS the source of truth
            $allowed = app(FeatureGate::class)->allowed($request, $enableMethod);

            if (! $allowed) {
                abort(404);
            }

            return $next($request);
        }

        // No override: respect base enabled config
        $baseEnabled = (bool) config('features.pulse.enabled');

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
    protected function hasOverride(string $enableMethod): bool {
        $normalized = strtolower(trim($enableMethod));

        return $normalized !== '' && $normalized !== 'none';
    }
}
