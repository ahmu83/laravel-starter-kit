<?php
namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware {
  /**
   * URIs that should be excluded from CSRF verification.
   *
   * CSRF protection is designed for browser-based requests that
   * rely on cookies and sessions. Webhook requests are server-to-server
   * calls and cannot send CSRF tokens.
   *
   * Excluding `webhooks/*` allows external services (Stripe, Paddle,
   * GitHub, etc.) to successfully POST webhook payloads without
   * triggering a 419 CSRF error.
   *
   * Webhook endpoints MUST still implement their own verification
   * (signatures, shared secrets, or IP allowlists).
   *
   * @var array<int, string>
   */
  protected $except = [
    'webhooks/*',
  ];
}
