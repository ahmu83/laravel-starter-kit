<?php
namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WpUserWebhookController extends Controller {

  public function test(Request $request) {
    return response()->json([
      'status' => 'ok',
      'message' => 'Webhook test endpoint',
    ]);
  }

  public function handle(Request $request) {
    Log::info('[WPLL] WpUserWebhookController@handle starting...', [
      'payload' => $request->all(),
    ]);

    // Validate all fields that WordPress sends
    $data = $request->validate([
      'event'           => ['required', 'string'],
      'wp_user_id'      => ['required', 'integer'],
      'laravel_user_id' => ['nullable', 'integer'],
      'wp_user_login'   => ['nullable', 'string', 'max:60'],
      'wp_roles'        => ['nullable', 'array'],
      'wp_roles.*'      => ['string'],
      'first_name'      => ['nullable', 'string', 'max:255'],
      'last_name'       => ['nullable', 'string', 'max:255'],
      'email'           => ['nullable', 'email', 'max:255'],
      'occurred_at'     => ['nullable', 'string'],
    ]);

    Log::info('[WPLL] Webhook data validated', [
      'event'           => $data['event'],
      'wp_user_id'      => $data['wp_user_id'],
      'laravel_user_id' => $data['laravel_user_id'] ?? null,
    ]);

    // Find user - try Laravel ID first, then WordPress ID
    $user = $this->findUser($data);

    // User not found - return 202 Accepted (non-error response)
    if (!$user) {
      Log::warning('[WPLL] No user found for webhook event', [
        'event'           => $data['event'],
        'wp_user_id'      => $data['wp_user_id'],
        'laravel_user_id' => $data['laravel_user_id'] ?? null,
      ]);

      return response()->json([
        'ok'   => true,
        'note' => 'user-not-found',
      ], 202);
    }

    // Update user with provided data
    $this->updateUser($user, $data);

    Log::info('[WPLL] User updated successfully', [
      'user_id' => $user->id,
      'event'   => $data['event'],
    ]);

    return response()->json(['ok' => true]);
  }

  /**
   * Find user by Laravel ID or WordPress ID
   */
  protected function findUser(array $data): ?User {
    $user = null;

    // Try to find user by Laravel ID first (more reliable)
    if (!empty($data['laravel_user_id'])) {
      $user = User::query()->find($data['laravel_user_id']);

      Log::info('[WPLL] User lookup by laravel_user_id', [
        'laravel_user_id' => $data['laravel_user_id'],
        'found'           => $user ? 'yes' : 'no',
        'user_id'         => $user?->id,
      ]);
    }

    // Fall back to WordPress ID if Laravel ID didn't work
    if (!$user && !empty($data['wp_user_id'])) {
      $user = User::query()
        ->where('wp_user_id', (int) $data['wp_user_id'])
        ->first();

      Log::info('[WPLL] User lookup by wp_user_id', [
        'wp_user_id' => $data['wp_user_id'],
        'found'      => $user ? 'yes' : 'no',
        'user_id'    => $user?->id,
      ]);
    }

    return $user;
  }

  /**
   * Update user with validated data
   */
  protected function updateUser(User $user, array $data): void {
    // Prepare wp_roles
    $wpRoles = $data['wp_roles'] ?? null;
    if (is_array($wpRoles)) {
      $wpRoles = array_values(array_unique(array_filter($wpRoles)));
    }

    // Build updates array
    $updates = [
      'wp_user_id'    => (int) $data['wp_user_id'],
      'wp_user_login' => $data['wp_user_login'] ?? $user->wp_user_login,
      'wp_roles'      => $wpRoles ?? $user->wp_roles,
    ];

    // Update first_name if provided
    if (!empty($data['first_name'])) {
      $updates['first_name'] = $data['first_name'];
    }

    // Update last_name if provided
    if (!empty($data['last_name'])) {
      $updates['last_name'] = $data['last_name'];
    }

    // Update email if provided
    if (!empty($data['email'])) {
      $updates['email'] = $data['email'];
    }

    // Build full name from first_name and last_name
    if (!empty($data['first_name']) || !empty($data['last_name'])) {
      $firstName = $data['first_name'] ?? '';
      $lastName = $data['last_name'] ?? '';
      $updates['name'] = trim($firstName . ' ' . $lastName);
    }

    Log::info('[WPLL] Updating user', [
      'user_id' => $user->id,
      'updates' => $updates,
    ]);

    // Use forceFill to bypass mass assignment protection
    $user->forceFill($updates)->save();
  }
}
