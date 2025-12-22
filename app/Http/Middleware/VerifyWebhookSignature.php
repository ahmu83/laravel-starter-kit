<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature {
  public function handle(
    Request $request,
    Closure $next,
    string $secretConfigKey = 'api_keys.webhook_default',
    string $headerName = 'X-Webhook-Signature'
  ): Response {
    log_info('VerifyWebhookSignature@handle', ['$secretConfigKey' => $secretConfigKey]);
    return app(VerifyHmacSignature::class)
      ->handle($request, $next, $secretConfigKey, $headerName);
  }
}
