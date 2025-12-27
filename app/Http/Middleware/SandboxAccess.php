<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SandboxAccess extends HasWpRole
{
  /**
   * Restrict access to sandbox routes.
   *
   * Enforcement order:
   * 1. Local environment bypass (optional)
   * 2. Authenticated user required
   * 3. WordPress role check (explicit roles required)
   * 4. Email allowlist check (sandbox.allowed_emails)
   *
   * IMPORTANT:
   * - At least one role MUST be provided.
   * - If no role is passed, access is denied (fail closed).
   */
  public function handle(Request $request, Closure $next, string ...$roles): Response
  {
    // Optional: allow unrestricted access locally
    if (app()->environment('local')) {
      return $next($request);
    }

    if (! auth()->check()) {
      abort(403, 'Authentication required.');
    }

    // 1) Enforce WordPress role(s)
    parent::handle($request, function () {
      // no-op; role enforcement happens inside HasWpRole
    }, ...$roles);

    // 2) Enforce email allowlist
    $allowedEmails = config('sandbox.allowed_emails', []);
    $email = (string) auth()->user()->email;

    if (empty($allowedEmails)) {
      abort(403, 'Sandbox allowed emails are not configured.');
    }

    if (! in_array($email, $allowedEmails, true)) {
      abort(403, 'You are not allowed to access sandbox routes.');
    }

    return $next($request);
  }
}
