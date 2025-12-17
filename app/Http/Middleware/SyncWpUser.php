<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\WordPressUserSync;

class SyncWpUser
{
    public function __construct(
        protected WordPressUserSync $sync
    ) {}

    public function handle(Request $request, Closure $next)
    {
        // If WordPress user is logged in but Laravel user isn't
        if (function_exists('is_user_logged_in') && is_user_logged_in() && !auth()->check()) {
            $wpUser = wp_get_current_user();
            $user = $this->sync->syncUserByEmail($wpUser->user_email);

            if ($user) {
                auth()->login($user);
            }
        }

        return $next($request);
    }
}

