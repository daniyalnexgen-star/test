<?php
/**
 * Service interface for plugin components.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface SP_Service_Interface {
    /**
     * Register WordPress hooks.
     *
     * @return void
     */
    public function register_hooks();
}
