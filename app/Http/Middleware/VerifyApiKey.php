<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey {
  public function handle(
    Request $request,
    Closure $next,
    string $configKey = 'api_secrets.api_default',
    string $headerName = 'X-API-KEY'
  ): Response {
    $provided = (string) $request->header($headerName);
    $expected = (string) config($configKey);

    if ($provided === '' || $expected === '' || ! hash_equals($expected, $provided)) {
      return response()->json([
        'error'   => 'invalid-api-key',
        'message' => 'Invalid API key',
      ], 401);
    }

    return $next($request);
  }
}
