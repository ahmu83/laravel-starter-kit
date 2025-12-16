<?php

if (!function_exists('printr')) :
  function printr($var, $title = false, $hide = false, $die = false, $var_export = false) {
    $styles = [
      'pre' => 'color: #a9b7c6; background-color: #2b2b2b; white-space: break-spaces; margin: 12px 20px 12px 12px; padding: 8px 10px 0 10px; border: 1px solid #000; border-radius: 5px; box-sizing: border-box; font-family: Consolas, Monaco, monospace; overflow: hidden;',

      'h2' => 'color: #cc7832; padding:12px 10px 10px; margin:-8px -10px 0; border-bottom: 1px solid #000; font-weight:bold; background-color: #313335; cursor: pointer; font-size: 18px; ',

      'small' => 'color: #6897bb; font-weight:normal; font-size: 14px; ',

      'content' => 'background-color:transparent; margin: 6px 0 10px; font-size: 12px; line-height: 18px; color: white;',
    ];

    $toggle_js = 'onclick="this.closest(\'pre\').querySelector(\'.var-content\').classList.toggle(\'printr-pre-hide\')"';

    // Determine variable type
    $var_type = gettype($var);
    if (is_object($var)) {
      $var_type = '<b>' . get_class($var) . '</b> ' . $var_type;
    } elseif (is_bool($var)) {
      $var_type .= ' - ' . ($var ? 'true' : 'false');
    }

    // Append variable type to title
    $var_type = ' <small style="' . $styles['small'] . '">(' . $var_type . ')</small>';
    $title = $title ? $title : '';
    $title .= $var_type;

    // Format variable content
    $var_content = '';
    switch (gettype($var)) {
      case 'boolean':
        $var_content = $var ? 'true' : 'false';
        break;
      case 'NULL':
        $var_content = 'NULL';
        break;
      default:
        if ($var_export) {
          $var_content = var_export($var, true);
        } else {
          $var_content = printr_format_value($var);
        }
        break;
    }

    echo '<pre style="' . $styles['pre'] . '">';
    echo '<h2 style="' . $styles['h2'] . '" ' . $toggle_js . '>' . $title . '</h2>';
    echo '<div class="var-content ' . ($hide ? 'printr-pre-hide' : '') . '" style="' . $styles['content'] . '">' . $var_content . '</div>';
    echo '<style>.printr-pre-hide{display:none}</style>';
    echo '</pre>';

    if ($die) {
      exit();
    }
  }
endif;

if (!function_exists('printr_format_value')) :
  function printr_format_value($value, $indent = 0) {
    $spacing = str_repeat('  ', $indent);

    if (is_null($value)) {
      return 'null';
    }

    if (is_bool($value)) {
      return $value ? 'true' : 'false';
    }

    if (is_string($value)) {
      return '"' . $value . '"';
    }

    if (is_numeric($value)) {
      return (string) $value;
    }

    if (is_array($value)) {
      if (empty($value)) {
        return '[]';
      }

      $output = "[\n";
      foreach ($value as $key => $val) {
        $output .= $spacing . '  ';
        $output .= is_string($key) ? '"' . $key . '"' : $key;
        $output .= ' => ';
        $output .= printr_format_value($val, $indent + 1);
        $output .= "\n";
      }
      $output .= $spacing . ']';
      return $output;
    }

    if (is_object($value)) {
      return print_r($value, true);
    }

    return print_r($value, true);
  }
endif;

if (!function_exists('printr_die')) :
  function printr_die($var, $title = false, $hide = false, $var_export = false) {
    printr($var, $title, $hide, true, $var_export);
  }
endif;

if (!function_exists('printr_hide')) :
  function printr_hide($var, $title = false, $var_export = false) {
    printr($var, $title, true, false, $var_export);
  }
endif;

if (!function_exists('printr_hide_die')) :
  function printr_hide_die($var, $title = false, $var_export = false) {
    printr($var, $title, true, true, $var_export);
  }
endif;
