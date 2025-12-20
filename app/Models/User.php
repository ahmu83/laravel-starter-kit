<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable {
  use HasFactory, Notifiable;

  /**
   * Mass assignable attributes.
   */
  protected $fillable = [
    'name',
    'email',
    'password',

    // WordPress sync fields
    'wp_user_id',
    'wp_roles',
    'wp_user_login',

    // Social auth
    'social_provider',
    'social_provider_id',
    'social_avatar_url',
  ];

  /**
   * Attribute casting.
   */
  protected $casts = [
    'email_verified_at' => 'datetime',
    'password'          => 'hashed',

    'wp_user_id'        => 'integer',
    'wp_roles'          => 'array',
  ];

  /**
   * -------------------------------------------------
   * WordPress helpers
   * -------------------------------------------------
   */

  /**
   * Check if the user has a specific WordPress role.
   */
  public function hasWpRole(string $role): bool {
    return in_array($role, $this->wp_roles ?? [], true);
  }

  /**
   * Check if the user has any of the given WordPress roles.
   */
  public function hasAnyWpRole(array $roles): bool {
    $userRoles = $this->wp_roles ?? [];

    foreach ($roles as $role) {
      if (in_array($role, $userRoles, true)) {
        return true;
      }
    }

    return false;
  }

  /**
   * Check if the user is a WordPress administrator.
   */
  public function isWpAdmin(): bool {
    return $this->hasWpRole('administrator');
  }

  /**
   * Convenience accessor: does this Laravel user
   * have a linked WordPress account?
   */
  public function hasWpAccount(): bool {
    return ! is_null($this->wp_user_id);
  }
}
