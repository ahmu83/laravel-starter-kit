// app/Services/WordPressUserSync.php
<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class WordPressUserSync
{
    protected string $prefix;

    public function __construct()
    {
        $this->prefix = config('database.connections.wordpress.prefix');
    }

    /**
     * Sync a WordPress user to Laravel by ID
     */
    public function syncUserById(int $wpUserId): ?User
    {
        $wpUser = $this->getWpUser($wpUserId);

        if (!$wpUser) {
            return null;
        }

        return $this->syncUser($wpUser);
    }

    /**
     * Sync a WordPress user to Laravel by email
     */
    public function syncUserByEmail(string $email): ?User
    {
        $wpUser = $this->getWpUserByEmail($email);

        if (!$wpUser) {
            return null;
        }

        return $this->syncUser($wpUser);
    }

    /**
     * Sync WordPress user data to Laravel
     */
    protected function syncUser(object $wpUser): User
    {
        return User::updateOrCreate(
            ['wp_user_id' => $wpUser->ID],
            [
                'name' => $wpUser->display_name,
                'email' => $wpUser->user_email,
                'password' => $wpUser->user_pass, // Already hashed by WP
                'email_verified_at' => now(),
                'wp_roles' => $this->getWpUserRoles($wpUser->ID),
            ]
        );
    }

    /**
     * Get WordPress user by ID
     */
    protected function getWpUser(int $wpUserId): ?object
    {
        return DB::connection('wordpress')
            ->table('users') // Laravel will automatically add the prefix
            ->where('ID', $wpUserId)
            ->first();
    }

    /**
     * Get WordPress user by email
     */
    protected function getWpUserByEmail(string $email): ?object
    {
        return DB::connection('wordpress')
            ->table('users')
            ->where('user_email', $email)
            ->first();
    }

    /**
     * Get WordPress user roles
     */
    protected function getWpUserRoles(int $wpUserId): array
    {
        $meta = DB::connection('wordpress')
            ->table('usermeta')
            ->where('user_id', $wpUserId)
            ->where('meta_key', $this->prefix . 'capabilities')
            ->value('meta_value');

        if (!$meta) {
            return [];
        }

        $capabilities = maybe_unserialize($meta);

        if (!is_array($capabilities)) {
            return [];
        }

        return array_keys(array_filter($capabilities));
    }
}
