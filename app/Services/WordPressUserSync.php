<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class WordPressUserSync {
  protected string $prefix;

  public function __construct() {
    $this->prefix = config('database.connections.wordpress.prefix');
  }

  public function syncUserById(int $wpUserId): ?User {
    $wpUser = $this->getWpUser($wpUserId);

    if (! $wpUser) {
      return null;
    }

    return $this->syncUser($wpUser);
  }

  public function syncUserByEmail(string $email): ?User {
    $wpUser = $this->getWpUserByEmail($email);

    if (! $wpUser) {
      return null;
    }

    return $this->syncUser($wpUser);
  }

  protected function syncUser(object $wpUser): User {
    $capabilities = $this->getWpUserCapabilities($wpUser->ID);
    $roles        = array_keys(array_filter($capabilities, fn($val, $key) =>
      ! str_contains($key, '_') && $val === true,
      ARRAY_FILTER_USE_BOTH
    ));

    return User::updateOrCreate(
      ['wp_user_id' => $wpUser->ID],
      [
        'name'              => $wpUser->display_name,
        'email'             => $wpUser->user_email,
        'password'          => $wpUser->user_pass,
        'email_verified_at' => now(),
        'wp_roles'          => $roles,            // ["administrator"]
        'wp_capabilities'   => $capabilities,     // {"manage_options": true, ...}
        'wp_primary_role'   => $roles[0] ?? null, // "administrator"
      ]
    );
  }

  protected function getWpUser(int $wpUserId): ?object {
    return DB::connection('wordpress')
      ->table('users')
      ->where('ID', $wpUserId)
      ->first();
  }

  protected function getWpUserByEmail(string $email): ?object {
    return DB::connection('wordpress')
      ->table('users')
      ->where('user_email', $email)
      ->first();
  }

  /**
   * Get WordPress user capabilities (includes roles + individual permissions)
   */
  protected function getWpUserCapabilities(int $wpUserId): array {
    $meta = DB::connection('wordpress')
      ->table('usermeta')
      ->where('user_id', $wpUserId)
      ->where('meta_key', $this->prefix . 'capabilities')
      ->value('meta_value');

    if (! $meta) {
      return [];
    }

    $capabilities = maybe_unserialize($meta);

    return is_array($capabilities) ? $capabilities : [];
  }
}
