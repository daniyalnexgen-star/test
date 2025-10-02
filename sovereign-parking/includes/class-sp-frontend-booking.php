<?php
/**
 * Front-end booking form and scripts.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SP_Frontend_Booking extends SP_Service {

    /**
     * Register hooks.
     */
    public function register_hooks() {
        add_shortcode( 'sovereign_parking_booking', [ $this, 'render_booking_form' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
    }

    /**
     * Register scripts and styles.
     */
    public function register_assets() {
        wp_register_style( 'sp-frontend', SP_PLUGIN_URL . 'assets/css/frontend.css', [], SP_PLUGIN_VERSION );
        wp_register_script( 'sp-frontend', SP_PLUGIN_URL . 'assets/js/frontend.js', [ 'jquery' ], SP_PLUGIN_VERSION, true );
    }

    /**
     * Render booking form shortcode.
     */
    public function render_booking_form( $atts ) {
        wp_enqueue_style( 'sp-frontend' );
        wp_enqueue_script( 'sp-frontend' );

        $atts = shortcode_atts( [
            'show_title' => true,
        ], $atts, 'sovereign_parking_booking' );

        $cruises = get_posts(
            [
                'post_type'      => SP_Cruise_CPT::POST_TYPE,
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'meta_value',
                'meta_key'       => '_sp_departure_datetime',
                'order'          => 'ASC',
            ]
        );

        $shuttle_slots = get_posts(
            [
                'post_type'      => SP_Shuttle_Slot_CPT::POST_TYPE,
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'meta_value',
                'meta_key'       => '_sp_slot_start',
                'order'          => 'ASC',
            ]
        );

        $cruise_data = [];
        foreach ( $cruises as $cruise ) {
            $cruise_data[] = [
                'id'        => $cruise->ID,
                'title'     => $cruise->post_title,
                'line'      => get_post_meta( $cruise->ID, '_sp_cruise_line', true ),
                'ship'      => get_post_meta( $cruise->ID, '_sp_ship_name', true ),
                'departure' => get_post_meta( $cruise->ID, '_sp_departure_datetime', true ),
                'return'    => get_post_meta( $cruise->ID, '_sp_return_datetime', true ),
            ];
        }

        $slot_data = [];
        foreach ( $shuttle_slots as $slot ) {
            $slot_data[] = [
                'id'       => $slot->ID,
                'label'    => $slot->post_title,
                'start'    => get_post_meta( $slot->ID, '_sp_slot_start', true ),
                'end'      => get_post_meta( $slot->ID, '_sp_slot_end', true ),
                'capacity' => (int) get_post_meta( $slot->ID, '_sp_slot_capacity', true ),
            ];
        }

        $pricing = [
            [ 'days' => 1, 'price' => 69 ],
            [ 'days' => 2, 'price' => 89 ],
            [ 'days' => 3, 'price' => 99 ],
            [ 'days' => 4, 'price' => 109 ],
            [ 'days' => 5, 'price' => 119 ],
            [ 'days' => 6, 'price' => 129 ],
            [ 'days' => 7, 'price' => 139 ],
            [ 'days' => 8, 'price' => 149 ],
            [ 'days' => 9, 'price' => 159 ],
            [ 'days' => 10, 'price' => 169 ],
            [ 'days' => 11, 'price' => 179 ],
            [ 'days' => 12, 'price' => 189 ],
            [ 'days' => 13, 'price' => 199 ],
            [ 'days' => 14, 'price' => 209 ],
            [ 'days' => 15, 'price' => 219 ],
            [ 'days' => 16, 'price' => 229 ],
            [ 'days' => 17, 'price' => 239 ],
            [ 'days' => 18, 'price' => 249 ],
            [ 'days' => 19, 'price' => 259 ],
            [ 'days' => 20, 'price' => 269 ],
            [ 'days' => 21, 'price' => 279 ],
            [ 'days' => 22, 'price' => 289 ],
            [ 'days' => 23, 'price' => 299 ],
            [ 'days' => 24, 'price' => 309 ],
            [ 'days' => 25, 'price' => 319 ],
            [ 'days' => 26, 'price' => 329 ],
            [ 'days' => 27, 'price' => 339 ],
            [ 'days' => 28, 'price' => 349 ],
            [ 'days' => 29, 'price' => 359 ],
            [ 'days' => 30, 'price' => 0 ],
        ];

        wp_localize_script(
            'sp-frontend',
            'spBookingData',
            [
                'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
                'nonce'         => wp_create_nonce( 'sp_booking_nonce' ),
                'cruises'       => $cruise_data,
                'slots'         => $slot_data,
                'pricing'       => $pricing,
                'strings'       => [
                    'step'          => __( 'Step', 'sovereign-parking' ),
                    'courtesyNote'  => __( 'During peak times, we kindly suggest dropping passengers and luggage at the cruise terminal before parking. This courtesy step helps free up shuttle capacity, allowing more transfers to run smoothly and making additional time slots available for all guests.', 'sovereign-parking' ),
                    'disclaimer'    => __( 'We do our best to get you to the cruise terminal within your selected time slot. However, we cannot control external factors such as traffic congestion or roadworks. Access to the terminal is via a single road in and out, so - while rare - delays may occur.', 'sovereign-parking' ),
                    'payOnArrival'  => __( 'Pay on Arrival requires a $10 holding deposit. The balance is due on arrival.', 'sovereign-parking' ),
                    'callForQuote'  => __( 'For stays of 30 days or more, please call for a personalised quote.', 'sovereign-parking' ),
                    'noSlots'       => __( 'No shuttle slots are available for the selected date. Please adjust passengers or time.', 'sovereign-parking' ),
                ],
                'depositAmount' => (float) SP_Helpers::get_option( 'poa_deposit_amount', 10 ),
            ]
        );

        ob_start();
        $confirmation = isset( $_GET['sp_booking_confirmed'] ) ? absint( $_GET['booking'] ) : 0;
        include SP_PLUGIN_DIR . 'templates/frontend/booking-form.php';
        return ob_get_clean();
    }
}
