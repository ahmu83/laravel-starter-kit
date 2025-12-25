<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SyncWordPressAuth
{
  /**
   * Routes that should be excluded from WordPress auth sync
   */
  protected array $except = [
    'api/*',
    'webhook/*',
    'toolbox/*', // Admin/debugging routes
    'sandbox/*', // Testing routes
  ];

  /**
   * Handle an incoming request.
   *
   * Bidirectional WordPress auth sync:
   * 1. If WP logged in but Laravel NOT logged in → Auto-login Laravel user
   * 2. If Laravel logged in but WP NOT logged in → Logout Laravel user
   */
  public function handle(Request $request, Closure $next): Response
  {
    // Skip check for excluded routes
    if ($this->shouldExclude($request)) {
      return $next($request);
    }

    // Check if WordPress functions are available
    if (!function_exists('is_user_logged_in') || !function_exists('wp_get_current_user')) {
      // WordPress not loaded yet, skip check
      return $next($request);
    }

    $wpUserLoggedIn = is_user_logged_in();
    $laravelUserLoggedIn = Auth::check();

    // Case 1: WP logged in, Laravel NOT logged in → Auto-login to Laravel
    if ($wpUserLoggedIn && !$laravelUserLoggedIn) {
      $wpUser = wp_get_current_user();

      if ($wpUser && $wpUser->ID) {
        $laravelUser = $this->findLaravelUser($wpUser);

        if ($laravelUser) {
          Auth::login($laravelUser, true); // remember = true

          // Regenerate session for security
          $request->session()->regenerate();
        }
      }
    }

    // Case 2: Laravel logged in, WP NOT logged in → Logout Laravel
    if ($laravelUserLoggedIn && !$wpUserLoggedIn) {
      Auth::logout();
      $request->session()->invalidate();
      $request->session()->regenerateToken();
    }

    return $next($request);
  }

  /**
   * Find Laravel user by WordPress user
   *
   * @param \WP_User $wpUser
   * @return \App\Models\User|null
   */
  protected function findLaravelUser($wpUser): ?User
  {
    // Try by wp_user_id first
    $user = User::where('wp_user_id', $wpUser->ID)->first();

    if ($user) {
      return $user;
    }

    // Fallback to email
    return User::where('email', $wpUser->user_email)->first();
  }

  /**
   * Determine if the request should be excluded from sync
   */
  protected function shouldExclude(Request $request): bool
  {
    foreach ($this->except as $pattern) {
      if ($request->is($pattern)) {
        return true;
      }
    }

    return false;
  }
}
