<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class LogsView extends Command {
  /**
   * --lines  : Number of lines to show (default 200)
   * --file   : Specific log file name (default: latest)
   * --grep   : Filter lines by keyword (case-insensitive)
   * --newest : Show newest entries first
   * --group  : Group lines into entries starting with [YYYY-MM-DD HH:MM:SS]
   */
  protected $signature = 'logs:view
    {--lines=200 : Number of lines to display}
    {--file= : Specific log file name}
    {--grep= : Filter output by keyword}
    {--newest : Show newest first}
    {--group : Group lines into log entries}';

  protected $description = 'View Laravel logs in a readable, terminal-friendly way (production-safe)';

  public function handle(): int {
    $logsPath = storage_path('logs');

    if (! is_dir($logsPath)) {
      $this->error('Logs directory does not exist: ' . $logsPath);
      return self::FAILURE;
    }

    $logFile = $this->resolveLogFile($logsPath);

    if (! $logFile) {
      return self::FAILURE;
    }

    $lines = max(1, (int) $this->option('lines'));
    $grep  = $this->option('grep');

    $this->info('Log file: ' . basename($logFile));
    $this->line(str_repeat('-', 60));

    $contentLines = $this->tailFile($logFile, $lines);

    if ($grep) {
      $needle       = Str::lower($grep);
      $contentLines = collect($contentLines)
        ->filter(fn($line) => Str::contains(Str::lower($line), $needle))
        ->values()
        ->all();
    }

    if ($this->option('group')) {
      $entries = $this->groupEntries($contentLines);

      if ($this->option('newest')) {
        $entries = array_reverse($entries);
      }

      foreach ($entries as $entryLines) {
        $this->renderEntry($entryLines);
      }

      return self::SUCCESS;
    }

    if ($this->option('newest')) {
      $contentLines = array_reverse($contentLines);
    }

    foreach ($contentLines as $line) {
      $this->outputLine($line);
    }

    return self::SUCCESS;
  }

  private function resolveLogFile(string $logsPath): ?string {
    $file = $this->option('file');

    if ($file) {
      $path = $logsPath . '/' . ltrim($file, '/');

      if (! file_exists($path)) {
        $this->error("Log file not found: {$path}");
        return null;
      }

      return $path;
    }

    $files = collect(glob($logsPath . '/*.log'))
      ->sortByDesc(fn($f) => @filemtime($f) ?: 0)
      ->values();

    if ($files->isEmpty()) {
      $this->error('No log files found in: ' . $logsPath);
      return null;
    }

    return $files->first();
  }

  /**
   * Efficiently read last N lines from a file.
   */
  protected function tailFile(string $file, int $lines): array {
    $fp = @fopen($file, 'r');
    if (! $fp) {
      return [];
    }

    $buffer = '';
    fseek($fp, 0, SEEK_END);
    $position = ftell($fp);

    while ($position > 0 && substr_count($buffer, "\n") <= $lines) {
      $chunkSize = min(4096, $position);
      $position -= $chunkSize;
      fseek($fp, $position);
      $buffer = fread($fp, $chunkSize) . $buffer;
    }

    fclose($fp);

    $parts = explode("\n", trim($buffer));
    return array_slice($parts, -$lines);
  }

  /**
   * Group lines into entries starting with a timestamp line like:
   * [2025-12-27 01:12:19] local.ERROR: ...
   */
  private function groupEntries(array $lines): array {
    $entries = [];
    $current = [];

    foreach ($lines as $line) {
      $isStart = (bool) preg_match('/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $line);

      if ($isStart && ! empty($current)) {
        $entries[] = $current;
        $current   = [];
      }

      $current[] = $line;
    }

    if (! empty($current)) {
      $entries[] = $current;
    }

    return $entries;
  }

  private function renderEntry(array $lines): void {
    $this->line(''); // spacing between entries

    $first = $lines[0] ?? '';
    $this->outputLine($first);

    $rest = array_slice($lines, 1);
    foreach ($rest as $line) {
      // Stack lines + JSON context tend to be noisy; keep them gray
      if (Str::startsWith(trim($line), '#') || Str::startsWith(trim($line), '{') || Str::startsWith(trim($line), '"')) {
        $this->line('<fg=gray>' . $this->escapeTags($line) . '</>');
        continue;
      }

      $this->line('<fg=gray>' . $this->escapeTags($line) . '</>');
    }

    $this->line('<fg=gray>' . str_repeat('-', 60) . '</>');
  }

  /**
   * Output a line with minimal but safe formatting.
   */
  protected function outputLine(string $line): void {
    $line = $this->escapeTags($line);

// Match leading [YYYY-MM-DD HH:MM:SS]
    if (preg_match('/^(\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\])\s*(.*)$/', $line, $m)) {
      $rawTimestamp = $m[2];
      $rest         = $m[3];

      try {
        $dt       = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $rawTimestamp);
        $relative = $dt->diffForHumans(null, true); // e.g. "2 minutes"
        $relative = '(' . $relative . ' ago)';
      } catch (\Throwable $e) {
        $relative = '';
      }

      $timestamp = '<fg=gray>' . $m[1] . '</>';
      $relative  = $relative
      ? ' <fg=cyan>' . $relative . '</>'
      : '';

      $line = $timestamp . $relative . ' ' . $rest;
    }

    if (Str::contains($line, ['.CRITICAL', 'CRITICAL'])) {
      $this->line('<fg=red;options=bold>' . $line . '</>');
      return;
    }

    if (Str::contains($line, ['.ERROR', 'ERROR', 'Exception'])) {
      $this->line('<fg=red>' . $line . '</>');
      return;
    }

    if (Str::contains($line, ['.WARNING', 'WARNING'])) {
      $this->line('<fg=yellow>' . $line . '</>');
      return;
    }

    if (Str::contains($line, ['.INFO', 'INFO'])) {
      $this->line('<fg=cyan>' . $line . '</>');
      return;
    }

    $this->line($line);
  }
  /**
   * Prevent Symfony console from treating log content as formatting tags.
   */
  private function escapeTags(string $text): string {
    return str_replace(['<', '>'], ['&lt;', '&gt;'], $text);
  }
}
