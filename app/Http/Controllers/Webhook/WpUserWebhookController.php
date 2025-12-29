<?php

namespace App\Http\Controllers\Webhook;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class WpUserWebhookController extends Controller {
    public function handle(Request $request) {
        $event = $request->input('event');

        // Route to appropriate handler based on event type
        if ($event === 'profile_update') {
            return $this->handleProfileUpdate($request);
        }

        if ($event === 'user_logout') {
            return $this->handleUserLogout($request);
        }

        if ($event === 'user_login') {
            return $this->handleUserLogin($request);
        }

        if ($event === 'user_register') {
            return $this->handleUserRegister($request);
        }

        if ($event === 'user_delete') {
            return $this->handleUserDelete($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unknown event type',
        ], 400);
    }

    /**
     * Handle profile_update event from WordPress
     */
    protected function handleProfileUpdate(Request $request) {
        $data = $request->validate([
            'event' => ['required', 'string'],
            'wp_user_id' => ['required', 'integer'],
            'wp_user_login' => ['nullable', 'string', 'max:60'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'wp_roles' => ['nullable', 'array'],
            'wp_roles.*' => ['string'],
            'laravel_user_id' => ['nullable', 'integer'],
            'occurred_at' => ['nullable', 'string'],
        ]);

        // Prefer an explicit Laravel user link if WP has it stored.
        $user = null;
        if (! empty($data['laravel_user_id'])) {
            $user = User::query()->find($data['laravel_user_id']);
        }

        // Otherwise, find by wp_user_id if previously linked.
        if (! $user) {
            $user = User::query()->where('wp_user_id', (int) $data['wp_user_id'])->first();
        }

        // If we can't find the user, accept the event but do nothing (for now).
        if (! $user) {
            return response()->json([
                'ok' => true,
                'note' => 'user-not-found',
            ], 202);
        }

        $wpRoles = $data['wp_roles'] ?? null;
        if (is_array($wpRoles)) {
            $wpRoles = array_values(array_unique(array_map('strval', $wpRoles)));
        }

        // Construct name from first_name + last_name, or fallback to nickname
        $name = null;
        if (! empty($data['first_name']) || ! empty($data['last_name'])) {
            $name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
        }
        if (empty($name) && ! empty($data['nickname'])) {
            $name = $data['nickname'];
        }
        if (empty($name)) {
            $name = $user->name; // Keep existing name if nothing provided
        }

        $user->forceFill([
            'wp_user_id' => (int) $data['wp_user_id'],
            'wp_user_login' => $data['wp_user_login'] ?? $user->wp_user_login,
            'wp_roles' => $wpRoles ?? $user->wp_roles,
            'name' => $name,
        ])->save();

        return response()->json([
            'ok' => true,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Handle user_logout event from WordPress
     *
     * Invalidates ALL sessions for the user across all devices
     */
    protected function handleUserLogout(Request $request) {
        $data = $request->validate([
            'event' => ['required', 'string'],
            'wp_user_id' => ['required', 'integer'],
            'laravel_user_id' => ['nullable', 'integer'],
            'wp_user_login' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'occurred_at' => ['nullable', 'string'],
        ]);

        // Try to find user by Laravel ID first
        $user = null;
        if (! empty($data['laravel_user_id'])) {
            $user = User::query()->find($data['laravel_user_id']);
        }

        // Otherwise, find by wp_user_id if previously linked
        if (! $user) {
            $user = User::query()->where('wp_user_id', (int) $data['wp_user_id'])->first();
        }

        // Fallback to email if provided
        if (! $user && ! empty($data['email'])) {
            $user = User::query()->where('email', $data['email'])->first();
        }

        // If we can't find the user, log it and return
        if (! $user) {
            Log::warning('User logout webhook: User not found', [
                'laravel_user_id' => $data['laravel_user_id'] ?? null,
                'wp_user_id' => $data['wp_user_id'],
                'email' => $data['email'] ?? null,
            ]);

            return response()->json([
                'ok' => true,
                'note' => 'user-not-found',
            ], 202);
        }

        // Delete ALL sessions for this user from the database
        $deletedCount = DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();

        Log::info('User logged out from WordPress - invalidated Laravel sessions', [
            'user_id' => $user->id,
            'email' => $user->email,
            'wp_user_id' => $data['wp_user_id'],
            'sessions_deleted' => $deletedCount,
        ]);

        return response()->json([
            'ok' => true,
            'sessions_deleted' => $deletedCount,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Handle user_login event from WordPress
     *
     * Creates a Laravel user if they don't exist (WordPress is source of truth)
     * Ensures user record is ready for auto-login via middleware
     */
    protected function handleUserLogin(Request $request) {
        $data = $request->validate([
            'event' => ['required', 'string'],
            'wp_user_id' => ['required', 'integer'],
            'laravel_user_id' => ['nullable', 'integer'],
            'wp_user_login' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'wp_roles' => ['nullable', 'array'],
            'wp_roles.*' => ['string'],
            'occurred_at' => ['nullable', 'string'],
        ]);

        // Try to find user by Laravel ID first
        $user = null;
        if (! empty($data['laravel_user_id'])) {
            $user = User::query()->find($data['laravel_user_id']);
        }

        // Otherwise, find by wp_user_id if previously linked
        if (! $user) {
            $user = User::query()->where('wp_user_id', (int) $data['wp_user_id'])->first();
        }

        // Fallback to email
        if (! $user) {
            $user = User::query()->where('email', $data['email'])->first();
        }

        // User not found - CREATE them (WordPress is source of truth)
        if (! $user) {
            $wpRoles = $data['wp_roles'] ?? [];
            if (is_array($wpRoles)) {
                $wpRoles = array_values(array_unique(array_map('strval', $wpRoles)));
            }

            // Construct name from first_name + last_name, or fallback to nickname/display_name
            $name = null;
            if (! empty($data['first_name']) || ! empty($data['last_name'])) {
                $name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
            }
            if (empty($name) && ! empty($data['display_name'])) {
                $name = $data['display_name'];
            }
            if (empty($name) && ! empty($data['nickname'])) {
                $name = $data['nickname'];
            }
            if (empty($name)) {
                $name = $data['wp_user_login']; // Last resort
            }

            $user = User::create([
                'wp_user_id' => (int) $data['wp_user_id'],
                'wp_user_login' => $data['wp_user_login'],
                'wp_roles' => $wpRoles,
                'name' => $name,
                'email' => $data['email'],
                'password' => bcrypt(\Illuminate\Support\Str::random(32)), // Random password
                'email_verified_at' => now(), // Trust WordPress verification
            ]);

            Log::info('Created new Laravel user from WordPress login', [
                'user_id' => $user->id,
                'wp_user_id' => $data['wp_user_id'],
                'email' => $user->email,
                'name' => $user->name,
            ]);

            return response()->json([
                'ok' => true,
                'user_id' => $user->id,
                'note' => 'user-created',
            ]);
        }

        // User exists - ensure wp_user_id is linked
        if (! $user->wp_user_id) {
            $user->forceFill(['wp_user_id' => (int) $data['wp_user_id']])->save();
        }

        Log::info('User logged in from WordPress - Laravel user ready', [
            'user_id' => $user->id,
            'email' => $user->email,
            'wp_user_id' => $data['wp_user_id'],
        ]);

        return response()->json([
            'ok' => true,
            'user_id' => $user->id,
            'note' => 'user-ready-for-auto-login',
        ]);
    }

    /**
     * Handle user_register event from WordPress
     *
     * Creates a Laravel user immediately when WordPress user is created
     * This can be reused logic from handleUserLogin since both create users
     */
    protected function handleUserRegister(Request $request) {
        $data = $request->validate([
            'event' => ['required', 'string'],
            'wp_user_id' => ['required', 'integer'],
            'laravel_user_id' => ['nullable', 'integer'],
            'wp_user_login' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'wp_roles' => ['nullable', 'array'],
            'wp_roles.*' => ['string'],
            'occurred_at' => ['nullable', 'string'],
        ]);

        // Check if user already exists
        $user = null;
        if (! empty($data['laravel_user_id'])) {
            $user = User::query()->find($data['laravel_user_id']);
        }

        if (! $user) {
            $user = User::query()->where('wp_user_id', (int) $data['wp_user_id'])->first();
        }

        if (! $user) {
            $user = User::query()->where('email', $data['email'])->first();
        }

        // User already exists - just ensure wp_user_id is linked
        if ($user) {
            if (! $user->wp_user_id) {
                $user->forceFill(['wp_user_id' => (int) $data['wp_user_id']])->save();
            }

            Log::info('User register webhook: User already exists', [
                'user_id' => $user->id,
                'wp_user_id' => $data['wp_user_id'],
            ]);

            return response()->json([
                'ok' => true,
                'user_id' => $user->id,
                'note' => 'user-already-exists',
            ]);
        }

        // Create new user
        $wpRoles = $data['wp_roles'] ?? [];
        if (is_array($wpRoles)) {
            $wpRoles = array_values(array_unique(array_map('strval', $wpRoles)));
        }

        // Construct name
        $name = null;
        if (! empty($data['first_name']) || ! empty($data['last_name'])) {
            $name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
        }
        if (empty($name) && ! empty($data['display_name'])) {
            $name = $data['display_name'];
        }
        if (empty($name) && ! empty($data['nickname'])) {
            $name = $data['nickname'];
        }
        if (empty($name)) {
            $name = $data['wp_user_login'];
        }

        $user = User::create([
            'wp_user_id' => (int) $data['wp_user_id'],
            'wp_user_login' => $data['wp_user_login'],
            'wp_roles' => $wpRoles,
            'name' => $name,
            'email' => $data['email'],
            'password' => bcrypt(\Illuminate\Support\Str::random(32)),
            'email_verified_at' => now(),
        ]);

        Log::info('Created new Laravel user from WordPress registration', [
            'user_id' => $user->id,
            'wp_user_id' => $data['wp_user_id'],
            'email' => $user->email,
            'name' => $user->name,
        ]);

        return response()->json([
            'ok' => true,
            'user_id' => $user->id,
            'note' => 'user-created',
        ]);
    }

    /**
     * Handle user_delete event from WordPress
     *
     * Deletes the Laravel user when WordPress user is deleted
     * WordPress is the source of truth, so deletion must cascade
     */
    protected function handleUserDelete(Request $request) {
        $data = $request->validate([
            'event' => ['required', 'string'],
            'wp_user_id' => ['required', 'integer'],
            'laravel_user_id' => ['nullable', 'integer'],
            'wp_user_login' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'occurred_at' => ['nullable', 'string'],
        ]);

        // Try to find user by Laravel ID first
        $user = null;
        if (! empty($data['laravel_user_id'])) {
            $user = User::query()->find($data['laravel_user_id']);
        }

        // Otherwise, find by wp_user_id
        if (! $user) {
            $user = User::query()->where('wp_user_id', (int) $data['wp_user_id'])->first();
        }

        // Fallback to email if provided
        if (! $user && ! empty($data['email'])) {
            $user = User::query()->where('email', $data['email'])->first();
        }

        // If we can't find the user, it's already gone or never existed
        if (! $user) {
            Log::info('User delete webhook: User not found (may already be deleted)', [
                'laravel_user_id' => $data['laravel_user_id'] ?? null,
                'wp_user_id' => $data['wp_user_id'],
                'email' => $data['email'] ?? null,
            ]);

            return response()->json([
                'ok' => true,
                'note' => 'user-not-found',
            ], 202);
        }

        // Delete all sessions first
        $deletedSessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();

        // Delete the user
        $userId = $user->id;
        $userEmail = $user->email;
        $user->delete();

        Log::info('Deleted Laravel user (WordPress user deleted)', [
            'user_id' => $userId,
            'email' => $userEmail,
            'wp_user_id' => $data['wp_user_id'],
            'sessions_deleted' => $deletedSessions,
        ]);

        return response()->json([
            'ok' => true,
            'user_id' => $userId,
            'sessions_deleted' => $deletedSessions,
            'note' => 'user-deleted',
        ]);
    }
}
