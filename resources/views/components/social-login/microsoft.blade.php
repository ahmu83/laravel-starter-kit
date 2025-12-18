{{-- Microsoft --}}
@if ($provider === 'microsoft')
  <div class="{{ $buttonClass }}">
    <svg class="w-5 h-5 mr-2 opacity-40" viewBox="0 0 23 23">
      <path fill="#D1D5DB" d="M1 1h10v10H1z"/>
      <path fill="#D1D5DB" d="M12 1h10v10H12z"/>
      <path fill="#D1D5DB" d="M1 12h10v10H1z"/>
      <path fill="#D1D5DB" d="M12 12h10v10H12z"/>
    </svg>
    Continue with Microsoft
    <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-1 text-xs text-white bg-gray-900 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
      Coming Soon
    </span>
  </div>
@endif
