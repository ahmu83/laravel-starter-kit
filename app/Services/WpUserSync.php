<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\ConnectionInterface;

/**
 * Synchronizes a Laravel user with a WordPress user.
 *
 * WordPress is treated as the parent system for roles.
 *
 * Persists to Laravel:
 * - wp_user_id
 * - wp_roles (array of WP role slugs)
 * - wp_user_login (mirrors wp_users.user_login)
 *
 * Persists to WordPress usermeta:
 * - laravel_user_id (string)
 */
class WpUserSync {
    /**
     * Default WordPress role to seed for newly-created WP users.
     * This is only used when the WP user has no roles yet.
     */
    protected string $defaultWpRole = 'subscriber';

    /**
     * WordPress database connection.
     */
    protected ConnectionInterface $WPDB;

    /**
     * WordPress table prefix (usually "wp_").
     */
    protected string $prefix;

    /**
     * The Laravel user being synced.
     */
    protected User $laravelUser;

    /**
     * Cached WordPress user ID once resolved.
     */
    protected ?int $wpUserId = null;

    public function __construct(User $laravelUser) {
        $this->WPDB = DB::connection('wordpress');
        $this->prefix = (string) $this->WPDB->getTablePrefix();
        $this->laravelUser = $laravelUser;

        $this->wpUserId = $laravelUser->wp_user_id ? (int) $laravelUser->wp_user_id : null;
    }

    /**
     * Main entry point.
     *
     * - Upserts the WP user (preferring wp_user_id when available)
     * - Stores laravel_user_id in WP usermeta
     * - Reads WP roles from WP meta and mirrors them into Laravel wp_roles
     * - If the WP user has no roles yet, seeds a default role once
     * - Mirrors wp_users.user_login into Laravel wp_user_login
     */
    public function sync(): array {
        $email = (string) $this->laravelUser->email;

        $wpUser = $this->wpUpsertUser($email, $this->laravelUser);

        $wpUserId = (int) $wpUser->ID;
        $this->wpUserId = $wpUserId;

        // Link WP → Laravel by storing Laravel user ID in WP usermeta
        $this->wpSyncLaravelUserId($wpUserId);

        // WP is parent: read roles from WP meta
        $capabilities = $this->wpGetCapabilities($wpUserId);
        $roles = $this->wpExtractRolesFromCapabilities($capabilities);

        // New WP user (or missing caps): seed a default role once
        if (! count($roles)) {
            $this->wpSetRolesOnUser($wpUserId, [$this->defaultWpRole]);

            // Re-read after seeding
            $capabilities = $this->wpGetCapabilities($wpUserId);
            $roles = $this->wpExtractRolesFromCapabilities($capabilities);
        }

        $this->laravelSyncColumns($wpUserId, $roles, $wpUser);

        return [
            'wp_user_id' => $wpUserId,
            'wp_roles' => $roles,
            'wp_user' => [
                'ID' => $wpUserId,
                'user_login' => $wpUser->user_login ?? null,
                'user_email' => $wpUser->user_email ?? null,
                'display_name' => $wpUser->display_name ?? null,
            ],
        ];
    }

    /**
     * Retrieve the WP user ID by email (cached).
     */
    public function getId(string $email): ?int {
        if ($this->wpUserId) {
            return $this->wpUserId;
        }

        $id = $this->wpUserIdByEmail($email);
        $this->wpUserId = $id;

        return $id;
    }

    /**
     * Retrieve the full WP user record by email.
     */
    public function get(string $email): ?object {
        $id = $this->getId($email);

        if (! $id) {
            return null;
        }

        return $this->wpUsersTable()
            ->where('ID', $id)
            ->first();
    }

    /**
     * Force re-sync with a different Laravel user instance.
     */
    public function update(User $laravelUser): array {
        $this->laravelUser = $laravelUser;
        $this->wpUserId = $laravelUser->wp_user_id ? (int) $laravelUser->wp_user_id : null;

        return $this->sync();
    }

    /**
     * -------------------------------------------------
     * WordPress side
     * -------------------------------------------------
     */

    /**
     * Create or update a WordPress user record.
     *
     * Prefers matching by Laravel user's wp_user_id (if present) to avoid duplicates
     * when email changes. Falls back to email match.
     */
    protected function wpUpsertUser(string $email, User $laravelUser): object {
        $existing = $this->wpFindUser($laravelUser, $email);

        $first = (string) ($laravelUser->first_name ?? '');
        $last = (string) ($laravelUser->last_name ?? '');
        $displayName = trim($first . ' ' . $last);

        if ($displayName === '') {
            $displayName = (string) ($laravelUser->name ?? Str::before($email, '@'));
        }

        // Only generate a login for new users
        $userLogin = $existing
          ? (string) ($existing->user_login ?? '')
          : $this->wpMakeUserLogin($laravelUser, $email);

        if ($userLogin === '') {
            $userLogin = $this->wpMakeUserLogin($laravelUser, $email);
        }

        $payload = [
            'user_email' => $email,
            'display_name' => $displayName,
            'user_nicename' => Str::slug($displayName) ?: $userLogin,
        ];

        if ($existing) {
            $id = (int) $existing->ID;

            $needsUpdate = false;
            foreach ($payload as $key => $value) {
                if (($existing->{$key} ?? null) !== $value) {
                    $needsUpdate = true;
                    break;
                }
            }

            if ($needsUpdate) {
                $this->wpUsersTable()->where('ID', $id)->update($payload);

                foreach ($payload as $key => $value) {
                    $existing->{$key} = $value;
                }
            }

            return $existing;
        }

        $id = (int) $this->wpUsersTable()->insertGetId([
            'user_login' => $userLogin,
            'user_pass' => Str::random(32), // placeholder
            'user_nicename' => $payload['user_nicename'],
            'user_email' => $email,
            'display_name' => $displayName,
            'user_registered' => now()->toDateTimeString(),
        ]);

        return $this->wpUsersTable()->where('ID', $id)->first();
    }

    /**
     * Find a WP user by wp_user_id (preferred), otherwise by email.
     */
    protected function wpFindUser(User $laravelUser, string $email): ?object {
        $wpUserId = $laravelUser->wp_user_id ? (int) $laravelUser->wp_user_id : null;

        if ($wpUserId) {
            $byId = $this->wpUsersTable()->where('ID', $wpUserId)->first();
            if ($byId) {
                return $byId;
            }
        }

        return $this->wpUsersTable()->where('user_email', $email)->first();
    }

    /**
     * Resolve WordPress user ID by email.
     */
    protected function wpUserIdByEmail(string $email): ?int {
        $id = $this->wpUsersTable()
            ->where('user_email', $email)
            ->value('ID');

        return $id ? (int) $id : null;
    }

    /**
     * Store the Laravel user ID in WP usermeta.
     */
    protected function wpSyncLaravelUserId(int $wpUserId): void {
        $this->wpSetUserMeta($wpUserId, 'laravel_user_id', (string) $this->laravelUser->id);
    }

    /**
     * Read the user's wp_capabilities meta and return it as an array.
     */
    protected function wpGetCapabilities(int $wpUserId): array {
        $meta = $this->wpUsermetaTable()
            ->where('user_id', $wpUserId)
            ->where('meta_key', $this->wpMetaKey('capabilities'))
            ->value('meta_value');

        if (! $meta) {
            return [];
        }

        $value = $this->maybeUnserialize((string) $meta);

        return is_array($value) ? $value : [];
    }

    /**
     * Extract roles from capabilities by intersecting capability keys with WP's role registry.
     */
    protected function wpExtractRolesFromCapabilities(array $capabilities): array {
        if (! count($capabilities)) {
            return [];
        }

        $registry = $this->wpGetRolesRegistry();
        if (! count($registry)) {
            return [];
        }

        $roles = [];

        foreach ($capabilities as $key => $enabled) {
            if ($enabled === true && isset($registry[(string) $key])) {
                $roles[] = (string) $key;
            }
        }

        return array_values(array_unique($roles));
    }

    /**
     * Seed roles onto a WP user by updating the wp_capabilities meta.
     * Adds roles but does not remove existing roles.
     */
    protected function wpSetRolesOnUser(int $wpUserId, array $roles): void {
        $roles = array_values(array_unique(array_filter(array_map('strval', $roles))));
        if (! count($roles)) {
            return;
        }

        $existing = $this->wpGetCapabilities($wpUserId);
        $next = $existing;

        foreach ($roles as $role) {
            $next[$role] = true;
        }

        if ($next !== $existing) {
            $this->wpSetUserMeta(
                $wpUserId,
                $this->wpMetaKey('capabilities'),
                $this->maybeSerialize($next)
            );
        }

        // user_level is optional; set only if missing
        $hasUserLevel = $this->wpUsermetaTable()
            ->where('user_id', $wpUserId)
            ->where('meta_key', $this->wpMetaKey('user_level'))
            ->exists();

        if (! $hasUserLevel) {
            $this->wpSetUserMeta($wpUserId, $this->wpMetaKey('user_level'), '0');
        }
    }

    /**
     * Insert or update a WordPress usermeta record.
     */
    protected function wpSetUserMeta(int $wpUserId, string $metaKey, string $metaValue): void {
        $query = $this->wpUsermetaTable()
            ->where('user_id', $wpUserId)
            ->where('meta_key', $metaKey);

        if ($query->exists()) {
            $query->update(['meta_value' => $metaValue]);

            return;
        }

        $this->wpUsermetaTable()->insert([
            'user_id' => $wpUserId,
            'meta_key' => $metaKey,
            'meta_value' => $metaValue,
        ]);
    }

    /**
     * Build a prefixed WordPress meta key (e.g. wp_capabilities).
     */
    protected function wpMetaKey(string $suffix): string {
        return $this->prefix . $suffix;
    }

    /**
     * Load WordPress role definitions from wp_options.
     */
    protected function wpGetRolesRegistry(): array {
        $optionName = $this->prefix . 'user_roles';

        $raw = $this->WPDB
            ->table('options')
            ->where('option_name', $optionName)
            ->value('option_value');

        if (! $raw) {
            return [];
        }

        $roles = $this->maybeUnserialize((string) $raw);

        return is_array($roles) ? $roles : [];
    }

    /**
     * Generate a valid and unique WordPress user_login value.
     */
    protected function wpMakeUserLogin(User $laravelUser, string $email): string {
        $candidate = (string) ($laravelUser->username ?? '');
        if ($candidate !== '') {
            return $this->wpEnsureUniqueUserLogin($candidate);
        }

        $base = preg_replace('/[^a-zA-Z0-9._-]/', '', (string) Str::before($email, '@')) ?: 'user';

        return $this->wpEnsureUniqueUserLogin($base);
    }

    /**
     * Ensure the WordPress user_login is unique and never exceeds VARCHAR(60).
     */
    protected function wpEnsureUniqueUserLogin(string $base): string {
        $maxLength = 60;

        $base = strtolower($base);
        $base = substr($base, 0, $maxLength);

        $login = $base;
        $i = 1;

        while ($this->wpUsersTable()->where('user_login', $login)->exists()) {
            $suffix = (string) $i;
            $trimLength = $maxLength - strlen($suffix);
            $login = substr($base, 0, $trimLength) . $suffix;
            $i++;
        }

        return $login;
    }

    protected function wpUsersTable() {
        return $this->WPDB->table('users');
    }

    protected function wpUsermetaTable() {
        return $this->WPDB->table('usermeta');
    }

    /**
     * -------------------------------------------------
     * Laravel side
     * -------------------------------------------------
     */

    /**
     * Persist wp_user_id + wp_roles + wp_user_login back to Laravel.
     */
    protected function laravelSyncColumns(int $wpUserId, array $roles, object $wpUser): void {
        $roles = array_values(array_unique(array_map('strval', $roles)));

        $this->laravelUser->forceFill([
            'wp_user_id' => $wpUserId,
            'wp_roles' => $roles,
            'wp_user_login' => $wpUser->user_login ?? null,
        ]);

        if ($this->laravelUser->isDirty(['wp_user_id', 'wp_roles', 'wp_user_login'])) {
            $this->laravelUser->save();
        }
    }

    /**
     * -------------------------------------------------
     * Serialization helpers
     * -------------------------------------------------
     */
    protected function maybeSerialize(mixed $value): string {
        if (function_exists('maybe_serialize')) {
            return (string) maybe_serialize($value);
        }

        if (is_array($value) || is_object($value)) {
            return serialize($value);
        }

        return (string) $value;
    }

    protected function maybeUnserialize(string $value): mixed {
        if (function_exists('maybe_unserialize')) {
            return maybe_unserialize($value);
        }

        $trim = trim($value);

        if ($trim === 'N;') {
            return null;
        }

        if (preg_match('/^(a|O|s|i|d|b):/', $trim) === 1) {
            try {
                return unserialize($value);
            } catch (\Throwable $e) {
                return $value;
            }
        }

        return $value;
    }

    /**
     * Log the user into WordPress
     */
    public function login(): bool {
        if (! $this->wpUserId) {
            log_info('WpUserSync@login WP login skipped: wp_user_id missing', [
                'laravel_user_id' => $this->laravelUser->id,
            ]);

            return false;
        }

        if (
            ! function_exists('wp_set_current_user') ||
            ! function_exists('wp_set_auth_cookie')
        ) {
            log_info('WpUserSync@login WP login skipped: WordPress auth functions unavailable');

            return false;
        }

        wp_set_current_user($this->wpUserId);
        wp_set_auth_cookie($this->wpUserId, true, is_ssl());

        /**
         * Let WP know a login just happened
         */
        do_action('wp_login', get_userdata($this->wpUserId)->user_login, get_userdata($this->wpUserId));

        log_info('WpUserSync@login WordPress user logged in', [
            'wp_user_id' => $this->wpUserId,
            'laravel_user_id' => $this->laravelUser->id,
        ]);

        return true;
    }
}
