<?php
namespace App\Providers;

use Illuminate\Support\Facades\Gate;
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
  }
}
