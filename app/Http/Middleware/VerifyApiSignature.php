<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiSignature {
  public function handle(
    Request $request,
    Closure $next,
    string $secretConfigKey = 'api_keys.default',
    string $headerName = 'X-API-Signature'
  ): Response {
    return app(VerifyHmacSignature::class)
      ->handle($request, $next, $secretConfigKey, $headerName);
  }
}
