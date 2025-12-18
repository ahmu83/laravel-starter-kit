<?php
// app/View/Components/SocialLoginButtons.php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class SocialLoginButtons extends Component {
  public string $dividerText;
  public array $providers;
  public array $activeProviders;

  public function __construct(string $dividerText = 'LOGIN WITH') {
    $this->dividerText     = $dividerText;
    $this->providers       = config('social.providers', ['google', 'github']);
    $this->activeProviders = config('social.active', ['google']);
  }

  public function render(): View {
    return view('components.social-login.buttons'); // Updated path with dot notation
  }
}
