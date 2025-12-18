<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class WordPressAuth
{
    protected WordPressUserSync $sync;

    public function __construct(WordPressUserSync $sync)
    {
        $this->sync = $sync;
    }

    /**
     * Attempt to authenticate against WordPress
     */
    public function attempt(string $email, string $password): ?User
    {
        // Get WP user
        $wpUser = DB::connection('wordpress')
            ->table('users')
            ->where('user_email', $email)
            ->first();

        if (!$wpUser) {
            return null;
        }

        // Use WordPress's built-in password checker
        if (!wp_check_password($password, $wpUser->user_pass, $wpUser->ID)) {
            return null;
        }

        // Sync to Laravel and return
        return $this->sync->syncUserByEmail($email);
    }
}


