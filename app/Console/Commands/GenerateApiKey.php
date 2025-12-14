<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateApiKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:api-key
                            {--copy : Copy the key to clipboard}
                            {--show-all : Generate 3 keys at once}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a secure API key for api_auth middleware';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('show-all')) {
            $this->info('Generated API Keys:');
            $this->newLine();
            for ($i = 1; $i <= 3; $i++) {
                $key = bin2hex(random_bytes(32));
                $this->line("API_KEY_{$i}={$key}");
            }
            $this->newLine();
            $this->info('Copy these to your .env file');
        } else {
            $key = bin2hex(random_bytes(32));

            $this->newLine();
            $this->info('Generated API Key:');
            $this->line("API_KEY_1={$key}");
            $this->newLine();

            if ($this->option('copy')) {
                $this->copyToClipboard($key);
                $this->info('✓ Key copied to clipboard!');
            } else {
                $this->comment('Tip: Use --copy to copy the key to clipboard');
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Copy text to clipboard (works on macOS, Linux, Windows)
     */
    private function copyToClipboard(string $text): void
    {
        $os = PHP_OS_FAMILY;

        try {
            if ($os === 'Darwin') {
                // macOS
                $process = popen('pbcopy', 'w');
            } elseif ($os === 'Linux') {
                // Linux (requires xclip)
                $process = popen('xclip -selection clipboard', 'w');
            } elseif ($os === 'Windows') {
                // Windows
                $process = popen('clip', 'w');
            } else {
                return;
            }

            if ($process) {
                fwrite($process, $text);
                pclose($process);
            }
        } catch (\Exception $e) {
            // Silently fail if clipboard not available
        }
    }
}
