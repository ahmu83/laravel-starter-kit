<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BasicAuth
{
  /**
   * HTTP Basic Authentication middleware.
   *
   * This middleware protects routes using HTTP Basic Auth credentials
   * defined in environment/config.
   *
   * Intended use cases:
   * - Sandbox routes
   * - Internal tools
   * - Temporary admin endpoints
   *
   * Behavior:
   * - Skips authentication entirely in local environment
   * - Fails closed if credentials are not configured
   * - Returns a Basic Auth challenge on invalid credentials
   */
  public function handle(Request $request, Closure $next): Response
  {
    // Skip Basic Auth entirely in local environment
    // (useful for local development and DX)
    if (app()->environment('local')) {
      return $next($request);
    }

    $user = config('basic_auth.user');
    $pass = config('basic_auth.pass');

    // Fail closed if credentials are missing
    if (! $user || ! $pass) {
      abort(403, 'Basic auth credentials are not configured.');
    }

    // Validate HTTP Basic Auth credentials
    if (
      $request->getUser() !== $user ||
      $request->getPassword() !== $pass
    ) {
      return response('Unauthorized', 401, [
        'WWW-Authenticate' => 'Basic',
      ]);
    }

    return $next($request);
  }
}
