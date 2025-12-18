{{-- Twitter/X --}}
@if ($provider === 'twitter')
  <div class="{{ $buttonClass }}">
    <svg class="w-5 h-5 mr-2 opacity-40" fill="#9CA3AF" viewBox="0 0 24 24">
      <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
    </svg>
    Continue with X
    <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs text-white bg-gray-900 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
      Coming Soon
    </span>
  </div>
@endif
