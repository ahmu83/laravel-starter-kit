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
