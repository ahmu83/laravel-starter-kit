<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAuth {
  /**
   * API Key Authentication Middleware
   *
   * Validates incoming API requests using X-API-KEY header.
   *
   * Configuration: config/api_auth.php
   *
   * Usage:
   *   Route::middleware(['api.auth'])->group(function () {
   *       Route::get('/users', [ApiController::class, 'index']);
   *   });
   */
  public function handle(Request $request, Closure $next): Response {
    $headerName = config('api_auth.header', 'X-API-KEY');

    // Check if API key header exists
    if (! $request->hasHeader($headerName)) {
      return response()->json([
        'error'   => 'missing-auth',
        'message' => "{$headerName} header is required",
      ], 401);
    }

    $apiKey = $request->header($headerName);

    // Check if key is empty
    if (empty($apiKey)) {
      return response()->json([
        'error'   => 'invalid-key',
        'message' => "{$headerName} cannot be empty",
      ], 401);
    }

    // Get valid API keys from config
    $validKeys = config('api_auth.keys', []);

    // Check if any valid keys are configured
    if (empty($validKeys)) {
      return response()->json([
        'error'   => 'server-error',
        'message' => 'API authentication is not properly configured',
      ], 500);
    }

    // Validate the provided key
    if (! in_array($apiKey, $validKeys, true)) {
      return response()->json([
        'error'   => 'invalid-key',
        'message' => 'Invalid API key',
      ], 401);
    }

    return $next($request);
  }
}
