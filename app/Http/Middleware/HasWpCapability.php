<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HasWpCapability
{
  /**
   * Require that the current user has a given WordPress capability.
   *
   * Mirrors user_can( $user_id, 'capability' ):
   * - One capability per check.
   *
   * Usage:
   *   ->middleware('has.wp.capability:manage_options')
   *   ->middleware('has.wp.capability:edit_posts')
   *
   * If you need multiple caps, either stack middleware
   * or implement the AND/OR logic in your own code.
   */
  public function handle(Request $request, Closure $next, string $capability)
  {
    $user = $request->user();

    if (! $user || ! method_exists($user, 'hasWpCapability')) {
      abort(403, 'Unauthorized.');
    }

    $capability = trim($capability);

    if ($capability === '') {
      abort(403, 'Unauthorized - No capability specified.');
    }

    if (! $user->hasWpCapability($capability)) {
      abort(403, "Unauthorized - Missing capability: {$capability}");
    }

    return $next($request);
  }
}
