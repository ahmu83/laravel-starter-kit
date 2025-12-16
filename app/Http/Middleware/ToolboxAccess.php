<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ToolboxAccess
{
  /**
   * Restrict access to toolbox/internal tooling routes.
   */
  public function handle(Request $request, Closure $next): Response
  {
    // Allow unrestricted access in local environment
    if (app()->environment('local')) {
      return $next($request);
    }

    if (! auth()->check()) {
      abort(403, 'You must be logged in to access toolbox routes.');
    }

    $allowed = config('toolbox.allowed_emails', []);
    $email   = (string) auth()->user()->email;

    if (empty($allowed)) {
      abort(403, 'Toolbox allowed emails are not configured.');
    }

    if (! in_array($email, $allowed, true)) {
      abort(403, 'You are not allowed to access toolbox routes.');
    }

    return $next($request);
  }
}
