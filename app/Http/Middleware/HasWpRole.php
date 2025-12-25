<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HasWpRole
{
  /**
   * Require that the current user has at least one of the given WordPress roles.
   *
   * Supports multiple roles with OR logic (user needs ANY of the specified roles).
   *
   * Usage:
   *
   *   Single role:
   *   ->middleware('has.wp.role:administrator')
   *
   *   Multiple roles (OR logic - user needs ANY):
   *   ->middleware('has.wp.role:administrator|editor')
   *   ->middleware('has.wp.role:administrator|editor|author')
   *
   *   Comma separator also supported (but pipe is preferred):
   *   ->middleware('has.wp.role:administrator,editor')
   *
   * Examples:
   *   'has.wp.role:administrator'                → Must be admin
   *   'has.wp.role:administrator|editor'         → Must be admin OR editor (recommended)
   *   'has.wp.role:subscriber|contributor'       → Must be subscriber OR contributor
   *   'has.wp.role:admin|editor|author'          → Must be ANY of these three
   *
   * Note: Pipe separator (|) is preferred for clarity as it visually represents OR logic,
   *       following Laravel's convention for validation rules and route constraints.
   */
  public function handle(Request $request, Closure $next, string ...$roles)
  {
    $user = $request->user();

    if (!$user || !method_exists($user, 'hasWpRole')) {
      abort(403, 'Unauthorized.');
    }

    // If no roles provided, deny access
    if (empty($roles)) {
      abort(403, 'Unauthorized - No role specified.');
    }

    // Support both comma and pipe separators
    // 'administrator,editor' or 'administrator|editor'
    $allRoles = [];
    foreach ($roles as $roleString) {
      // Split by comma first
      $parts = explode(',', $roleString);
      foreach ($parts as $part) {
        // Then split by pipe
        $subParts = explode('|', $part);
        foreach ($subParts as $subPart) {
          $trimmed = trim($subPart);
          if ($trimmed !== '') {
            $allRoles[] = $trimmed;
          }
        }
      }
    }

    // Remove duplicates
    $allRoles = array_unique($allRoles);

    if (empty($allRoles)) {
      abort(403, 'Unauthorized - No valid roles specified.');
    }

    // Check if user has ANY of the specified roles (OR logic)
    foreach ($allRoles as $role) {
      if ($user->hasWpRole($role)) {
        return $next($request);
      }
    }

    // User doesn't have any of the required roles
    $rolesString = implode(', ', $allRoles);
    abort(403, "Unauthorized - Missing required role. Need one of: {$rolesString}");
  }
}
