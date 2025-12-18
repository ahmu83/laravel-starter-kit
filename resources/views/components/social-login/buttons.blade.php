{{-- resources/views/components/social-login/buttons.blade.php --}}
<div class="social-login-container">

  {{-- Divider --}}
  <div class="relative mt-1 mb-4">
    <div class="absolute inset-0 flex items-center">
      <div class="w-full border-t border-gray-300"></div>
    </div>
    <div class="relative flex justify-center text-sm">
      <span class="px-2 bg-white text-gray-500 font-black text-[11px] uppercase">
        {{ $dividerText }}
      </span>
    </div>
  </div>

  {{-- Social Buttons - 2 per row --}}
  <div class="grid grid-cols-2 gap-3">

    {{-- Email Login Button --}}
    @include('components.social-login.email')

    {{-- Loop through providers --}}
    @foreach ($providers as $provider)
      @php
        $isActive = in_array($provider, $activeProviders);
        $buttonClass = $isActive
          ? 'flex items-center justify-start px-4 py-2 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2'
          : 'flex items-center justify-start px-4 py-2 border border-gray-200 rounded-md shadow-sm bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed relative group';
      @endphp

      @includeWhen($provider === 'google', 'components.social-login.google')
      @includeWhen($provider === 'github', 'components.social-login.github')
      @includeWhen($provider === 'facebook', 'components.social-login.facebook')
      @includeWhen($provider === 'twitter', 'components.social-login.twitter')
      @includeWhen($provider === 'linkedin', 'components.social-login.linkedin')
      @includeWhen($provider === 'microsoft', 'components.social-login.microsoft')
      @includeWhen($provider === 'apple', 'components.social-login.apple')

    @endforeach
  </div>
</div>
