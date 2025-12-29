<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAuth {
    public function handle(
        Request $request,
        Closure $next,
        string $configKey = 'api_keys.default',
        string $headerName = 'X-API-KEY'
    ): Response {
        return app(VerifyApiKey::class)
            ->handle($request, $next, $configKey, $headerName);
    }
}
