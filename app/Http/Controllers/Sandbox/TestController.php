<?php
namespace App\Http\Controllers\Sandbox;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TestController extends Controller {
  private string $wp_user_email    = 'ahmu83@gmail.com';
  private string $wp_laravel_email = 'ahmu83@gmail.com';

  public function handler_index(Request $request) {
    // Laravel login status
    $laravel_logged_in = Auth::check();

    // WordPress login status (only if WP is loaded)
    $wp_logged_in = function_exists('is_user_logged_in')
      ? \is_user_logged_in()
      : false;

    $data = [
      'laravel_user'         => Auth::user()?->only(['id', 'email']),
      'wp_user_email'        => $this->wp_user_email,
      'laravel_email'        => $this->wp_laravel_email,

      // Login statuses
      'laravel_login_status' => $laravel_logged_in,
      'wp_user_login_status' => $wp_logged_in,
    ];

    printr($data); // <-- unchanged

    /**
     * Login WP user
     */
    $this->loginWpUser($request);

    /**
     * Logout WP user
     */
    // $this->logoutWpUser($request);

    /**
     * Login Laravel user
     */
    // $this->loginLaravelUser($request);

    /**
     * Logout Laravel user
     */
    // $this->logoutLaravelUser($request);

  }

  /**
   * Log a WordPress user in by email using the full WP environment.
   */
  public function loginWpUser(Request $request) {
    // Make sure WP is actually loaded
    if (! function_exists('get_user_by') || ! function_exists('wp_set_auth_cookie')) {
      return response()->json([
        'ok'      => false,
        'message' => 'WordPress environment not loaded in this request.',
      ], 500);
    }

    $email = $this->wp_user_email;

    $user = \get_user_by('email', $email);

    if (! $user) {
      return response()->json([
        'ok'      => false,
        'message' => "No WordPress user found for email {$email}",
      ], 404);
    }

    // Clear any existing WP auth, then set the new one
    if (function_exists('wp_clear_auth_cookie')) {
      \wp_clear_auth_cookie();
    }

    \wp_set_current_user($user->ID);
    \wp_set_auth_cookie($user->ID);

    // Fire the usual WP login hook for plugin compatibility
    if (function_exists('do_action')) {
      \do_action('wp_login', $user->user_login, $user);
    }

    return response()->json([
      'ok'         => true,
      'message'    => 'WordPress user logged in',
      'user_id'    => $user->ID,
      'user_login' => $user->user_login,
      'email'      => $email,
    ]);
  }

  /**
   * Log the current WordPress user out using the full WP environment.
   */
  public function logoutWpUser(Request $request) {
    if (! function_exists('wp_logout')) {
      return response()->json([
        'ok'      => false,
        'message' => 'WordPress environment not loaded in this request.',
      ], 500);
    }

    // wp_logout() internally clears cookies and current user
    \wp_logout();

    return response()->json([
      'ok'      => true,
      'message' => 'WordPress user logged out',
    ]);
  }

  /**
   * Log a Laravel user in by email (no password check).
   */
  public function loginLaravelUser(Request $request) {
    $email = $this->wp_laravel_email;

    $user = User::query()
      ->where('email', $email)
      ->first();

    if (! $user) {
      return response()->json([
        'ok'      => false,
        'message' => "Laravel user not found for email {$email}",
      ], 404);
    }

    Auth::login($user);
    $request->session()->regenerate();

    return response()->json([
      'ok'      => true,
      'message' => 'Laravel user logged in',
      'user_id' => $user->id,
      'email'   => $user->email,
    ]);
  }

  /**
   * Log the current Laravel user out.
   */
  public function logoutLaravelUser(Request $request) {
    if (! Auth::check()) {
      return response()->json([
        'ok'      => true,
        'message' => 'No Laravel user was logged in',
      ]);
    }

    $userId = Auth::id();
    $email  = Auth::user()->email ?? null;

    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return response()->json([
      'ok'      => true,
      'message' => 'Laravel user logged out',
      'user_id' => $userId,
      'email'   => $email,
    ]);
  }

  public function handler_proxiedUrl(Request $request) {

    dd([
        'request_full_url' => $request->fullUrl(),
        'request_root'     => $request->root(),
        'request_host'     => $request->getHost(),
        'app_url'          => config('app.url'),
        'route_login'      => route('login'),
    ]);

  }

}
