<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SandboxMiddleware
{
  /**
   * Sandbox access control middleware.
   *
   * This middleware restricts access to sandbox routes based on:
   * - Authentication status
   * - An explicit allowlist of email addresses
   *
   * This middleware is intentionally "fail safe":
   * - Not logged in      -> access denied
   * - Allowlist missing -> access denied
   * - Email not allowed -> access denied
   *
   * The goal is to prevent accidental exposure of internal
   * sandbox or testing routes.
   */
  public function handle(Request $request, Closure $next): Response
  {
    // Optional: hide sandbox routes entirely outside local/dev
    // Uncomment if you want sandbox routes to 404 in non-dev environments
    //
    // if (! app()->environment(['local', 'development'])) {
    //   abort(404);
    // }

    if (app()->environment('local')) {
      return $next($request);
    }

    // Require an authenticated user
    if (! auth()->check()) {
      abort(403, 'You must be logged in to access sandbox routes.');
    }

    // Allowed email allowlist (comma-separated via env)
    $allowed = config('sandbox.allowed_emails', []);
    $email = (string) auth()->user()->email;

    // Fail closed if allowlist is not configured
    if (empty($allowed)) {
      abort(403, 'Sandbox allowed emails are not configured.');
    }

    // Deny access if user email is not explicitly allowed
    if (! in_array($email, $allowed, true)) {
      abort(403, 'You are not allowed to access sandbox routes.');
    }

    return $next($request);
  }
}
