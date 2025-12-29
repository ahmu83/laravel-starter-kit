<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HasWpCapability {
    /**
     * Require that the current user has at least one of the given WordPress capabilities.
     *
     * Supports multiple capabilities with OR logic (user needs ANY of the specified capabilities).
     *
     * Usage:
     *
     *   Single capability:
     *   ->middleware('has.wp.capability:manage_options')
     *
     *   Multiple capabilities (OR logic - user needs ANY):
     *   ->middleware('has.wp.capability:manage_options|edit_posts')
     *   ->middleware('has.wp.capability:edit_posts|publish_posts|delete_posts')
     *
     *   Comma separator also supported (but pipe is preferred):
     *   ->middleware('has.wp.capability:manage_options,edit_posts')
     *
     * Examples:
     *   'has.wp.capability:manage_options'                    → Must have manage_options
     *   'has.wp.capability:manage_options|edit_posts'         → Must have manage_options OR edit_posts (recommended)
     *   'has.wp.capability:edit_posts|publish_posts'          → Must have edit_posts OR publish_posts
     *   'has.wp.capability:edit_posts|delete_posts|moderate_comments' → Must have ANY of these
     *
     * Note: Pipe separator (|) is preferred for clarity as it visually represents OR logic,
     *       following Laravel's convention for validation rules and route constraints.
     */
    public function handle(Request $request, Closure $next, string ...$capabilities) {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'hasWpCapability')) {
            abort(403, 'Unauthorized.');
        }

        // If no capabilities provided, deny access
        if (empty($capabilities)) {
            abort(403, 'Unauthorized - No capability specified.');
        }

        // Support both comma and pipe separators
        // 'manage_options|edit_posts' or 'manage_options,edit_posts'
        $allCapabilities = [];
        foreach ($capabilities as $capabilityString) {
            // Split by comma first
            $parts = explode(',', $capabilityString);
            foreach ($parts as $part) {
                // Then split by pipe
                $subParts = explode('|', $part);
                foreach ($subParts as $subPart) {
                    $trimmed = trim($subPart);
                    if ($trimmed !== '') {
                        $allCapabilities[] = $trimmed;
                    }
                }
            }
        }

        // Remove duplicates
        $allCapabilities = array_unique($allCapabilities);

        if (empty($allCapabilities)) {
            abort(403, 'Unauthorized - No valid capabilities specified.');
        }

        // Check if user has ANY of the specified capabilities (OR logic)
        foreach ($allCapabilities as $capability) {
            if ($user->hasWpCapability($capability)) {
                return $next($request);
            }
        }

        // User doesn't have any of the required capabilities
        $capabilitiesString = implode(', ', $allCapabilities);
        abort(403, "Unauthorized - Missing required capability. Need one of: {$capabilitiesString}");
    }
}
