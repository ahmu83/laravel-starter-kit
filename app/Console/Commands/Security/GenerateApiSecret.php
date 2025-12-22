<?php

namespace App\Console\Commands\Security;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateApiSecret extends Command
{
  /**
   * The name and signature of the console command.
   */
  protected $signature = 'generate:api-secret
    {--length=64 : Length of the secret}
    {--hex : Generate a hex-encoded secret}';

  /**
   * The console command description.
   */
  protected $description = 'Generate a cryptographically secure secret for API keys, webhooks, or internal use';

  public function handle(): int
  {
    $length = (int) $this->option('length');
    $hex = (bool) $this->option('hex');

    if ($length <= 0) {
      $this->error('Length must be greater than 0.');
      return self::FAILURE;
    }

    if ($hex) {
      // hex string => length / 2 bytes
      $bytes = (int) ceil($length / 2);
      $secret = bin2hex(random_bytes($bytes));
      $secret = substr($secret, 0, $length);
    } else {
      $secret = Str::random($length);
    }

    $this->line('');
    $this->info('Generated secret:');
    $this->line($secret);
    $this->line('');

    $this->comment('Tip: store this in your .env file, not in source control.');
    return self::SUCCESS;
  }
}
