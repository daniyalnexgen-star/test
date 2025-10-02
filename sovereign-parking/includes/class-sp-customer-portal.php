<?php
/**
 * Customer self-service portal.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SP_Customer_Portal extends SP_Service {

    /**
     * Register hooks.
     */
    public function register_hooks() {
        add_shortcode( 'sovereign_parking_portal', [ $this, 'render_portal' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
    }

    /**
     * Register portal assets.
     */
    public function register_assets() {
        wp_register_style( 'sp-portal', SP_PLUGIN_URL . 'assets/css/portal.css', [], SP_PLUGIN_VERSION );
        wp_register_script( 'sp-portal', SP_PLUGIN_URL . 'assets/js/portal.js', [ 'jquery' ], SP_PLUGIN_VERSION, true );
    }

    /**
     * Render portal content.
     */
    public function render_portal() {
        if ( ! is_user_logged_in() ) {
            ob_start();
            echo '<div class="sp-portal-login">';
            echo '<h2>' . esc_html__( 'Customer Login', 'sovereign-parking' ) . '</h2>';
            wp_login_form();
            echo '</div>';
            return ob_get_clean();
        }

        wp_enqueue_style( 'sp-portal' );
        wp_enqueue_script( 'sp-portal' );

        $user_id  = get_current_user_id();
        $bookings = get_posts(
            [
                'post_type'      => SP_Booking_CPT::POST_TYPE,
                'post_status'    => [ 'sp-confirmed', 'sp-poa-pending', 'sp-poa-paid', 'sp-pending' ],
                'posts_per_page' => -1,
                'meta_query'     => [
                    [
                        'key'   => '_sp_booking_user_id',
                        'value' => $user_id,
                    ],
                ],
                'orderby'        => 'meta_value',
                'meta_key'       => '_sp_booking_entry',
                'order'          => 'ASC',
            ]
        );

        $data = [];
        foreach ( $bookings as $booking ) {
            $data[] = [
                'id'         => $booking->ID,
                'number'     => get_post_meta( $booking->ID, '_sp_booking_number', true ),
                'cruise'     => get_the_title( get_post_meta( $booking->ID, '_sp_booking_cruise_id', true ) ),
                'entry'      => get_post_meta( $booking->ID, '_sp_booking_entry', true ),
                'exit'       => get_post_meta( $booking->ID, '_sp_booking_exit', true ),
                'passengers' => (int) get_post_meta( $booking->ID, '_sp_booking_passengers', true ),
                'shuttle_id' => (int) get_post_meta( $booking->ID, '_sp_booking_shuttle_id', true ),
                'shuttle_date' => get_post_meta( $booking->ID, '_sp_booking_shuttle_date', true ),
                'vehicle'    => get_post_meta( $booking->ID, '_sp_booking_vehicle', true ),
                'phone'      => get_post_meta( $booking->ID, '_sp_booking_phone', true ),
                'status'     => get_post_meta( $booking->ID, '_sp_booking_payment_status', true ),
            ];
        }

        $slots = get_posts(
            [
                'post_type'      => SP_Shuttle_Slot_CPT::POST_TYPE,
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'meta_value',
                'meta_key'       => '_sp_slot_start',
                'order'          => 'ASC',
            ]
        );
        $slot_data = [];
        foreach ( $slots as $slot ) {
            $slot_data[] = [
                'id'    => $slot->ID,
                'label' => $slot->post_title,
            ];
        }

        wp_localize_script(
            'sp-portal',
            'spPortalData',
            [
                'nonce'     => wp_create_nonce( 'sp_booking_nonce' ),
                'bookings'  => $data,
                'slots'     => $slot_data,
                'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
                'strings'   => [
                    'updateSuccess' => __( 'Booking updated successfully.', 'sovereign-parking' ),
                    'updateError'   => __( 'Could not update booking. Please try again.', 'sovereign-parking' ),
                ],
            ]
        );

        ob_start();
        include SP_PLUGIN_DIR . 'templates/frontend/customer-portal.php';
        return ob_get_clean();
    }
}
