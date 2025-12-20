<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Log;
use App\Services\WpUserSync;

class SocialAuthController extends Controller {
  /**
   * Redirect to provider
   */
  public function redirect(string $provider): RedirectResponse {
    // Validate provider
    if (! $this->isValidProvider($provider)) {
      return redirect()->route('login')
        ->withErrors(['error' => $this->getErrorMessage('invalid_provider')]);
    }

    // Get scopes from config
    $scopes = config("social.scopes.{$provider}", []);

    // Redirect with scopes
    $driver = Socialite::driver($provider);

    if (! empty($scopes)) {
      $driver->scopes($scopes);
    }

    return $driver->redirect();
  }

  /**
   * Handle provider callback
   */
  public function callback(string $provider): RedirectResponse {
    // Validate provider
    if (! $this->isValidProvider($provider)) {
      return redirect()->route('login')
        ->withErrors(['error' => $this->getErrorMessage('invalid_provider')]);
    }

    try {
      $socialUser = Socialite::driver($provider)->user();
    } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
      // User likely hit back button or session expired
      return redirect()->route('login')
        ->withErrors(['error' => $this->getErrorMessage('auth_failed', $provider)]);
    } catch (\GuzzleHttp\Exception\ClientException $e) {
      // User denied access
      if (str_contains($e->getMessage(), 'access_denied')) {
        return redirect()->route('login')
          ->withErrors(['error' => $this->getErrorMessage('access_denied')]);
      }

      return redirect()->route('login')
        ->withErrors(['error' => $this->getErrorMessage('auth_failed', $provider)]);
    } catch (\Exception $e) {
      // Generic error
      return redirect()->route('login')
        ->withErrors(['error' => $this->getErrorMessage('auth_failed', $provider)]);
    }

    // Handle account linking
    $user = $this->handleAccountLinking($socialUser, $provider);

    if (! $user) {
      return redirect()->route('login')
        ->withErrors(['error' => $this->getErrorMessage('account_exists', $provider)]);
    }

    // Sync Laravel user -> WP user + update wp_* columns on Laravel user
    try {
      // app(WpUserSync::class, ['laravelUser' => $user])->sync();
      $WpUserSync = app(\App\Services\WpUserSync::class, [
        'laravelUser' => $user
      ]);
      $WpUserSync->sync();
      $WpUserSync->login();
    } catch (\Throwable $e) {
      // Decide if you want to fail open (login anyway) or fail closed.
      // For now, fail open:
      logger()->warning('WP user sync failed during social auth', [
        'user_id' => $user->id,
        'email' => $user->email,
        'provider' => $provider,
        'error' => $e->getMessage(),
      ]);
    }

    // Log the user in
    auth()->login($user, true);

    return redirect()->intended(route('dashboard'));
  }

  /**
   * Handle account linking based on config strategy
   */
  protected function handleAccountLinking($socialUser, string $provider): ?User {
    $email    = $socialUser->getEmail();
    $strategy = config('social.account_linking', 'link');

    // Check if user exists
    $existingUser = User::where('email', $email)->first();

    if ($existingUser) {
      // Account exists - handle based on strategy
      switch ($strategy) {
      case 'link':
        // Link social account to existing user
        $existingUser->update([
          'social_provider'    => $provider,
          'social_provider_id' => $socialUser->getId(),
          'social_avatar_url'  => $socialUser->getAvatar(),
        ]);
        return $existingUser;

      case 'error':
        // Return null to trigger error
        return null;

      case 'overwrite':
        // Overwrite existing account (dangerous!)
        $existingUser->update([
          'name'               => $socialUser->getName() ?? $socialUser->getNickname(),
          'password'           => bcrypt(Str::random(32)),
          'social_provider'    => $provider,
          'social_provider_id' => $socialUser->getId(),
          'social_avatar_url'  => $socialUser->getAvatar(),
          'email_verified_at'  => now(),
        ]);
        return $existingUser;
      }
    }

    // New user - create them
    return User::create([
      'name'               => $socialUser->getName() ?? $socialUser->getNickname(),
      'email'              => $email,
      'email_verified_at'  => now(),
      'password'           => bcrypt(Str::random(32)),
      'social_provider'    => $provider,
      'social_provider_id' => $socialUser->getId(),
      'social_avatar_url'  => $socialUser->getAvatar(),
    ]);
  }

  /**
   * Check if provider is valid/enabled
   */
  protected function isValidProvider(string $provider): bool {
    return in_array($provider, config('social.providers', []));
  }

  /**
   * Get error message from config
   */
  protected function getErrorMessage(string $key, ?string $provider = null): string {
    $message = config("social.errors.{$key}", 'An error occurred.');

    if ($provider) {
      $message = str_replace(':provider', ucfirst($provider), $message);
    }

    return $message;
  }
}

