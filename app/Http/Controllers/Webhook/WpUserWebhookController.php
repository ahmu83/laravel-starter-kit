<?php
namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WpUserWebhookController extends Controller {

  public function test(Request $request) {
    echo 123;
  }

  public function handle(Request $request) {
    $data = $this->getValidatedRequestData($request);

    $user = null;

    if (! empty($data['laravel_user_id'])) {
      $user = User::query()->find((int) $data['laravel_user_id']);
    }

    if (! $user) {
      $user = User::query()
        ->where('wp_user_id', (int) $data['wp_user_id'])
        ->first();
    }

    if (! $user) {
      $user = $this->createUser($data);
    } else {
      $this->updateUser($user, $data);
    }

    return response()->json([
      'status'  => 'ok',
      'message' => 'User synced',
      'user_id' => $user->id,
    ]);
  }

  private function getValidatedRequestData(Request $request): array {
    return $request->validate([
      'event'           => ['required', 'string'],
      'wp_user_id'      => ['required', 'integer'],

      // Lookup helper (optional)
      'laravel_user_id' => ['nullable', 'integer'],

      // Data we care about
      'wp_roles'        => ['nullable', 'array'],
      'wp_roles.*'      => ['string'],

      // May be sent, but we will not update these for existing users
      'wp_user_login'   => ['nullable', 'string', 'max:60'],
      'email'           => ['nullable', 'string', 'max:255'],

      // Name sources
      'name'            => ['nullable', 'string', 'max:255'],
      'first_name'      => ['nullable', 'string', 'max:255'],
      'last_name'       => ['nullable', 'string', 'max:255'],

      'occurred_at'     => ['nullable', 'string'],
    ]);
  }

  private function createUser(array $data): User {
    $email = trim((string) ($data['email'] ?? ''));
    if ($email === '') {
      // Your table requires email, so creation must have it.
      abort(422, 'Email is required to create a Laravel user.');
    }

    $name = $this->resolveFullName($data);
    if ($name === '') {
      $name = 'WP User #' . (int) $data['wp_user_id'];
    }

    $user = User::query()->create([
      'wp_user_id'    => (int) $data['wp_user_id'],
      'wp_roles'      => $data['wp_roles'] ?? [],

      // Set these only on create (per your preference)
      'wp_user_login' => $data['wp_user_login'] ?? null,
      'email'         => $email,

      'name'          => $name,

      // Random password so they can't log in unless you later allow it
      'password'      => bcrypt(Str::random(40)),
    ]);

    log_info('WpUserWebhookController@createUser Created Laravel user from WP webhook', [
      'user_id'    => $user->id,
      'wp_user_id' => $user->wp_user_id,
    ]);

    // Ensure roles/name stay consistent with update logic too
    $this->updateUser($user, $data);

    return $user;
  }

  private function updateUser(User $user, array $data): void {
    $updates = [];

    // Keep WP linkage synced
    $updates['wp_user_id'] = (int) $data['wp_user_id'];

    if (array_key_exists('wp_roles', $data)) {
      $updates['wp_roles'] = is_array($data['wp_roles']) ? $data['wp_roles'] : [];
    }

    $name = $this->resolveFullName($data);
    if ($name !== '') {
      $updates['name'] = $name;
    }

    // Intentionally NOT updating for existing users:
    // - wp_user_login
    // - email

    if (empty($updates)) {
      return;
    }

    $user->forceFill($updates)->save();
  }

  private function resolveFullName(array $data): string {
    $name = trim((string) ($data['name'] ?? ''));

    if ($name !== '') {
      return $name;
    }

    $first = trim((string) ($data['first_name'] ?? ''));
    $last  = trim((string) ($data['last_name'] ?? ''));

    return trim($first . ' ' . $last);
  }
}
