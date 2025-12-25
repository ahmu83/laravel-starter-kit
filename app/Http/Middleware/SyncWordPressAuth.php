<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SyncWordPressAuth {
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
   * Check if WordPress user is logged in.
   * If Laravel user is logged in but WordPress user is NOT logged in, logout Laravel user.
   */
  public function handle(Request $request, Closure $next): Response {
    // Skip check for excluded routes
    if ($this->shouldExclude($request)) {
      return $next($request);
    }

    // Only check if Laravel user is logged in
    if (! Auth::check()) {
      return $next($request);
    }

    // Check if WordPress functions are available
    if (! function_exists('is_user_logged_in') || ! function_exists('wp_get_current_user')) {
      // WordPress not loaded yet, skip check
      return $next($request);
    }

    // Check if WordPress user is logged in
    $wpUserLoggedIn = is_user_logged_in();

    // If Laravel logged in but WordPress NOT logged in → logout Laravel
    if (! $wpUserLoggedIn) {
      Auth::logout();
      $request->session()->invalidate();
      $request->session()->regenerateToken();

      // Optionally redirect to login or home
      // return redirect('/')->with('message', 'Your session has expired.');
    }

    return $next($request);
  }

  /**
   * Determine if the request should be excluded from sync
   */
  protected function shouldExclude(Request $request): bool {
    foreach ($this->except as $pattern) {
      if ($request->is($pattern)) {
        return true;
      }
    }

    return false;
  }
}
