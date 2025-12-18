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
      <span class="px-2 bg-white text-gray-500 font-black text-[11px] uppercase tracking-wider">
        Or continue with
      </span>
    </div>
  </div>

  @php
    $emailLoginEnabled = config('social.email_login_enabled', true);
  @endphp

  <div class="relative">
    @if (!$emailLoginEnabled)
      <div class="absolute -top-2 right-0 z-20">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-gray-200 text-gray-700">
          Coming soon
        </span>
      </div>
      <div class="absolute inset-0 bg-white/30 z-10 cursor-not-allowed rounded-lg"></div>
    @endif

    <form method="POST" action="{{ route('login') }}">
      @csrf

      <fieldset {{ !$emailLoginEnabled ? 'disabled' : '' }} class="{{ !$emailLoginEnabled ? 'opacity-50 pointer-events-none' : '' }}">
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
          <x-primary-button class="w-full justify-center">
            {{ __('Log in') }}
          </x-primary-button>
        </div>
      </fieldset>
    </form>
  </div>

  {{-- Account Links Divider --}}
  <div class="relative mt-6 mb-4">
    <div class="absolute inset-0 flex items-center">
      <div class="w-full border-t border-gray-300"></div>
    </div>
    <div class="relative flex justify-center text-sm">
      <span class="px-2 bg-white text-gray-500 font-black text-[11px] uppercase tracking-wider">
        Quick links
      </span>
    </div>
  </div>

  {{-- Account Links (Horizontal Layout) --}}
  <div class="flex items-center justify-center gap-4 text-sm">
    <a class="text-gray-600 hover:text-gray-900 hover:underline focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 rounded-md" href="{{ route('register') }}">
      {{ __('Register') }}
    </a>

    <span class="text-gray-300">|</span>

    <a class="text-gray-600 hover:text-gray-900 hover:underline focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 rounded-md" href="{{ route('password.request') }}">
      {{ __('Forgot password?') }}
    </a>
  </div>

</x-guest-layout>
