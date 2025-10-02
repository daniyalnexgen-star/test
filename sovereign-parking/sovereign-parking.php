<?php
/**
 * Plugin Name: Sovereign Parking Booking System
 * Description: Comprehensive cruise terminal parking management with shuttle scheduling and payments.
 * Version: 1.0.0
 * Author: OpenAI ChatGPT
 * Text Domain: sovereign-parking
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SP_PLUGIN_VERSION', '1.0.0' );
define( 'SP_PLUGIN_FILE', __FILE__ );
define( 'SP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

autoload_sp_classes();

/**
 * Simple autoloader for plugin classes following the SP_ naming convention.
 */
function autoload_sp_classes() {
    spl_autoload_register(
        function ( $class ) {
            if ( 0 !== strpos( $class, 'SP_' ) ) {
                return;
            }

            $class_slug = strtolower( str_replace( '_', '-', $class ) );
            $file       = SP_PLUGIN_DIR . 'includes/class-' . $class_slug . '.php';

            if ( file_exists( $file ) ) {
                include_once $file;
            }
        }
    );
}

/**
 * Bootstrap the plugin once plugins are loaded.
 */
function sp_bootstrap_plugin() {
    $plugin = SP_Plugin::instance();
    $plugin->boot();
}
add_action( 'plugins_loaded', 'sp_bootstrap_plugin' );

/**
 * Activation hook to prepare custom roles, capabilities and seed data.
 */
function sp_activate_plugin() {
    $plugin = SP_Plugin::instance();
    $plugin->activate();
}
register_activation_hook( __FILE__, 'sp_activate_plugin' );

/**
 * Deactivation hook for cleanup tasks.
 */
function sp_deactivate_plugin() {
    $plugin = SP_Plugin::instance();
    $plugin->deactivate();
}
register_deactivation_hook( __FILE__, 'sp_deactivate_plugin' );
