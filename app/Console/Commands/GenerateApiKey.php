<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateApiKey extends Command {
    /**
     * The name and signature of the console command.
     *
     * --length  Desired length of the generated key (default: 64)
     * --hex     Generate a hex-only key instead of base64url
     */
    protected $signature = 'generate:api-key
    {--length=64 : Length of the key}
    {--hex : Generate a hex-encoded key}';

    /**
     * Command description.
     *
     * This command generates a high-entropy secret suitable for:
     * - Plain API key authentication
     * - HMAC webhook signatures
     * - Internal service-to-service secrets
     */
    protected $description = 'Generate a cryptographically secure key for API keys, webhooks, or internal use';

    public function handle(): int {
        $length = (int) $this->option('length');
        $hex = (bool) $this->option('hex');

        if ($length <= 0) {
            $this->error('Length must be greater than 0.');

            return self::FAILURE;
        }

        if ($hex) {
            /**
             * Hex-encoded key
             *
             * - Uses random_bytes() for cryptographic security
             * - bin2hex() doubles the byte length, so we generate
             *   length / 2 bytes and trim to the requested length
             * - Useful for environments that prefer hex-only secrets
             */
            $bytes = (int) ceil($length / 2);
            $key = substr(bin2hex(random_bytes($bytes)), 0, $length);
        } else {
            /**
             * Base64url-encoded key (no padding)
             *
             * - URL-safe and header-safe
             * - Higher entropy per character than hex
             * - Ideal for API keys, webhook secrets, and headers
             */
            $bytes = (int) ceil($length * 3 / 4) + 2;
            $raw = random_bytes($bytes);
            $b64 = rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
            $key = substr($b64, 0, $length);
        }

        $this->newLine();
        $this->info('API key:');
        $this->line($key);
        $this->newLine();

        // $this->comment('Tip: store this in your .env file, not in source control.');
        return self::SUCCESS;
    }
}
