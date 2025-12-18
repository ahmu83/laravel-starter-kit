{{-- resources/views/auth/login.blade.php --}}
<x-guest-layout>
  <!-- Session Status -->
  <x-auth-session-status class="mb-4" :status="session('status')" />

  {{-- Social Login Buttons --}}
  <x-social-login-buttons divider-text="Login with" />

  {{-- Divider --}}
  <div class="relative mt-4 mb-5">
    <div class="absolute inset-0 flex items-center">
      <div class="w-full border-t border-gray-300"></div>
    </div>
    <div class="relative flex justify-center text-sm">
      <span class="px-2 bg-white text-gray-500">Or continue with</span>
    </div>
  </div>

  <form method="POST" action="{{ route('login') }}">
    @csrf

    @php
      $emailLoginEnabled = config('social.email_login_enabled', true);
    @endphp

    <fieldset {{ !$emailLoginEnabled ? 'disabled' : '' }} class="{{ !$emailLoginEnabled ? 'opacity-50 pointer-events-none' : '' }}">
    {{-- <fieldset> --}}

      <!-- Email Address -->
      <div>
        <x-input-label for="email" :value="__('Email')" />
        <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
      </div>

      <!-- Password -->
      <div class="mt-4">
        <x-input-label for="password" :value="__('Password')" />
        <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
        <x-input-error :messages="$errors->get('password')" class="mt-2" />
      </div>

      <!-- Remember Me -->
      <div class="block mt-4">
        <label for="remember_me" class="inline-flex items-center">
          <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
          <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
        </label>
      </div>

      <div class="flex items-center justify-end mt-4">
        @if (Route::has('password.request'))
        <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
          {{ __('Forgot your password?') }}
        </a>
        @endif
        <x-primary-button class="ms-3">
          {{ __('Log in') }}
        </x-primary-button>
      </div>

    </fieldset>

  </form>
</x-guest-layout>
