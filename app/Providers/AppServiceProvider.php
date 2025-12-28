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
    |
    | Uses unified FeatureGate service with override behavior.
    |
    | Override behavior:
    | - If enable_method is set → Override base enabled config
    | - If enable_method is empty/none → Respect base enabled config
    |
    */
    LogViewer::auth(function ($request) {
      $baseEnabled = (bool) config('features.log_viewer.enabled');
      $enableMethod = (string) config('features.log_viewer.enable_method');

      // If override is active, use it
      if ($this->hasOverride($enableMethod)) {
        return app(FeatureGate::class)->allowed($request, $enableMethod);
      }

      // No override: respect base enabled
      return $baseEnabled;
    });

    /*
    |--------------------------------------------------------------------------
    | Pulse Authorization
    |--------------------------------------------------------------------------
    |
    | Uses unified FeatureGate service with override behavior.
    |
    | Override behavior:
    | - If enable_method is set → Override base enabled config
    | - If enable_method is empty/none → Respect base enabled config
    |
    | Examples:
    |   PULSE_ENABLED=false
    |   PULSE_ENABLE_METHOD=ip:strict
    |   Result: Enabled ONLY for allowed IPs
    |
    */
    Gate::define('viewPulse', function ($user = null) {
      $baseEnabled = (bool) config('features.pulse.enabled');
      $enableMethod = (string) config('features.pulse.enable_method');

      // If override is active, use it
      if ($this->hasOverride($enableMethod)) {
        return app(FeatureGate::class)->allowed(request(), $enableMethod);
      }

      // No override: respect base enabled
      return $baseEnabled;
    });

    /*
    |--------------------------------------------------------------------------
    | WordPress Gates & Blade Directives
    |--------------------------------------------------------------------------
    */
    $this->registerWordPressGates();
    $this->registerWordPressBladeDirectives();

    // Optional (only if you actively use APP_URL_PROXIED behavior)
    // $this->maybeApplyProxiedAppUrl();
  }

  /**
   * Check if enable_method is acting as an override.
   *
   * Empty or "none" means no override (use base config).
   * Any other value means override is active.
   */
  protected function hasOverride(string $enableMethod): bool
  {
    $normalized = strtolower(trim($enableMethod));
    return $normalized !== '' && $normalized !== 'none';
  }

  /**
   * Register WordPress authorization gates
   *
   * These gates delegate to the User model helpers, which use wp_user_id
   * to check roles/capabilities against the WordPress side.
   *
   * Semantics match WordPress:
   *   - One role per check
   *   - One capability per check
   */
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

  /**
   * Register WordPress Blade directives
   *
   * Custom Blade directives for checking WordPress roles and capabilities
   * in your views. These provide cleaner syntax than @can/@cannot.
   *
   * All directives accept either a single string or an array of strings.
   * When an array is provided, OR logic is used (user needs ANY of them).
   */
  protected function registerWordPressBladeDirectives(): void
  {
    // Positive role check: @wpRole('administrator') or @wpRole(['admin', 'editor'])
    Blade::if('wpRole', function (string|array $roles) {
      if (! auth()->check()) {
        return false;
      }

      $user = auth()->user();

      // Single role
      if (is_string($roles)) {
        return $user->hasWpRole($roles);
      }

      // Multiple roles (OR logic)
      return $user->hasAnyWpRole($roles);
    });

    // Negative role check: @notWpRole('subscriber') or @notWpRole(['subscriber', 'contributor'])
    Blade::if('notWpRole', function (string|array $roles) {
      if (! auth()->check()) {
        return false;
      }

      $user = auth()->user();

      // Single role
      if (is_string($roles)) {
        return ! $user->hasWpRole($roles);
      }

      // Multiple roles - user must NOT have ANY of them
      return ! $user->hasAnyWpRole($roles);
    });

    // Positive capability check: @wpCan('manage_options') or @wpCan(['edit_posts', 'publish_posts'])
    Blade::if('wpCan', function (string|array $capabilities) {
      if (! auth()->check()) {
        return false;
      }

      $user = auth()->user();

      // Single capability
      if (is_string($capabilities)) {
        return $user->hasWpCapability($capabilities);
      }

      // Multiple capabilities (OR logic)
      foreach ($capabilities as $capability) {
        if ($user->hasWpCapability($capability)) {
          return true;
        }
      }

      return false;
    });

    // Negative capability check: @notWpCan('manage_options') or @notWpCan(['manage_options', 'activate_plugins'])
    Blade::if('notWpCan', function (string|array $capabilities) {
      if (! auth()->check()) {
        return false;
      }

      $user = auth()->user();

      // Single capability
      if (is_string($capabilities)) {
        return ! $user->hasWpCapability($capabilities);
      }

      // Multiple capabilities - user must NOT have ANY of them
      foreach ($capabilities as $capability) {
        if ($user->hasWpCapability($capability)) {
          return false;
        }
      }

      return true;
    });
  }

  /**
   * Apply proxied app URL if configured
   *
   * Forces URL generation to use APP_URL when running behind a proxy.
   *
   * Configuration:
   *   In .env:
   *   APP_URL=https://example.com
   *   APP_URL_PROXIED=true
   *
   *   In config/app.php:
   *   'app_url_proxied' => env('APP_URL_PROXIED', false),
   */
  protected function maybeApplyProxiedAppUrl(): void
  {
    $proxiedMode = (bool) config('app.app_url_proxied');

    if (! $proxiedMode) {
      return;
    }

    $appUrl = config('app.url');

    if (empty($appUrl)) {
      return;
    }

    URL::forceRootUrl($appUrl);

    if (str_starts_with($appUrl, 'https://')) {
      URL::forceScheme('https');
    }
  }
}
