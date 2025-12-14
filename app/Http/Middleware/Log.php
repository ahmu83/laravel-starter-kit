<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class Log
{
  /**
   * Attach a unique identifier to each request for logging and tracing.
   *
   * This middleware generates a UUID for every incoming request and:
   * - stores it as a request attribute (safe, does not affect input/validation)
   * - adds it to the response headers for easier debugging
   *
   * This makes it easier to correlate:
   * - application logs
   * - error reports
   * - client-side bug reports
   *
   * IMPORTANT:
   * We intentionally do NOT merge this value into request input,
   * as that can interfere with validation, old input, or persistence.
   */
  public function handle(Request $request, Closure $next): Response
  {
    $uuid = (string) Str::uuid();

    // Store on the request (safe, internal use only)
    $request->attributes->set('log_uuid', $uuid);

    $response = $next($request);

    // Expose the ID in the response for debugging/tracing
    $response->headers->set('X-Log-UUID', $uuid);

    return $response;
  }
}
