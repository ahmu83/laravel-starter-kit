<?php

namespace App\Http\Middleware;

use App\Services\IpAccessService;
use Closure;
use Illuminate\Http\Request;

class VantageAccess
{
  public function handle(Request $request, Closure $next)
  {
    // Hard toggle
    if (! filter_var(env('VANTAGE_ENABLED', false), FILTER_VALIDATE_BOOLEAN)) {
      abort(404);
    }

    // Reuse the same method parsing logic, locally.
    // (Keeps ConditionalFeatureEnable::isFeatureAllowed() private.)
    $raw = strtolower(trim((string) env('VANTAGE_ENABLE_METHOD', '')));

    if ($raw === '' || $raw === 'none') {
      return $next($request);
    }

    $methods = array_values(array_filter(array_map('trim', explode(',', $raw))));

    if (in_array('deny_all', $methods, true)) {
      abort(404);
    }

    foreach ($methods as $method) {
      if (str_starts_with($method, 'ip:')) {
        $mode = trim(substr($method, 3));

        if (! in_array($mode, ['strict', 'class'], true)) {
          abort(404);
        }

        $ip = (string) $request->ip();
        if ($ip === '' || ! app(IpAccessService::class)->isAllowed($ip, $mode)) {
          abort(404);
        }

        continue;
      }

      if ($method === 'auth' || $method === 'auth:any') {
        if (! auth()->check()) {
          abort(404);
        }
        continue;
      }

      if ($method === 'auth:admin') {
        $user = auth()->user();
        if (! $user || ! method_exists($user, 'isWpAdmin') || ! $user->isWpAdmin()) {
          abort(404);
        }
        continue;
      }

      abort(404);
    }

    return $next($request);
  }
}
