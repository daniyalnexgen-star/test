<?php
/**
 * Admin menu registration.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SP_Admin_Menu extends SP_Service {

    /**
     * Register hooks.
     */
    public function register_hooks() {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
    }

    /**
     * Add admin menu entries.
     */
    public function register_menu() {
        add_menu_page(
            __( 'Sovereign Parking', 'sovereign-parking' ),
            __( 'Sovereign Parking', 'sovereign-parking' ),
            'edit_sp_bookings',
            'sovereign-parking',
            [ $this, 'render_dashboard' ],
            'dashicons-parking',
            56
        );

        add_submenu_page( 'sovereign-parking', __( 'Dashboard', 'sovereign-parking' ), __( 'Dashboard', 'sovereign-parking' ), 'edit_sp_bookings', 'sovereign-parking', [ $this, 'render_dashboard' ] );
        add_submenu_page( 'sovereign-parking', __( 'Bookings', 'sovereign-parking' ), __( 'Bookings', 'sovereign-parking' ), 'edit_sp_bookings', 'edit.php?post_type=' . SP_Booking_CPT::POST_TYPE );
        add_submenu_page( 'sovereign-parking', __( 'Cruises', 'sovereign-parking' ), __( 'Cruises', 'sovereign-parking' ), 'edit_sp_bookings', 'edit.php?post_type=' . SP_Cruise_CPT::POST_TYPE );
        add_submenu_page( 'sovereign-parking', __( 'Shuttle Slots', 'sovereign-parking' ), __( 'Shuttle Slots', 'sovereign-parking' ), 'edit_sp_bookings', 'edit.php?post_type=' . SP_Shuttle_Slot_CPT::POST_TYPE );
        add_submenu_page( 'sovereign-parking', __( 'Calendar', 'sovereign-parking' ), __( 'Calendar', 'sovereign-parking' ), 'edit_sp_bookings', 'sp-calendar', [ $this, 'render_calendar' ] );
        add_submenu_page( 'sovereign-parking', __( 'Customer Credits', 'sovereign-parking' ), __( 'Customer Credits', 'sovereign-parking' ), 'edit_sp_bookings', 'sp-credits', [ $this, 'render_credits' ] );
        add_submenu_page( 'sovereign-parking', __( 'Settings', 'sovereign-parking' ), __( 'Settings', 'sovereign-parking' ), 'manage_sp_settings', 'sp-settings', [ $this, 'render_settings' ] );
    }

    /**
     * Render dashboard summary.
     */
    public function render_dashboard() {
        $bookings_count = wp_count_posts( SP_Booking_CPT::POST_TYPE );
        $confirmed      = isset( $bookings_count->{'sp-confirmed'} ) ? intval( $bookings_count->{'sp-confirmed'} ) : 0;
        $pending        = isset( $bookings_count->{'sp-pending'} ) ? intval( $bookings_count->{'sp-pending'} ) : 0;
        $poa_pending    = isset( $bookings_count->{'sp-poa-pending'} ) ? intval( $bookings_count->{'sp-poa-pending'} ) : 0;
        $poa_paid       = isset( $bookings_count->{'sp-poa-paid'} ) ? intval( $bookings_count->{'sp-poa-paid'} ) : 0;
        $total          = array_sum( (array) $bookings_count );
        include SP_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }

    /**
     * Render settings page.
     */
    public function render_settings() {
        if ( ! current_user_can( 'manage_sp_settings' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'sovereign-parking' ) );
        }
        include SP_PLUGIN_DIR . 'templates/admin/settings.php';
    }

    /**
     * Render calendar.
     */
    public function render_calendar() {
        include SP_PLUGIN_DIR . 'templates/admin/calendar.php';
    }

    /**
     * Render credits page.
     */
    public function render_credits() {
        include SP_PLUGIN_DIR . 'templates/admin/credits.php';
    }
}
