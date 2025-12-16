<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SandboxAccess
{
  /**
   * Restrict access to sandbox routes.
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
    // Allow unrestricted access in local environment
    if (app()->environment('local')) {
      return $next($request);
    }

    if (! auth()->check()) {
      abort(403, 'You must be logged in to access sandbox routes.');
    }

    $allowed = config('sandbox.allowed_emails', []);
    $email   = (string) auth()->user()->email;

    if (empty($allowed)) {
      abort(403, 'Sandbox allowed emails are not configured.');
    }

    if (! in_array($email, $allowed, true)) {
      abort(403, 'You are not allowed to access sandbox routes.');
    }

    return $next($request);
  }
}
