<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;

class FeatureGate {
    public function allowed(Request $request, string $methodRaw): bool {
        $raw = strtolower(trim($methodRaw));

        // Missing / empty -> allow (default-on)
        if ($raw === '') {
            return true;
        }

        $methods = array_values(array_filter(array_map('trim', explode(',', $raw))));

        // Explicit deny always wins
        if (in_array('deny_all', $methods, true)) {
            return false;
        }

        // `none` only valid if it's the sole token
        if (count($methods) === 1 && $methods[0] === 'none') {
            return true;
        }

        foreach ($methods as $method) {

            if ($method === 'none' || $method === 'deny_all') {
                continue;
            }

            // ip:strict | ip:class
            if (str_starts_with($method, 'ip:')) {
                $mode = substr($method, 3);

                if (! in_array($mode, ['strict', 'class'], true)) {
                    return false;
                }

                $ip = (string) $request->ip();
                if ($ip === '') {
                    return false;
                }

                if (! app(IpAccessService::class)->isAllowed($ip, $mode)) {
                    return false;
                }

                continue;
            }

            // auth / auth:any
            if ($method === 'auth' || $method === 'auth:any') {
                if (! auth()->check()) {
                    return false;
                }
                continue;
            }

            // auth:admin (WordPress admin semantics)
            if ($method === 'auth:admin') {
                $user = auth()->user();

                if (! $user instanceof User) {
                    return false;
                }

                if (! method_exists($user, 'isWpAdmin') || ! $user->isWpAdmin()) {
                    return false;
                }

                continue;
            }

            // Unknown token -> fail closed
            return false;
        }

        return true;
    }
}
