<?php

namespace App\Http\Middleware;

use App\Services\IpAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IpAccess
{
  /**
   * IP address access control middleware.
   *
   * Modes:
   * - strict (default)
   *   - exact IP match (normalized via inet_pton/inet_ntop)
   * - class
   *   - IPv4: compares the first 3 octets (A.B.C.*)
   *   - IPv6: compares the first 4 hextets (roughly a /64 “class”)
   *
   * IP version filtering:
   * - auto (default): checks both v4 and v6 allowlists
   * - v4: only checks IPv4 allowlist
   * - v6: only checks IPv6 allowlist
   *
   * Allowlist sources:
   * - If $allowedV4 / $allowedV6 args are provided, they override config.
   * - Otherwise uses config, based on mode:
   *   - strict => allowed_ipv4 / allowed_ipv6
   *   - class  => allowed_ipv4_class / allowed_ipv6_class
   *
   * Inline allowlist format (when using args):
   * - IPv4 entries are separated by "|" (pipe)
   * - IPv6 entries are separated by "|" (pipe)
   * - CIDR is supported for both IPv4 and IPv6 (e.g. 10.0.0.0/8, 2001:db8::/32)
   *
   * Usage examples:
   *
   * // Default: strict + auto (uses config strict allowlists)
   * Route::middleware('ip.access')->get('/toolbox', fn () => 'ok');
   *
   * // Strict exact match for both v4/v6 (same as default)
   * Route::middleware('ip.access:strict')->get('/admin', fn () => 'ok');
   *
   * // Strict IPv4 only
   * Route::middleware('ip.access:strict,v4')->get('/admin', fn () => 'ok');
   *
   * // Strict IPv6 only
   * Route::middleware('ip.access:strict,v6')->get('/admin-v6', fn () => 'ok');
   *
   * // Class mode (uses class allowlists from config)
   * Route::middleware('ip.access:class')->get('/internal', fn () => 'ok');
   *
   * // Inline allowlists (class mode)
   * // - IPv4: 1.2.3.4 means allow 1.2.3.* in class mode
   * // - IPv6: 2001:db8::1 means allow anything matching first 4 hextets in class mode
   * Route::middleware('ip.access:class,auto,1.2.3.4|5.6.7.8,2001:db8::1')
   *   ->get('/internal-inline', fn () => 'ok');
   *
   * // Inline allowlists (strict mode)
   * Route::middleware('ip.access:strict,auto,162.197.8.160,2001:4860:7:110e::d5')
   *   ->get('/locked', fn () => 'ok');
   *
   * // CIDR allowlists (strict + auto)
   * Route::middleware('ip.access:strict,auto,162.197.8.0/24,2001:4860:7:110e::/64')
   *   ->get('/office', fn () => 'ok');
   *
   * // Grouping routes with a shared policy
   * Route::middleware('ip.access:class,auto,162.197.8.160,2001:4860:7:110e::d5')->group(function () {
   *   Route::get('/sandbox', fn () => 'ok');
   *   Route::get('/sandbox/logs', fn () => 'ok');
   * });
   */
  public function handle(
    Request $request,
    Closure $next,
    string $mode = 'strict',
    string $ipVersion = 'auto',
    ?string $allowedV4 = null,
    ?string $allowedV6 = null
  ): Response {
    $clientIp = (string) $request->ip();

    if ($clientIp === '') {
      abort(403, 'IP not detected.');
    }

    $ok = app(IpAccessService::class)->isAllowed(
      $clientIp,
      $mode,
      $ipVersion,
      $allowedV4,
      $allowedV6
    );

    if (!$ok) {
      abort(403, 'Access denied.');
    }

    return $next($request);
  }
}
