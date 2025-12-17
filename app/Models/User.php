<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
  use HasFactory, Notifiable;

  protected $fillable = [
    'name',
    'email',
    'password',
    'wp_user_id',
    'wp_roles',
    'wp_capabilities',
    'wp_primary_role',
  ];

  protected $casts = [
    'email_verified_at' => 'datetime',
    'password' => 'hashed',
    'wp_roles' => 'array',
    'wp_capabilities' => 'array',
    'wp_user_id' => 'integer',
  ];

  /**
   * Check if user has a WordPress capability
   */
  public function hasWpCapability(string $capability): bool
  {
    return ($this->wp_capabilities[$capability] ?? false) === true;
  }

  /**
   * Check if user has a WordPress role
   */
  public function hasWpRole(string $role): bool
  {
    return in_array($role, $this->wp_roles ?? []);
  }

  /**
   * Check if user is WordPress administrator
   */
  public function isWpAdmin(): bool
  {
    return $this->hasWpRole('administrator');
  }
}
