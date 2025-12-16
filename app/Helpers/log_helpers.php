<?php

if (!function_exists('normalize_log_context')) :
  /**
   * Ensures the return value is always an array suitable for logging.
   *
   * @param mixed  $var  The variable to normalize (optional).
   * @param string $name Optional key name to wrap the value with.
   * @return array
   */
  function normalize_log_context(mixed $var = null, string $name = ''): array {
    // Special-case: null or "empty" inputs
    if (
      $var === null ||
      (is_string($var) && $var === '') ||
      (is_array($var) && $var === [])
    ) {
      return $name === '' ? [] : [$name => $var];
    }

    // Normalize objects
    if (is_object($var)) {
      if (method_exists($var, 'toArray')) {
        $var = $var->toArray();
      } elseif ($var instanceof JsonSerializable) {
        $var = $var->jsonSerialize();
      } elseif (method_exists($var, '__toString')) {
        $var = (string) $var;
      } else {
        $var = get_class($var);
      }
    }

    // Arrays
    if (is_array($var)) {
      return $name === '' ? $var : [$name => $var];
    }

    // Scalars / everything else
    return $name === '' ? ['value' => $var] : [$name => $var];
  }
endif;

if (!function_exists('add_user_log_context')) :
  /**
   * Add authenticated user context to log message
   */
  function add_user_log_context(string $message): string {
    if (!auth()->check()) {
      return $message;
    }

    $user = auth()->user();
    return $message . " [uid: {$user->id}]";
  }
endif;

if (!function_exists('log_with_level')) :
  /**
   * Generic logging function to reduce code duplication
   */
  function log_with_level(string $level, string $message, mixed $context = null, ?string $channel = null): void {
    $context = normalize_log_context($context);
    $message = add_user_log_context($message);

    if ($channel) {
      Log::channel($channel)->$level($message, $context);
    } else {
      Log::$level($message, $context);
    }
  }
endif;

if (!function_exists('log_emergency')) {
  function log_emergency(string $message, mixed $context = null, ?string $channel = null): void {
    log_with_level('emergency', $message, $context, $channel);
  }
}

if (!function_exists('log_alert')) {
  function log_alert(string $message, mixed $context = null, ?string $channel = null): void {
    log_with_level('alert', $message, $context, $channel);
  }
}

if (!function_exists('log_critical')) {
  function log_critical(string $message, mixed $context = null, ?string $channel = null): void {
    log_with_level('critical', $message, $context, $channel);
  }
}

if (!function_exists('log_error')) {
  function log_error(string $message, mixed $context = null, ?string $channel = null): void {
    log_with_level('error', $message, $context, $channel);
  }
}

if (!function_exists('log_warning')) {
  function log_warning(string $message, mixed $context = null, ?string $channel = null): void {
    log_with_level('warning', $message, $context, $channel);
  }
}

if (!function_exists('log_notice')) {
  function log_notice(string $message, mixed $context = null, ?string $channel = null): void {
    log_with_level('notice', $message, $context, $channel);
  }
}

if (!function_exists('log_info')) {
  function log_info(string $message, mixed $context = null, ?string $channel = null): void {
    log_with_level('info', $message, $context, $channel);
  }
}

if (!function_exists('log_debug')) {
  function log_debug(string $message, mixed $context = null, ?string $channel = null): void {
    log_with_level('debug', $message, $context, $channel);
  }
}
