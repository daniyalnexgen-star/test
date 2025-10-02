<?php
/**
 * Calendar admin controller.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SP_Admin_Calendar extends SP_Service {

    /**
     * Register hooks.
     */
    public function register_hooks() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Enqueue assets on calendar page.
     */
    public function enqueue_assets( $hook ) {
        if ( 'toplevel_page_sovereign-parking' === $hook || 'sovereign-parking_page_sp-calendar' === $hook ) {
            wp_enqueue_style( 'sp-admin', SP_PLUGIN_URL . 'assets/css/admin.css', [], SP_PLUGIN_VERSION );
        }
    }
}
