<?php

/**
 * Plugin Name:       Narrato for Writers by Iftiar
 * Plugin URI:        https://wordpress.org/plugins/narrato-for-writers/
 * Description:       Transform your WordPress site into a clean, Medium-style writing and reading platform.
 * Version:           1.3.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Iftiar Hossain
 * Author URI:        https://iftiarhossain.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       narrato-for-writers
 * Domain Path:       /languages
 */

defined('ABSPATH') || exit;

// Plugin Constants
define('NARRATO_VERSION', '1.3.0');
define('NARRATO_FILE',        __FILE__);
define('NARRATO_PATH',        plugin_dir_path(__FILE__));
define('NARRATO_URL',         plugin_dir_url(__FILE__));
define('NARRATO_BASENAME',    plugin_basename(__FILE__));

// Autoloader
spl_autoload_register( function ( string $class ): void {
    $prefix   = 'Narrato\\';
    $base_dir = NARRATO_PATH . 'includes/';

    if ( ! str_starts_with( $class, $prefix ) ) {
        return;
    }

    $relative = substr( $class, strlen( $prefix ) );
    $parts    = explode( '\\', $relative );
    $class_name = array_pop( $parts );
    $sub_dir    = strtolower( implode( '/', $parts ) );
    $sub_dir    = $sub_dir ? $sub_dir . '/' : '';

    // Convert PascalCase to kebab-case, e.g. "StripeGateway" → "stripe-gateway"
    $kebab = strtolower( preg_replace( '/(?<!^)[A-Z]/', '-$0', $class_name ) );

    $candidates = [
        $base_dir . $sub_dir . 'class-' . $kebab . '.php',                    // stripe-gateway
        $base_dir . $sub_dir . 'class-' . strtolower( $class_name ) . '.php', // stripegateway
    ];

    foreach ( $candidates as $file ) {
        if ( file_exists( $file ) ) {
            require_once $file;
            return;
        }
    }
} );

// Activation Hook
register_activation_hook(
    NARRATO_FILE,
    ['Narrato\\Activator', 'run']
);

// Deactivation Hook
register_deactivation_hook(
    NARRATO_FILE,
    ['Narrato\\Deactivator', 'run']
);

// Run DB upgrades on version change
add_action('plugins_loaded', function (): void {
    if (get_option('narrato_db_version') !== '1.3.0') {
        Narrato\Activator::create_tables();
        flush_rewrite_rules();
    }
});

// Boot The Plugin
add_action('plugins_loaded', function (): void {
    (new Narrato\Plugin())->init();
});
