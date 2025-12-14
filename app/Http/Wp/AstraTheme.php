<?php
namespace App\Http\Wp;

use Illuminate\Routing\Controller;

/**
 * WordPress Theme Integration Class
 *
 * Handles WordPress-specific hooks and functionality for the Astra theme
 * when Laravel routes don't match. This allows theme-specific customizations
 * to run on non-Laravel pages.
 *
 * Usage:
 *   - Add this class to config/wp.php 'classes' array
 *   - It will automatically be instantiated and registered when needed
 *   - All hooks should be registered in registerHooks()
 */
class AstraTheme extends Controller {
  /**
   * Constructor - automatically registers WordPress hooks
   */
  public function __construct() {
    $this->registerHooks();
  }

  /**
   * Register all WordPress hooks
   *
   * Centralize all hook registration here for easy maintenance.
   * Hooks are only registered when this class is instantiated.
   */
  private function registerHooks(): void {
    add_action('wp_footer', [$this, 'footer']);
    add_action('wp_head', [$this, 'head']);
    add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
    // Add more hooks as needed
  }

  /**
   * Footer hook
   *
   * Fires in wp_footer - typically near </body> tag
   */
  public function footer(): void {
    echo 'AstraTheme@footer...!';
    // echo '<!-- AstraTheme@footer() -->';
    // Add your footer content here
  }

  /**
   * Head hook
   *
   * Fires in wp_head - typically in <head> section
   */
  public function head(): void {
    // echo '<meta name="custom" content="value">';
    // Add your head content here
  }

  /**
   * Enqueue Scripts & Styles
   *
   * Hook to register and enqueue theme scripts/styles
   */
  public function enqueueScripts(): void {
    // wp_enqueue_style('astra-custom', get_stylesheet_uri());
    // wp_enqueue_script('astra-custom', get_template_directory_uri() . '/assets/js/custom.js');
  }
}
