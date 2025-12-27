<?php
namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {

  public function register(): void {
    //
  }

  public function boot(): void {
    $this->registerInternalAccessGates();
    $this->registerWordPressGates();
    $this->registerWordPressBladeDirectives();
    // $this->maybeApplyProxiedAppUrl();
  }

  /**
   * Register internal access gates for toolbox and sandbox
   *
   * "Fail closed" by default: if allowlists are empty, deny access.
   * Local environment can be allowed automatically if you want.
   *
   * Usage examples:
   *
   * --- In controllers ---
   *
   *   if (Gate::denies('accessToolbox')) {
   *       abort(403, 'Access denied');
   *   }
   *
   *   $this->authorize('accessSandbox');
   *
   * --- In middleware ---
   *
   *   Route::get('/toolbox', ...)
   *       ->middleware('can:accessToolbox');
   *
   * --- In Blade ---
   *
   *   @can('accessToolbox')
   *       <a href="/toolbox">Toolbox</a>
   *   @endcan
   *
   * --- Configuration ---
   *
   *   config/toolbox.php:
   *   return [
   *       'allowed_emails' => [
   *           'admin@example.com',
   *           'dev@example.com',
   *       ],
   *   ];
   *
   *   config/sandbox.php:
   *   return [
   *       'allowed_emails' => [
   *           'tester@example.com',
   *       ],
   *   ];
   */
  protected function registerInternalAccessGates(): void {
    /**
     * IP-based authorization gate.
     *
     * This gate does NOT rely on the authenticated user.
     * It authorizes access purely based on the request IP.
     *
     * Examples:
     *
     * $this->authorize('ip-access');
     *
     * // Strict IPv4 only
     * $this->authorize('ip-access', ['strict', 'v4']);
     *
     * // Class mode using config class lists
     * $this->authorize('ip-access', ['class', 'auto']);
     *
     * // Inline allowlists
     * $this->authorize('ip-access', [
     *   'strict',
     *   'auto',
     *   '162.197.8.160|10.0.0.5',
     *   '2001:4860:7:110e::d5',
     * ]);
     *
     * // Manual check
     * if (Gate::denies('ip-access')) {
     *   abort(403);
     * }
     */
    Gate::define('ip-access', function ($user = null, ...$args): bool {
      $mode = $args[0] ?? 'strict';      // strict | class
      $ipVersion = $args[1] ?? 'auto';   // auto | v4 | v6
      $allowedV4 = $args[2] ?? null;     // pipe-separated list or null to use config
      $allowedV6 = $args[3] ?? null;     // pipe-separated list or null to use config

      $clientIp = (string) request()->ip();

      if ($clientIp === '') {
        return false;
      }

      return app(IpAccessService::class)->isAllowed(
        $clientIp,
        $mode,
        $ipVersion,
        $allowedV4,
        $allowedV6
      );
    });

    /**
     * Authorization gate for viewing Laravel Pulse.
     *
     * Access rules:
     * - User must be an admin (via User::isAdmin()).
     * - Request must originate from an allowed IP address.
     *
     * IP enforcement is delegated to the `ip-access` gate to ensure
     * the same IP allowlist logic is reused across the application.
     *
     * Current policy:
     * - strict mode
     * - auto IP version (IPv4 + IPv6)
     * - exact IP matches only
     *
     * Alternative (commented below):
     * - class mode
     * - allows entire IPv4 /24 and IPv6 /64 networks
     */
    Gate::define('viewPulse', function ($user = null) {
      // Allow unrestricted access in local
      if (app()->environment('local')) {
        return true;
      }

      // Non-local: must be logged in and admin
      if (! $user instanceof User) {
        return false;
      }

      if (! $user->isAdmin()) {
        return false;
      }

      // Non-local: IP allowlist
      return Gate::allows(
        'ip-access',
        ['strict', 'auto', '162.197.8.160', '2001:4860:7:110e::d5']
      );

      // Alternative network-based access:
      // return Gate::allows(
      //   'ip-access',
      //   ['class', 'auto', '162.197.8.0/24', '2001:4860:7:110e::/64']
      // );

    });

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
   *
   * Usage examples:
   *
   * --- Inside controllers (recommended) ---
   *
   *   // Require administrator role
   *   $this->authorize('wp-role', 'administrator');
   *
   *   // Require capability (manage settings)
   *   $this->authorize('wp-capability', 'manage_options');
   *
   * --- Manual checks ---
   *
   *   if (Gate::denies('wp-role', 'editor')) {
   *       abort(403);
   *   }
   *
   *   if (Gate::allows('wp-capability', 'edit_posts')) {
   *       // show editor-only controls
   *   }
   *
   * --- In policies ---
   *
   *   public function update(User $user)
   *   {
   *       return Gate::forUser($user)->allows('wp-capability', 'edit_posts');
   *   }
   *
   * --- When middleware is better ---
   *
   *   // Single role
   *   ->middleware(['auth', 'has.wp.role:administrator'])
   *
   *   // Multiple roles (OR logic - user needs ANY)
   *   ->middleware(['auth', 'has.wp.role:administrator|editor'])
   *   ->middleware(['auth', 'has.wp.role:admin|editor|author'])
   *
   *   // Single capability
   *   ->middleware(['auth', 'has.wp.capability:manage_options'])
   *
   *   // Multiple capabilities (OR logic - user needs ANY)
   *   ->middleware(['auth', 'has.wp.capability:edit_posts|publish_posts'])
   *   ->middleware(['auth', 'has.wp.capability:edit_posts|edit_pages|edit_others_posts'])
   *
   * Use gates when authorization is contextual / runtime.
   * Use middleware when authorization is structural / per-route.
   *
   * --- Optional: super-admin override ---
   *
   *   Gate::before(function ($user, string $ability) {
   *       if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
   *           return true;
   *       }
   *       return null;
   *   });
   */
  protected function registerWordPressGates(): void {
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
   *
   * Usage examples:
   *
   * --- Single role ---
   *
   *   @wpRole('administrator')
   *       <a href="/admin">Admin Panel</a>
   *   @endwpRole
   *
   *   @wpRole('editor')
   *       <button>Edit Content</button>
   *   @else
   *       <p>You need editor role</p>
   *   @endwpRole
   *
   * --- Multiple roles (OR logic - user needs ANY) ---
   *
   *   @wpRole(['administrator', 'editor'])
   *       <a href="/dashboard">Dashboard</a>
   *   @endwpRole
   *
   *   @wpRole(['admin', 'editor', 'author'])
   *       <button>Create Post</button>
   *   @endwpRole
   *
   * --- Negative role check (NOT) ---
   *
   *   @notWpRole('subscriber')
   *       <a href="/premium-content">Premium Content</a>
   *   @endnotWpRole
   *
   *   @notWpRole(['subscriber', 'contributor'])
   *       <a href="/advanced-features">Advanced Features</a>
   *   @endnotWpRole
   *
   * --- Single capability ---
   *
   *   @wpCan('manage_options')
   *       <a href="/settings">Settings</a>
   *   @endwpCan
   *
   *   @wpCan('edit_posts')
   *       <button>Edit</button>
   *   @else
   *       <span>Read Only</span>
   *   @endwpCan
   *
   * --- Multiple capabilities (OR logic - user needs ANY) ---
   *
   *   @wpCan(['edit_posts', 'publish_posts'])
   *       <button>Manage Posts</button>
   *   @endwpCan
   *
   *   @wpCan(['edit_posts', 'edit_pages', 'edit_others_posts'])
   *       <div class="content-editor">...</div>
   *   @endwpCan
   *
   * --- Negative capability check (NOT) ---
   *
   *   @notWpCan('manage_options')
   *       <p>Contact admin for settings access</p>
   *   @endnotWpCan
   *
   *   @notWpCan(['manage_options', 'activate_plugins'])
   *       <p>No admin access</p>
   *   @endnotWpCan
   */
  protected function registerWordPressBladeDirectives(): void {
    // Positive role check: @wpRole('administrator') or @wpRole(['admin', 'editor'])
    Blade::if('wpRole', function (string|array $roles) {
      if (!auth()->check()) {
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
      if (!auth()->check()) {
        return false;
      }

      $user = auth()->user();

      // Single role
      if (is_string($roles)) {
        return !$user->hasWpRole($roles);
      }

      // Multiple roles - user must NOT have ANY of them
      return !$user->hasAnyWpRole($roles);
    });

    // Positive capability check: @wpCan('manage_options') or @wpCan(['edit_posts', 'publish_posts'])
    Blade::if('wpCan', function (string|array $capabilities) {
      if (!auth()->check()) {
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
      if (!auth()->check()) {
        return false;
      }

      $user = auth()->user();

      // Single capability
      if (is_string($capabilities)) {
        return !$user->hasWpCapability($capabilities);
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
   *   'url_proxied' => env('APP_URL_PROXIED', false),
   */
  protected function maybeApplyProxiedAppUrl(): void {
    // Either via config:
    $proxiedMode = (bool) config('app.app_url_proxied');
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
