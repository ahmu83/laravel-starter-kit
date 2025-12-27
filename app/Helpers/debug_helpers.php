<?php

if (!function_exists('printr')) :
  function printr($var, $title = false, $hide = false, $die = false, $var_export = false) {
    $styles = [
      'pre' => 'color:#2d2d2d; background-color:#f7f8fa; white-space:break-spaces; margin:12px 20px 12px 12px; padding:8px 10px 0 10px; border:1px solid #d0d7de; border-radius:6px; box-sizing:border-box; font-family:Consolas, Monaco, monospace; overflow:hidden;',
      'h2' => 'color:#1f2328; padding:12px 10px 10px; margin:-8px -10px 0; border-bottom:1px solid #d0d7de; font-weight:600; background-color:#eef1f4; cursor:pointer; font-size:16px;',
      'small' => 'color:#57606a; font-weight:normal; font-size:13px;',
      'content' => 'background-color:transparent; margin:6px 0 10px; font-size:12px; line-height:18px; color:#24292f;',
    ];

    $toggle_js = 'onclick="this.closest(\'pre\').querySelector(\'.var-content\').classList.toggle(\'printr_pre_hide\')"';

    // Determine variable type
    $var_type = gettype($var);
    if (is_object($var)) {
      $var_type = '<b>' . htmlspecialchars(get_class($var), ENT_QUOTES, 'UTF-8') . '</b> ' . $var_type;
    } elseif (is_bool($var)) {
      $var_type .= ' - ' . ($var ? 'true' : 'false');
    }

    // Build title HTML (escape only the user-provided title text)
    $titleText = $title ? (string) $title : '';
    $titleText = htmlspecialchars($titleText, ENT_QUOTES, 'UTF-8');

    $typeHtml = ' <small style="' . $styles['small'] . '">(' . $var_type . ')</small>';
    $titleHtml = $titleText . $typeHtml;

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
        $var_content = $var_export ? var_export($var, true) : printr_format_value($var);
        break;
    }

    // Print CSS once per request
    static $styles_printed = false;
    if (!$styles_printed) {
      echo '<style>
        .printr_pre .printr_pre_hide{display:none}
        .printr_pre .var-content a{color:#0969da;text-decoration:none}
        .printr_pre .var-content a:hover{text-decoration:underline}
        .printr_pre .var-content a:visited{color:#8250df}
      </style>';
      $styles_printed = true;
    }

    echo '<pre style="' . $styles['pre'] . '" class="printr_pre">';
    echo '<h2 style="' . $styles['h2'] . '" ' . $toggle_js . '>' . $titleHtml . '</h2>';
    echo '<div class="var-content ' . ($hide ? 'printr_pre_hide' : '') . '" style="' . $styles['content'] . '">' . $var_content . '</div>';
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
