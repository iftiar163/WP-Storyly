<?php

/**
 * Plugin Name:       Narrato for Writers by Iftiar
 * Plugin URI:        https://wordpress.org/plugins/narrato-for-writers/
 * Description:       Transform your WordPress site into a clean, Medium-style writing and reading platform.
 * Version:           1.2.0
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
define('NARRATO_VERSION', '1.1.0');
define('NARRATO_FILE',        __FILE__);
define('NARRATO_PATH',        plugin_dir_path(__FILE__));
define('NARRATO_URL',         plugin_dir_url(__FILE__));
define('NARRATO_BASENAME',    plugin_basename(__FILE__));

// Autoloader
spl_autoload_register(function (string $class): void {
    $prefix   = 'Narrato\\';
    $base_dir = NARRATO_PATH . 'includes/';

    if (! str_starts_with($class, $prefix)) {
        return;
    }

    // Strip the root namespace: e.g. "Narrato\CPT\Story" → "CPT\Story"
    $relative = substr($class, strlen($prefix));

    // Split into parts: ["CPT", "Story"]
    $parts = explode('\\', $relative);

    if (count($parts) === 1) {
        // Top-level class: Narrato\Plugin → includes/class-plugin.php
        $file = $base_dir . 'class-' . strtolower($parts[0]) . '.php';
    } else {
        // Namespaced class: Narrato\CPT\Story → includes/cpt/class-story.php
        $class_name = array_pop($parts);
        $sub_dir    = strtolower(implode('/', $parts));
        $file       = $base_dir . $sub_dir . '/class-' . strtolower($class_name) . '.php';
    }

    if (file_exists($file)) {
        require_once $file;
    }
});

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
    if (get_option('narrato_db_version') !== '1.1.0') {
        Narrato\Activator::create_tables();
        flush_rewrite_rules();
    }
});

// Boot The Plugin
add_action('plugins_loaded', function (): void {
    (new Narrato\Plugin())->init();
});
