<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;
use App\Services\FeatureGate;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
  public function register(): void
  {
    //
  }

  public function boot(): void
  {
    /*
    |--------------------------------------------------------------------------
    | Log Viewer Authorization
    |--------------------------------------------------------------------------
    */
    LogViewer::auth(function ($request) {
      $enableMethod = (string) config('features.log_viewer.enable_method');

      // If override is set, it IS the source of truth
      if ($this->hasOverride($enableMethod)) {
        return app(FeatureGate::class)->allowed($request, $enableMethod);
      }

      // No override: respect base config
      return (bool) config('features.log_viewer.enabled');
    });

    /*
    |--------------------------------------------------------------------------
    | Pulse Authorization
    |--------------------------------------------------------------------------
    */
    Gate::define('viewPulse', function ($user = null) {
      $enableMethod = (string) config('features.pulse.enable_method');

      // If override is set, it IS the source of truth
      if ($this->hasOverride($enableMethod)) {
        return app(FeatureGate::class)->allowed(request(), $enableMethod);
      }

      // No override: respect base config
      return (bool) config('features.pulse.enabled');
    });

    /*
    |--------------------------------------------------------------------------
    | Vantage Authorization
    |--------------------------------------------------------------------------
    */
    Gate::define('viewVantage', function ($user = null) {
      $enableMethod = (string) config('features.vantage.enable_method');

      // If override is set, it IS the source of truth
      if ($this->hasOverride($enableMethod)) {
        return app(FeatureGate::class)->allowed(request(), $enableMethod);
      }

      // No override: respect base config
      return (bool) config('features.vantage.enabled');
    });

    /*
    |--------------------------------------------------------------------------
    | WordPress Gates & Blade Directives
    |--------------------------------------------------------------------------
    */
    $this->registerWordPressGates();
    $this->registerWordPressBladeDirectives();
  }

  protected function hasOverride(string $enableMethod): bool
  {
    $normalized = strtolower(trim($enableMethod));
    return $normalized !== '' && $normalized !== 'none';
  }

  protected function registerWordPressGates(): void
  {
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
  }

  protected function registerWordPressBladeDirectives(): void
  {
    Blade::if('wpRole', function (string|array $roles) {
      if (! auth()->check()) {
        return false;
      }

      $user = auth()->user();

      if (is_string($roles)) {
        return $user->hasWpRole($roles);
      }

      return $user->hasAnyWpRole($roles);
    });

    Blade::if('notWpRole', function (string|array $roles) {
      if (! auth()->check()) {
        return false;
      }

      $user = auth()->user();

      if (is_string($roles)) {
        return ! $user->hasWpRole($roles);
      }

      return ! $user->hasAnyWpRole($roles);
    });

    Blade::if('wpCan', function (string|array $capabilities) {
      if (! auth()->check()) {
        return false;
      }

      $user = auth()->user();

      if (is_string($capabilities)) {
        return $user->hasWpCapability($capabilities);
      }

      foreach ($capabilities as $capability) {
        if ($user->hasWpCapability($capability)) {
          return true;
        }
      }

      return false;
    });

    Blade::if('notWpCan', function (string|array $capabilities) {
      if (! auth()->check()) {
        return false;
      }

      $user = auth()->user();

      if (is_string($capabilities)) {
        return ! $user->hasWpCapability($capabilities);
      }

      foreach ($capabilities as $capability) {
        if ($user->hasWpCapability($capability)) {
          return false;
        }
      }

      return true;
    });
  }
}
