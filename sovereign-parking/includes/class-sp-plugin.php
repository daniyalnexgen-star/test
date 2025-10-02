<?php
/**
 * Main plugin orchestrator.
 *
 * @package SovereignParking
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class SP_Plugin
 */
class SP_Plugin {

    /**
     * Singleton instance.
     *
     * @var SP_Plugin
     */
    protected static $instance;

    /**
     * Tracks if plugin has been booted.
     *
     * @var bool
     */
    protected $booted = false;

    /**
     * Registered services.
     *
     * @var array
     */
    protected $services = [];

    /**
     * Retrieve singleton instance.
     *
     * @return SP_Plugin
     */
    public static function instance() {
        if ( null === static::$instance ) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Boot plugin services.
     */
    public function boot() {
        if ( $this->booted ) {
            return;
        }

        load_plugin_textdomain( 'sovereign-parking', false, dirname( plugin_basename( SP_PLUGIN_FILE ) ) . '/languages' );

        $this->register_services();

        /** @var SP_Service_Interface $service */
        foreach ( $this->services as $service ) {
            $service->register_hooks();
        }

        $this->booted = true;
    }

    /**
     * Register services used by plugin.
     */
    protected function register_services() {
        $this->services = [
            new SP_Roles(),
            new SP_Cruise_CPT(),
            new SP_Shuttle_Slot_CPT(),
            new SP_Booking_CPT(),
            new SP_Settings(),
            new SP_Frontend_Booking(),
            new SP_Ajax_Controller(),
            new SP_Payment_Stripe(),
            new SP_Payment_Paypal(),
            new SP_Email_Manager(),
            new SP_Customer_Portal(),
            new SP_Admin_Menu(),
            new SP_Admin_Bookings_List(),
            new SP_Admin_Export(),
            new SP_Admin_Calendar(),
            new SP_Customer_Credits(),
        ];
    }

    /**
     * Plugin activation tasks.
     */
    public function activate() {
        $this->boot();

        /** @var SP_Service_Interface $service */
        foreach ( $this->services as $service ) {
            if ( method_exists( $service, 'activate' ) ) {
                $service->activate();
            }
        }
    }

    /**
     * Plugin deactivation tasks.
     */
    public function deactivate() {
        /** @var SP_Service_Interface $service */
        foreach ( $this->services as $service ) {
            if ( method_exists( $service, 'deactivate' ) ) {
                $service->deactivate();
            }
        }
    }
}
