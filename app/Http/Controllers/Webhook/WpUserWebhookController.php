<?php
namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WpUserWebhookController extends Controller
{
  public function handle(Request $request)
  {
    $event = $request->input('event');

    // Route to appropriate handler based on event type
    if ($event === 'profile_update') {
      return $this->handleProfileUpdate($request);
    }

    if ($event === 'user_logout') {
      return $this->handleUserLogout($request);
    }

    return response()->json([
      'success' => false,
      'message' => 'Unknown event type',
    ], 400);
  }

  /**
   * Handle profile_update event from WordPress
   */
  protected function handleProfileUpdate(Request $request)
  {
    $data = $request->validate([
      'event' => ['required', 'string'],
      'wp_user_id' => ['required', 'integer'],
      'wp_user_login' => ['nullable', 'string', 'max:60'],
      'wp_roles' => ['nullable', 'array'],
      'wp_roles.*' => ['string'],
      'laravel_user_id' => ['nullable', 'integer'],
      'occurred_at' => ['nullable', 'string'],
    ]);

    // Prefer an explicit Laravel user link if WP has it stored.
    $user = null;
    if (!empty($data['laravel_user_id'])) {
      $user = User::query()->find($data['laravel_user_id']);
    }

    // Otherwise, find by wp_user_id if previously linked.
    if (!$user) {
      $user = User::query()->where('wp_user_id', (int) $data['wp_user_id'])->first();
    }

    // If we can't find the user, accept the event but do nothing (for now).
    if (!$user) {
      return response()->json([
        'ok' => true,
        'note' => 'user-not-found',
      ], 202);
    }

    $wpRoles = $data['wp_roles'] ?? null;
    if (is_array($wpRoles)) {
      $wpRoles = array_values(array_unique(array_map('strval', $wpRoles)));
    }

    $user->forceFill([
      'wp_user_id' => (int) $data['wp_user_id'],
      'wp_user_login' => $data['wp_user_login'] ?? $user->wp_user_login,
      'wp_roles' => $wpRoles ?? $user->wp_roles,
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
  protected function handleUserLogout(Request $request)
  {
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
    if (!empty($data['laravel_user_id'])) {
      $user = User::query()->find($data['laravel_user_id']);
    }

    // Otherwise, find by wp_user_id if previously linked
    if (!$user) {
      $user = User::query()->where('wp_user_id', (int) $data['wp_user_id'])->first();
    }

    // Fallback to email if provided
    if (!$user && !empty($data['email'])) {
      $user = User::query()->where('email', $data['email'])->first();
    }

    // If we can't find the user, log it and return
    if (!$user) {
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
}
