<?php
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

// In your helpers file or create app/helpers.php
if (! function_exists('maybe_unserialize')) {
  function maybe_unserialize($data) {
    if (is_serialized($data)) {
      return @unserialize(trim($data));
    }
    return $data;
  }
}

if (! function_exists('is_serialized')) {
  function is_serialized($data, $strict = true) {
    if (! is_string($data)) {
      return false;
    }
    $data = trim($data);
    if ('N;' === $data) {
      return true;
    }
    if (strlen($data) < 4) {
      return false;
    }
    if (':' !== $data[1]) {
      return false;
    }
    if ($strict) {
      $lastc = substr($data, -1);
      if (';' !== $lastc && '}' !== $lastc) {
        return false;
      }
    } else {
      $semicolon = strpos($data, ';');
      $brace     = strpos($data, '}');
      if (false === $semicolon && false === $brace) {
        return false;
      }
      if (false !== $semicolon && $semicolon < 3) {
        return false;
      }
      if (false !== $brace && $brace < 4) {
        return false;
      }
    }
    $token = $data[0];
    switch ($token) {
    case 's':
      if ($strict) {
        if ('"' !== substr($data, -2, 1)) {
          return false;
        }
      } elseif (false === strpos($data, '"')) {
        return false;
      }
    case 'a':
    case 'O':
      return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
    case 'b':
    case 'i':
    case 'd':
      $end = $strict ? '$' : '';
      return (bool) preg_match("/^{$token}:[0-9.E+-]+;$end/", $data);
    }
    return false;
  }
}

/**
 * Send a signed JSON webhook request using HMAC authentication.
 *
 * This helper:
 * - JSON-encodes the payload
 * - Computes an HMAC-SHA256 signature using the shared secret
 * - Sends the request using Laravel's HTTP client
 *
 * The signature is sent in a configurable header (default: X-API-Signature)
 * in the format:
 *
 *   sha256=<hex hmac>
 *
 * The shared secret is never sent directly.
 *
 * @param string $url              Absolute webhook URL
 * @param array  $payload          Payload data to JSON-encode and sign
 * @param string $secret           Shared secret used for HMAC generation
 * @param string $signatureHeader  Header name for the signature
 *                                 (default: X-API-Signature)
 * @param array  $options           Optional request overrides:
 *                                 - method  (POST|PUT|PATCH|DELETE)
 *                                 - timeout (seconds)
 *                                 - headers (array)
 *                                 - query   (array)
 *
 * @return \Illuminate\Http\Client\Response
 */
function send_signed_webhook(
  string $url,
  array $payload,
  string $secret,
  string $signatureHeader = 'X-Webhook-Signature',
  array $options = []
): Response {
  $method  = strtoupper((string) ($options['method'] ?? 'POST'));
  $timeout = (int) ($options['timeout'] ?? 10);
  $headers = (array) ($options['headers'] ?? []);
  $query   = (array) ($options['query'] ?? []);

  // Encode JSON deterministically for signing
  $body = json_encode($payload, JSON_UNESCAPED_SLASHES);

  $signature = hash_hmac('sha256', $body, $secret);

  $headers = array_merge([
    'Content-Type'   => 'application/json',
    $signatureHeader => 'sha256=' . $signature,
  ], $headers);

  $client = Http::withHeaders($headers)->timeout($timeout);

  return match ($method) {
    'GET'    => $client->get($url, $query),
    'PUT'    => $client->withBody($body, 'application/json')->put($url, $query),
    'PATCH'  => $client->withBody($body, 'application/json')->patch($url, $query),
    'DELETE' => $client->withBody($body, 'application/json')->delete($url, $query),
    default  => $client->withBody($body, 'application/json')->post($url, $query),
  };
}


