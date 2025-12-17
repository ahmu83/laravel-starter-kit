<?php
// app/Http/Middleware/CheckWpCapability.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckWpCapability
{
    public function handle(Request $request, Closure $next, string $capability)
    {
        if (!auth()->check() || !auth()->user()->hasWpCapability($capability)) {
            abort(403, 'Unauthorized - Missing capability: ' . $capability);
        }

        return $next($request);
    }
}
