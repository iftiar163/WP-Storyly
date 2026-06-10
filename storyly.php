<?php
/**
 * Plugin Name:       Storyly
 * Plugin URI:        https://wordpress.org/plugins/storyly/
 * Description:       Transform your WordPress site into a clean, Medium-style writing and reading platform.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Iftiar Hossain
 * Author URI:        https://iftiarhossain.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       storyly
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

// Plugin Constants
define('STORYLY_VERSION', '1.0.0');
define( 'STORYLY_FILE',        __FILE__ );
define( 'STORYLY_PATH',        plugin_dir_path( __FILE__ ) );
define( 'STORYLY_URL',         plugin_dir_url( __FILE__ ) );
define( 'STORYLY_BASENAME',    plugin_basename( __FILE__ ) );

// Autoloader
spl_autoload_register( function ( string $class ): void {
    $prefix   = 'Storyly\\';
    $base_dir = STORYLY_PATH . 'includes/';

    if ( ! str_starts_with( $class, $prefix ) ) {
        return;
    }

    // Strip the root namespace: e.g. "Storyly\CPT\Story" → "CPT\Story"
    $relative = substr( $class, strlen( $prefix ) );

    // Split into parts: ["CPT", "Story"]
    $parts = explode( '\\', $relative );

    if ( count( $parts ) === 1 ) {
        // Top-level class: Storyly\Plugin → includes/class-plugin.php
        $file = $base_dir . 'class-' . strtolower( $parts[0] ) . '.php';
    } else {
        // Namespaced class: Storyly\CPT\Story → includes/cpt/class-story.php
        $class_name = array_pop( $parts );
        $sub_dir    = strtolower( implode( '/', $parts ) );
        $file       = $base_dir . $sub_dir . '/class-' . strtolower( $class_name ) . '.php';
    }

    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );

// Activation Hook
register_activation_hook(
    STORYLY_FILE,
    ['Storyly\\Activator', 'run']
);

// Deactivation Hook
register_deactivation_hook(
    STORYLY_FILE,
    ['Storyly\\Deactivator', 'run']
);

// Boot The Plugin
add_action( 'plugins_loaded', function (): void {
    ( new Storyly\Plugin() )->init();
} );