<x-guest-layout>

  {{-- Social Login Buttons --}}
  <x-social-login-buttons divider-text="Recover account with" />

  {{-- Divider --}}
  <div class="relative mt-4 mb-9">
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
    $emailLoginEnabled = config('social.email_login_enabled', true);
  @endphp

  <div class="relative">

    @if (!$emailLoginEnabled)
      <div class="absolute -top-2 right-0 z-20 -mt-6">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-gray-200 text-gray-700">
          Coming soon
        </span>
      </div>
      <div class="absolute inset-0 bg-white/30 z-10 cursor-not-allowed rounded-lg"></div>
    @endif

    <div class="mb-4 text-sm text-gray-600">
      {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
      @csrf
      <fieldset {{ !$emailLoginEnabled ? 'disabled' : '' }} class="{{ !$emailLoginEnabled ? 'opacity-50 pointer-events-none' : '' }}">
        <!-- Email Address -->
        <div>
          <x-input-label for="email" :value="__('Email')" />
          <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
          <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div class="flex items-center justify-end mt-4">
          <x-primary-button>
            {{ __('Email Password Reset Link') }}
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

    <a class="text-gray-600 hover:text-gray-900 hover:underline focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 rounded-md" href="{{ route('register') }}">
      {{ __('Register') }}
    </a>

  </div>

</x-guest-layout>
