<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

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
        'password' => 'hashed',

        'wp_user_id' => 'integer',
        'wp_roles' => 'array',
    ];

    /**
     * -------------------------------------------------
     * WordPress helpers
     * -------------------------------------------------
     */

    /**
     * Return the current WordPress roles for this user.
     *
     * Prefers live roles from WordPress (via get_userdata)
     * and falls back to the cached wp_roles column.
     */
    public function wpRoleNames(): array {
        if ($this->wp_user_id && function_exists('get_userdata')) {
            $wpUser = get_userdata($this->wp_user_id);

            if ($wpUser && is_array($wpUser->roles)) {
                return $wpUser->roles;
            }
        }

        return $this->wp_roles ?? [];
    }

    /**
     * Check if the user has a specific WordPress role.
     *
     * One role per check (matches WP semantics).
     */
    public function hasWpRole(string $role): bool {
        $role = trim($role);

        if ($role === '') {
            return false;
        }

        return in_array($role, $this->wpRoleNames(), true);
    }

    /**
     * Check if the user has any of the given WordPress roles.
     *
     * Example:
     *   $user->hasAnyWpRole(['editor', 'administrator'])
     */
    public function hasAnyWpRole(array $roles): bool {
        if (empty($roles)) {
            return false;
        }

        $userRoles = $this->wpRoleNames();

        foreach ($roles as $role) {
            if (in_array($role, $userRoles, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the user has a specific WordPress capability.
     *
     * Mirrors user_can( $user_id, $capability )
     * One capability per call.
     */
    public function hasWpCapability(string $capability): bool {
        $capability = trim($capability);

        if ($capability === '') {
            return false;
        }

        if (! $this->wp_user_id) {
            return false;
        }

        if (! function_exists('user_can')) {
            return false;
        }

        return user_can($this->wp_user_id, $capability);
    }

    /**
     * Convenience shortcut for admin check.
     */
    public function isWpAdmin(): bool {
        return $this->hasWpRole('administrator');
    }

    /**
     * Does this Laravel user have a linked WordPress account?
     */
    public function hasWpAccount(): bool {
        return ! is_null($this->wp_user_id);
    }
}
