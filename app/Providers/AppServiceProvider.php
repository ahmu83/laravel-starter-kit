<?php
namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {

  public function register(): void {
    //
  }

  public function boot(): void {
    /*
    |--------------------------------------------------------------------------
    | Internal access gates
    |--------------------------------------------------------------------------
    |
    | "Fail closed" by default: if allowlists are empty, deny access.
    | Local environment can be allowed automatically if you want.
    |
    */

    Gate::define('accessToolbox', function ($user): bool {
      if (app()->environment('local')) {
        return true;
      }

      $allowed = config('toolbox.allowed_emails', []);
      if (empty($allowed)) {
        return false;
      }

      return in_array((string) $user->email, $allowed, true);
    });

    Gate::define('accessSandbox', function ($user): bool {
      if (app()->environment('local')) {
        return true;
      }

      $allowed = config('sandbox.allowed_emails', []);
      if (empty($allowed)) {
        return false;
      }

      return in_array((string) $user->email, $allowed, true);
    });





    /*
    |--------------------------------------------------------------------------
    | WordPress-based authorization
    |--------------------------------------------------------------------------
    |
    | These gates delegate to the User model helpers, which use wp_user_id
    | to check roles/capabilities against the WordPress side.
    |
    | Semantics match WordPress:
    |   - One role per check
    |   - One capability per check
    |
    | Usage examples:
    |
    | --- Inside controllers (recommended) ---
    |
    |   // Require administrator role
    |   $this->authorize('wp-role', 'administrator');
    |
    |   // Require capability (manage settings)
    |   $this->authorize('wp-capability', 'manage_options');
    |
    |
    | --- Manual checks ---
    |
    |   if (Gate::denies('wp-role', 'editor')) {
    |       abort(403);
    |   }
    |
    |   if (Gate::allows('wp-capability', 'edit_posts')) {
    |       // show editor-only controls
    |   }
    |
    |
    | --- In policies ---
    |
    |   public function update(User $user)
    |   {
    |       return Gate::forUser($user)->allows('wp-capability', 'edit_posts');
    |   }
    |
    |
    | --- When middleware is better ---
    |
    |   // Protect an entire route
    |   ->middleware(['auth', 'has.wp.role:administrator'])
    |
    | Use gates when authorization is contextual / runtime.
    | Use middleware when authorization is structural / per-route.
    |
    */

    Gate::define('wp-role', function ($user, string $role): bool {
      if (! $user || ! method_exists($user, 'hasWpRole')) {
        return false;
      }

      return $user->hasWpRole($role);
    });

    Gate::define('wp-capability', function ($user, string $capability): bool {
      if (! $user || ! method_exists($user, 'hasWpCapability')) {
        return false;
      }

      return $user->hasWpCapability($capability);
    });






    /*
    |--------------------------------------------------------------------------
    | Optional: super-admin override
    |--------------------------------------------------------------------------
    |
    | If you later add a role/flag on users, uncomment this and implement it.
    |
    */
    // Gate::before(function ($user, string $ability) {
    //   if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
    //     return true;
    //   }
    //   return null;
    // });

    $this->maybeApplyProxiedAppUrl();

  }

  private function maybeApplyProxiedAppUrl(): void {
    // Either via config:
    $proxiedMode = (bool) config('app.url_proxied');
    // or directly: $proxiedMode = filter_var(env('APP_URL_PROXIED', false), FILTER_VALIDATE_BOOLEAN);

    if (! $proxiedMode) {
      return;
    }

    $appUrl = config('app.url');
    if (empty($appUrl)) {
      return;
    }

    // This is the key: force URL generator root to APP_URL
    URL::forceRootUrl($appUrl);

    if (str_starts_with($appUrl, 'https://')) {
      URL::forceScheme('https');
    }
  }
}
