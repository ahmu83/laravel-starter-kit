<?php

namespace App\Console\Commands;

use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class LogsView extends Command
{
  /**
   * The name and signature of the console command.
   *
   * --lines : Number of lines to show (default 100)
   * --file  : Specific log file (default: latest)
   * --grep  : Filter lines by keyword (case-insensitive)
   */
  protected $signature = 'logs:view
    {--lines=100 : Number of lines to display}
    {--file= : Specific log file name}
    {--grep= : Filter output by keyword}';

  protected $description = 'View Laravel logs in a readable terminal format';

  public function handle(): int
  {
    $logsPath = storage_path('logs');

    if (! is_dir($logsPath)) {
      $this->error('Logs directory does not exist.');
      return self::FAILURE;
    }

    $file = $this->option('file');

    if ($file) {
      $logFile = $logsPath . '/' . $file;
    } else {
      $files = collect(glob($logsPath . '/*.log'))
        ->sortByDesc(fn ($f) => filemtime($f))
        ->values();

      if ($files->isEmpty()) {
        $this->error('No log files found.');
        return self::FAILURE;
      }

      $logFile = $files->first();
    }

    if (! file_exists($logFile)) {
      $this->error("Log file not found: {$logFile}");
      return self::FAILURE;
    }

    $lines = (int) $this->option('lines');
    $grep  = $this->option('grep');

    $this->info('Log file: ' . basename($logFile));
    $this->hr();

    $content = $this->tailFile($logFile, $lines);

    if ($grep) {
      $content = collect($content)
        ->filter(fn ($line) => Str::contains(Str::lower($line), Str::lower($grep)))
        ->values()
        ->all();
    }

    foreach ($content as $line) {
      $this->outputLine($line);
    }

    return self::SUCCESS;
  }

  protected function hr(int $width = 70): void
  {
    $this->line('<fg=gray>' . str_repeat('─', $width) . '</>');
  }

  /**
   * Efficiently read last N lines from a file.
   */
  protected function tailFile(string $file, int $lines): array
  {
    $buffer = '';
    $fp = fopen($file, 'r');

    if (! $fp) {
      return [];
    }

    fseek($fp, 0, SEEK_END);
    $position = ftell($fp);

    while ($position > 0 && substr_count($buffer, "\n") <= $lines) {
      $chunkSize = min(4096, $position);
      $position -= $chunkSize;
      fseek($fp, $position);
      $buffer = fread($fp, $chunkSize) . $buffer;
    }

    fclose($fp);

    return array_slice(explode("\n", trim($buffer)), -$lines);
  }

  /**
   * Output a single log line with coloring and relative time.
   */
  protected function outputLine(string $line): void
  {
    // Match leading [YYYY-MM-DD HH:MM:SS]
    if (preg_match('/^(\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\])\s*(.*)$/', $line, $m)) {
      $date = DateTime::createFromFormat('Y-m-d H:i:s', $m[2]);
      $relative = $date ? $this->shortRelativeTime($date) : null;

      $timestamp = '<fg=gray>' . $m[1] . '</>';
      $relative  = $relative ? ' <fg=gray>(' . $relative . ')</>' : '';

      $line = $timestamp . $relative . ' ' . $m[3];
    }

    if (Str::contains($line, ['ERROR', 'Exception'])) {
      $this->line('<fg=red>' . $line . '</>');
      return;
    }

    if (Str::contains($line, 'WARNING')) {
      $this->line('<fg=yellow>' . $line . '</>');
      return;
    }

    if (Str::contains($line, 'INFO')) {
      $this->line('<fg=green>' . $line . '</>');
      return;
    }

    $this->line($line);
  }

  /**
   * Short relative time formatter (s, m, h, d).
   */
  protected function shortRelativeTime(DateTime $time): string
  {
    $now  = new DateTime();
    $diff = $now->getTimestamp() - $time->getTimestamp();

    if ($diff < 0) {
      $diff = abs($diff);
      if ($diff < 60) return $diff . 's';
      if ($diff < 3600) return floor($diff / 60) . 'm';
      if ($diff < 86400) return floor($diff / 3600) . 'h';
      return floor($diff / 86400) . 'd';
    }

    if ($diff < 60) {
      return $diff . 's';
    }

    if ($diff < 3600) {
      return floor($diff / 60) . 'm';
    }

    if ($diff < 86400) {
      return floor($diff / 3600) . 'h';
    }

    return floor($diff / 86400) . 'd';
  }
}
