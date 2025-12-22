<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * VerifyHmacSignature
 *
 * Generic HMAC-based request authentication middleware.
 *
 * This middleware validates incoming requests by verifying an HMAC-SHA256
 * signature generated from the raw request body and a shared secret.
 *
 * How it works:
 * - The sender computes an HMAC-SHA256 of the raw request body
 *   using a shared secret.
 * - The computed signature is sent in a request header.
 * - This middleware recomputes the expected signature and compares it
 *   using a timing-safe comparison.
 *
 * Configuration:
 * - Secrets are stored in config/api_secrets.php
 * - The middleware accepts a config key pointing to the secret:
 *
 *     webhook.auth:api_secrets.webhook_wp_event
 *
 * - The signature header name can also be overridden:
 *
 *     webhook.auth:api_secrets.webhook_wp_event,X-Webhook-Signature
 *
 * Defaults:
 * - Secret config key: api_secrets.default
 * - Header name: X-API-Signature
 *
 * Supported features:
 * - Optional "sha256=" prefix in the signature header
 * - Route-level overrides of secrets and header names
 * - Safe for use with webhooks and signed internal APIs
 *
 * This middleware does NOT:
 * - Authenticate users
 * - Authorize roles or permissions
 * - Perform replay protection (timestamps, nonces, expiration)
 */
class VerifyHmacSignature {

  public function handle(
    Request $request,
    Closure $next,
    string $secretConfigKey = 'api_secrets.default',
    string $headerName = 'X-API-Signature'
  ): Response {
    if (! $this->verifySignature($request, $secretConfigKey, $headerName)) {
      return response()->json([
        'error'   => 'invalid-signature',
        'message' => 'Invalid signature',
      ], 401);
    }

    return $next($request);
  }

  private function verifySignature(Request $request, string $secretConfigKey, string $headerName): bool {
    $signature = trim((string) $request->header($headerName));
    $signature = preg_replace('/^sha256=/i', '', $signature);

    $secret = (string) config($secretConfigKey);

    log_info('VerifyHmacSignature@verifySignature', [
      '$request->header($headerName)' => $request->header($headerName),
      '$signature' => $signature,
      '$secret' => $secret,
      '$secretConfigKey' => $secretConfigKey,
      '$headerName' => $headerName,
    ]);

    if ($secret === '' || $signature === '') {
      return false;
    }

    $payload  = $request->getContent();
    $expected = hash_hmac('sha256', $payload, $secret);

    return hash_equals($expected, $signature);
  }
}
