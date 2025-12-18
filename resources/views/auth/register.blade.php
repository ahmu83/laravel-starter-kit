{{-- resources/views/auth/register.blade.php --}}
<x-guest-layout>
  {{-- Social Login Buttons --}}
  <x-social-login-buttons divider-text="Sign up with" />

  {{-- Divider --}}
  <div class="relative mt-4 mb-5">
    <div class="absolute inset-0 flex items-center">
      <div class="w-full border-t border-gray-300"></div>
    </div>
    <div class="relative flex justify-center text-sm">
      <span class="px-2 bg-white text-gray-500 font-black text-[11px] uppercase">
        Or continue with
      </span>
    </div>
  </div>

  @php
    $emailRegisterEnabled = config('social.email_register_enabled', true);
  @endphp

  <div class="relative">

    @if (!$emailRegisterEnabled)
      <div class="absolute -top-2 right-0 z-20">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-gray-200 text-gray-700">
          Coming soon
        </span>
      </div>
      <div class="absolute inset-0 bg-white/30 z-10 cursor-not-allowed rounded-lg"></div>
    @endif

    <form method="POST" action="{{ route('register') }}">
      @csrf

      <fieldset {{ !$emailRegisterEnabled ? 'disabled' : '' }} class="{{ !$emailRegisterEnabled ? 'opacity-50 pointer-events-none' : '' }}">
      {{-- <fieldset> --}}

        <!-- Name -->
        <div>
          <x-input-label for="name" :value="__('Name')" />
          <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
          <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
          <x-input-label for="email" :value="__('Email')" />
          <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
          <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
          <x-input-label for="password" :value="__('Password')" />
          <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
          <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
          <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
          <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
          <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
          <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 hidden" href="{{ route('login') }}">
            {{ __('Already registered?') }}
          </a>
          <x-primary-button class="ms-4">
            {{ __('Register') }}
          </x-primary-button>
        </div>

      </fieldset>

    </form>

  </div>

  {{-- Divider --}}
  <div class="relative mt-4 mb-5">
    <div class="absolute inset-0 flex items-center">
      <div class="w-full border-t border-gray-300"></div>
    </div>
    <div class="relative flex justify-center text-sm">
      <span class="px-2 bg-white text-gray-500 font-black text-[11px] uppercase">
        Quick links
      </span>
    </div>
  </div>

  {{-- Account Links (Horizontal Layout) --}}
  <div class="flex items-center justify-center gap-4 text-sm">
    <a class="text-gray-600 hover:text-gray-900 hover:underline focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 rounded-md" href="{{ route('login') }}">
      {{ __('Login') }}
    </a>

    <span class="text-gray-300">|</span>

    <a class="text-gray-600 hover:text-gray-900 hover:underline focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 rounded-md" href="{{ route('password.request') }}">
      {{ __('Forgot password?') }}
    </a>
  </div>

</x-guest-layout>
