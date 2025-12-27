<?php

namespace App\Services;

/**
 * Shared IP allowlist logic used by middleware and gates.
 */
class IpAccessService
{
  /**
   * Check whether a given client IP is allowed.
   *
   * $mode:
   * - strict (default): exact IP match (normalized)
   * - class: IPv4 compares A.B.C.* and IPv6 compares first 4 hextets
   *
   * $ipVersion:
   * - auto (default): checks both v4 and v6 lists
   * - v4: only checks IPv4 list
   * - v6: only checks IPv6 list
   *
   * Inline allowlist format:
   * - IPv4 entries are separated by "|" (pipe)
   * - IPv6 entries are separated by "|" (pipe)
   * - CIDR is supported for both IPv4 and IPv6
   */
  public function isAllowed(
    string $clientIp,
    string $mode = 'strict',
    string $ipVersion = 'auto',
    ?string $allowedV4 = null,
    ?string $allowedV6 = null
  ): bool {
    $clientIp = trim($clientIp);

    if ($clientIp === '') {
      return false;
    }

    $allowed = $this->resolveAllowedIps($ipVersion, $allowedV4, $allowedV6, $mode);

    return $this->checkAllowed($clientIp, $allowed, $mode);
  }

  private function resolveAllowedIps(
    string $ipVersion,
    ?string $allowedV4,
    ?string $allowedV6,
    string $mode = 'strict'
  ): array {
    // Treat empty strings as "not provided"
    $allowedV4 = (is_string($allowedV4) && trim($allowedV4) === '') ? null : $allowedV4;
    $allowedV6 = (is_string($allowedV6) && trim($allowedV6) === '') ? null : $allowedV6;

    // Inline overrides always win
    if ($allowedV4 !== null || $allowedV6 !== null) {
      $v4 = $allowedV4 !== null
        ? array_filter(array_map('trim', explode('|', $allowedV4)))
        : [];

      $v6 = $allowedV6 !== null
        ? array_filter(array_map('trim', explode('|', $allowedV6)))
        : [];
    } else {
      if ($mode === 'class') {
        $v4 = (array) config('ip_access.allowed_ipv4_class', []);
        $v6 = (array) config('ip_access.allowed_ipv6_class', []);
      } else {
        // strict (default) and any unknown mode falls back to strict allowlists
        $v4 = (array) config('ip_access.allowed_ipv4', []);
        $v6 = (array) config('ip_access.allowed_ipv6', []);
      }
    }

    if ($ipVersion === 'v4') {
      return $v4;
    }

    if ($ipVersion === 'v6') {
      return $v6;
    }

    return array_values(array_merge($v4, $v6));
  }

  private function checkAllowed(string $clientIp, array $allowed, string $mode): bool
  {
    foreach ($allowed as $entry) {
      $entry = trim((string) $entry);

      if ($entry === '') {
        continue;
      }

      // CIDR support (both v4 and v6)
      if (str_contains($entry, '/')) {
        if ($this->ipInCidr($clientIp, $entry)) {
          return true;
        }
        continue;
      }

      // Strict: exact normalized IP match
      if ($mode === 'strict') {
        if ($this->normalizeIp($clientIp) === $this->normalizeIp($entry)) {
          return true;
        }
        continue;
      }

      // Class: compare by IPv4 "class" (A.B.C.*) or IPv6 "class" (first 4 hextets)
      if ($mode === 'class') {
        if ($this->isSameIpClass($clientIp, $entry)) {
          return true;
        }
      }
    }

    return false;
  }

  private function isSameIpClass(string $clientIp, string $allowedIp): bool
  {
    $clientIsV4 = filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    $allowedIsV4 = filter_var($allowedIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;

    $clientIsV6 = filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    $allowedIsV6 = filter_var($allowedIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;

    // IPv4: compare first 3 octets (A.B.C.*)
    if ($clientIsV4 && $allowedIsV4) {
      $c = explode('.', $clientIp);
      $a = explode('.', $allowedIp);

      return ($c[0] ?? null) === ($a[0] ?? null)
        && ($c[1] ?? null) === ($a[1] ?? null)
        && ($c[2] ?? null) === ($a[2] ?? null);
    }

    // IPv6: compare first 4 hextets (roughly /64 "class")
    if ($clientIsV6 && $allowedIsV6) {
      $c = $this->expandIpv6ToHextets($clientIp);
      $a = $this->expandIpv6ToHextets($allowedIp);

      return ($c[0] ?? null) === ($a[0] ?? null)
        && ($c[1] ?? null) === ($a[1] ?? null)
        && ($c[2] ?? null) === ($a[2] ?? null)
        && ($c[3] ?? null) === ($a[3] ?? null);
    }

    return false;
  }

  private function normalizeIp(string $ip): string
  {
    $packed = @inet_pton($ip);

    if ($packed === false) {
      return $ip;
    }

    return inet_ntop($packed) ?: $ip;
  }

  private function expandIpv6ToHextets(string $ip): array
  {
    $packed = @inet_pton($ip);

    if ($packed === false || strlen($packed) !== 16) {
      return [];
    }

    $hex = bin2hex($packed);
    $out = [];

    for ($i = 0; $i < 32; $i += 4) {
      $out[] = ltrim(substr($hex, $i, 4), '0') ?: '0';
    }

    return $out;
  }

  private function ipInCidr(string $ip, string $cidr): bool
  {
    [$subnet, $bits] = array_pad(explode('/', $cidr, 2), 2, null);

    if (!is_string($subnet) || $subnet === '' || !is_string($bits) || $bits === '') {
      return false;
    }

    $bits = (int) $bits;

    $ipBin = @inet_pton($ip);
    $subnetBin = @inet_pton($subnet);

    if ($ipBin === false || $subnetBin === false) {
      return false;
    }

    if (strlen($ipBin) !== strlen($subnetBin)) {
      return false;
    }

    $maxBits = strlen($ipBin) * 8;

    if ($bits < 0 || $bits > $maxBits) {
      return false;
    }

    $bytes = intdiv($bits, 8);
    $remainder = $bits % 8;

    if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
      return false;
    }

    if ($remainder === 0) {
      return true;
    }

    $mask = chr((0xFF << (8 - $remainder)) & 0xFF);

    return (substr($ipBin, $bytes, 1) & $mask)
      === (substr($subnetBin, $bytes, 1) & $mask);
  }
}
