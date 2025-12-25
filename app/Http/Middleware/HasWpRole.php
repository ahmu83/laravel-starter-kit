<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HasWpRole
{
  /**
   * Require that the current user has a given WordPress role.
   *
   * Mirrors WordPress semantics:
   * - One role check per call (like user_can with a role-like cap)
   *
   * Usage:
   *   ->middleware('has.wp.role:administrator')
   *   ->middleware('has.wp.role:editor')
   *
   * If you need "admin OR editor", apply the middleware twice
   * or handle it explicitly in your own logic.
   */
  public function handle(Request $request, Closure $next, string $role)
  {
    $user = $request->user();

    if (! $user || ! method_exists($user, 'hasWpRole')) {
      abort(403, 'Unauthorized.');
    }

    $role = trim($role);

    if ($role === '') {
      abort(403, 'Unauthorized - No role specified.');
    }

    if (! $user->hasWpRole($role)) {
      abort(403, "Unauthorized - Missing role: {$role}");
    }

    return $next($request);
  }
}
