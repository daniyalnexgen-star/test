<?php
/**
 * AJAX endpoints for booking workflow.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SP_Ajax_Controller extends SP_Service {

    /**
     * Register hooks.
     */
    public function register_hooks() {
        add_action( 'wp_ajax_sp_get_slots', [ $this, 'get_slots' ] );
        add_action( 'wp_ajax_nopriv_sp_get_slots', [ $this, 'get_slots' ] );

        add_action( 'wp_ajax_sp_get_credit', [ $this, 'get_credit' ] );
        add_action( 'wp_ajax_nopriv_sp_get_credit', [ $this, 'get_credit' ] );

        add_action( 'wp_ajax_sp_create_booking', [ $this, 'create_booking' ] );
        add_action( 'wp_ajax_nopriv_sp_create_booking', [ $this, 'create_booking' ] );

        add_action( 'wp_ajax_sp_update_booking', [ $this, 'update_booking' ] );
    }

    /**
     * Validate nonce for requests.
     */
    protected function verify_nonce() {
        if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'sp_booking_nonce' ) ) {
            wp_send_json_error( [ 'message' => __( 'Security check failed. Refresh and try again.', 'sovereign-parking' ) ], 403 );
        }
    }

    /**
     * Fetch available shuttle slots based on passengers and date.
     */
    public function get_slots() {
        $this->verify_nonce();

        $passengers = isset( $_POST['passengers'] ) ? intval( $_POST['passengers'] ) : 0;
        $date       = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
        $booking_id = isset( $_POST['booking_id'] ) ? intval( $_POST['booking_id'] ) : 0;

        if ( $passengers <= 0 || empty( $date ) ) {
            wp_send_json_error( [ 'message' => __( 'Missing shuttle information.', 'sovereign-parking' ) ] );
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

        $available = [];
        foreach ( $slots as $slot ) {
            $capacity  = (int) get_post_meta( $slot->ID, '_sp_slot_capacity', true );
            $booked    = $this->get_slot_passengers( $slot->ID, $date, $booking_id );
            $remaining = max( 0, $capacity - $booked );
            if ( $remaining >= $passengers ) {
                $available[] = [
                    'id'        => $slot->ID,
                    'label'     => $slot->post_title,
                    'remaining' => $remaining,
                ];
            }
        }

        wp_send_json_success( $available );
    }

    /**
     * Retrieve remaining passengers for slot/date.
     */
    protected function get_slot_passengers( $slot_id, $date, $exclude_booking = 0 ) {
        $bookings = get_posts(
            [
                'post_type'      => SP_Booking_CPT::POST_TYPE,
                'post_status'    => [ 'sp-confirmed', 'sp-poa-pending', 'sp-poa-paid', 'sp-pending' ],
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'meta_query'     => [
                    [
                        'key'   => '_sp_booking_shuttle_id',
                        'value' => $slot_id,
                    ],
                    [
                        'key'   => '_sp_booking_shuttle_date',
                        'value' => $date,
                    ],
                ],
            ]
        );

        $total = 0;
        foreach ( $bookings as $booking_id ) {
            if ( $exclude_booking && intval( $booking_id ) === intval( $exclude_booking ) ) {
                continue;
            }
            $total += (int) get_post_meta( $booking_id, '_sp_booking_passengers', true );
        }
        return $total;
    }

    /**
     * Fetch customer credit balance.
     */
    public function get_credit() {
        $this->verify_nonce();
        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        if ( empty( $email ) ) {
            wp_send_json_error();
        }

        $user = get_user_by( 'email', $email );
        if ( ! $user ) {
            wp_send_json_success( [ 'balance' => 0 ] );
        }

        $balance = SP_Customer_Credits::instance()->get_balance( $user->ID );
        wp_send_json_success( [ 'balance' => $balance ] );
    }

    /**
     * Create booking and initiate payment.
     */
    public function create_booking() {
        $this->verify_nonce();

        $cruise_id     = isset( $_POST['cruise_id'] ) ? intval( $_POST['cruise_id'] ) : 0;
        $entry_raw     = isset( $_POST['entry'] ) ? sanitize_text_field( wp_unslash( $_POST['entry'] ) ) : '';
        $exit_raw      = isset( $_POST['exit'] ) ? sanitize_text_field( wp_unslash( $_POST['exit'] ) ) : '';
        $passengers    = isset( $_POST['passengers'] ) ? intval( $_POST['passengers'] ) : 0;
        $shuttle_id    = isset( $_POST['shuttle_id'] ) ? intval( $_POST['shuttle_id'] ) : 0;
        $shuttle_date  = isset( $_POST['shuttle_date'] ) ? sanitize_text_field( wp_unslash( $_POST['shuttle_date'] ) ) : '';
        $full_name     = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
        $email         = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $phone         = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        $vehicle       = isset( $_POST['vehicle'] ) ? sanitize_text_field( wp_unslash( $_POST['vehicle'] ) ) : '';
        $payment       = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : 'stripe';
        $credit_apply  = isset( $_POST['credit'] ) ? floatval( $_POST['credit'] ) : 0;
        $source_url    = isset( $_POST['source_url'] ) ? esc_url_raw( wp_unslash( $_POST['source_url'] ) ) : home_url( '/' );

        if ( ! $cruise_id || empty( $entry_raw ) || empty( $exit_raw ) || $passengers <= 0 || ! $shuttle_id || empty( $shuttle_date ) ) {
            wp_send_json_error( [ 'message' => __( 'Please complete all required fields.', 'sovereign-parking' ) ] );
        }

        $entry = SP_Helpers::sanitize_datetime( $entry_raw );
        $exit  = SP_Helpers::sanitize_datetime( $exit_raw );
        if ( empty( $entry ) || empty( $exit ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid entry/exit dates.', 'sovereign-parking' ) ] );
        }

        $days  = SP_Helpers::calculate_days( $entry, $exit );
        $price = SP_Helpers::calculate_price( $days );
        if ( $price <= 0 ) {
            wp_send_json_error( [ 'message' => __( 'For stays of 30 days or more please contact us for a quote.', 'sovereign-parking' ) ] );
        }

        $remaining_capacity = $this->get_slot_passengers( $shuttle_id, $shuttle_date );
        $capacity           = (int) get_post_meta( $shuttle_id, '_sp_slot_capacity', true );
        if ( $remaining_capacity + $passengers > $capacity ) {
            wp_send_json_error( [ 'message' => __( 'Selected shuttle slot is no longer available. Please choose another time.', 'sovereign-parking' ) ] );
        }

        $user = get_user_by( 'email', $email );
        if ( ! $user ) {
            $user_id = wp_insert_user(
                [
                    'user_login' => sanitize_user( current( explode( '@', $email ) ) . wp_generate_password( 4, false ) ),
                    'user_email' => $email,
                    'user_pass'  => wp_generate_password( 12, true ),
                    'display_name' => $full_name,
                    'first_name' => $full_name,
                    'role'       => SP_Roles::ROLE_CUSTOMER,
                ]
            );

            if ( is_wp_error( $user_id ) ) {
                wp_send_json_error( [ 'message' => __( 'Could not create customer account.', 'sovereign-parking' ) ] );
            }
            $user = get_user_by( 'id', $user_id );
            wp_new_user_notification( $user_id, null, 'user' );
        } else {
            $user_id = $user->ID;
            if ( ! in_array( SP_Roles::ROLE_CUSTOMER, (array) $user->roles, true ) ) {
                $user->add_role( SP_Roles::ROLE_CUSTOMER );
            }
        }

        update_user_meta( $user_id, 'phone', $phone );

        $credit_balance = SP_Customer_Credits::instance()->get_balance( $user_id );
        $credit_applied = min( max( $credit_apply, 0 ), $credit_balance, $price );
        $amount_due     = max( 0, $price - $credit_applied );

        $booking_id = wp_insert_post(
            [
                'post_type'   => SP_Booking_CPT::POST_TYPE,
                'post_status' => 'sp-pending',
                'post_title'  => $full_name,
                'post_excerpt'=> sprintf( 'Cruise %s from %s to %s', get_the_title( $cruise_id ), $entry, $exit ),
            ]
        );

        if ( is_wp_error( $booking_id ) ) {
            wp_send_json_error( [ 'message' => __( 'Could not create booking. Please try again.', 'sovereign-parking' ) ] );
        }

        $booking_number = SP_Helpers::generate_booking_number();
        update_post_meta( $booking_id, '_sp_booking_number', $booking_number );
        update_post_meta( $booking_id, '_sp_booking_user_id', $user_id );
        update_post_meta( $booking_id, '_sp_booking_cruise_id', $cruise_id );
        update_post_meta( $booking_id, '_sp_booking_entry', $entry );
        update_post_meta( $booking_id, '_sp_booking_exit', $exit );
        update_post_meta( $booking_id, '_sp_booking_days', $days );
        update_post_meta( $booking_id, '_sp_booking_passengers', $passengers );
        update_post_meta( $booking_id, '_sp_booking_shuttle_id', $shuttle_id );
        update_post_meta( $booking_id, '_sp_booking_shuttle_date', $shuttle_date );
        update_post_meta( $booking_id, '_sp_booking_amount', $price );
        update_post_meta( $booking_id, '_sp_booking_payment', $payment );
        update_post_meta( $booking_id, '_sp_booking_payment_status', 'pending' );
        update_post_meta( $booking_id, '_sp_booking_credit_used', $credit_applied );
        update_post_meta( $booking_id, '_sp_booking_vehicle', $vehicle );
        update_post_meta( $booking_id, '_sp_booking_phone', $phone );
        update_post_meta( $booking_id, '_sp_booking_email', $email );
        update_post_meta( $booking_id, '_sp_booking_source_url', $source_url );

        if ( $credit_applied > 0 ) {
            SP_Customer_Credits::instance()->deduct_credit( $user_id, $credit_applied, $booking_id );
        }

        if ( 'poa' === $payment ) {
            $deposit = (float) SP_Helpers::get_option( 'poa_deposit_amount', 10 );
            update_post_meta( $booking_id, '_sp_booking_hold_amount', $deposit );
            update_post_meta( $booking_id, '_sp_booking_payment_status', 'poa_pending' );
            wp_update_post( [ 'ID' => $booking_id, 'post_status' => 'sp-poa-pending' ] );

            $session = SP_Payment_Stripe::instance()->create_checkout_session( $booking_id, $deposit, 'deposit', $source_url );
            if ( is_wp_error( $session ) ) {
                wp_delete_post( $booking_id, true );
                wp_send_json_error( [ 'message' => $session->get_error_message() ] );
            }

            wp_send_json_success( [ 'redirect_url' => $session['url'] ] );
        }

        if ( $amount_due <= 0 ) {
            update_post_meta( $booking_id, '_sp_booking_payment_status', 'paid' );
            wp_update_post( [ 'ID' => $booking_id, 'post_status' => 'sp-confirmed' ] );
            do_action( 'sp_booking_payment_confirmed', $booking_id );
            wp_send_json_success( [ 'message' => __( 'Booking confirmed using credit balance.', 'sovereign-parking' ) ] );
        }

        if ( 'paypal' === $payment ) {
            $order = SP_Payment_Paypal::instance()->create_order( $booking_id, $amount_due, $source_url );
            if ( is_wp_error( $order ) ) {
                wp_delete_post( $booking_id, true );
                wp_send_json_error( [ 'message' => $order->get_error_message() ] );
            }

            wp_send_json_success( [ 'redirect_url' => $order['url'] ] );
        }

        // Default to Stripe full payment.
        $session = SP_Payment_Stripe::instance()->create_checkout_session( $booking_id, $amount_due, 'full', $source_url );
        if ( is_wp_error( $session ) ) {
            wp_delete_post( $booking_id, true );
            wp_send_json_error( [ 'message' => $session->get_error_message() ] );
        }

        wp_send_json_success( [ 'redirect_url' => $session['url'] ] );
    }

    /**
     * Update booking details from portal.
     */
    public function update_booking() {
        $this->verify_nonce();

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => __( 'You must be logged in to update bookings.', 'sovereign-parking' ) ], 401 );
        }

        $booking_id   = isset( $_POST['booking_id'] ) ? intval( $_POST['booking_id'] ) : 0;
        $vehicle      = isset( $_POST['vehicle'] ) ? sanitize_text_field( wp_unslash( $_POST['vehicle'] ) ) : '';
        $phone        = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        $shuttle_id   = isset( $_POST['shuttle_id'] ) ? intval( $_POST['shuttle_id'] ) : 0;
        $shuttle_date = isset( $_POST['shuttle_date'] ) ? sanitize_text_field( wp_unslash( $_POST['shuttle_date'] ) ) : '';

        if ( ! $booking_id ) {
            wp_send_json_error( [ 'message' => __( 'Booking not found.', 'sovereign-parking' ) ] );
        }

        $booking = get_post( $booking_id );
        if ( ! $booking || SP_Booking_CPT::POST_TYPE !== $booking->post_type ) {
            wp_send_json_error( [ 'message' => __( 'Invalid booking.', 'sovereign-parking' ) ] );
        }

        $user_id = get_current_user_id();
        $owner   = (int) get_post_meta( $booking_id, '_sp_booking_user_id', true );
        if ( $owner !== $user_id ) {
            wp_send_json_error( [ 'message' => __( 'You are not authorised to update this booking.', 'sovereign-parking' ) ], 403 );
        }

        if ( $shuttle_id && $shuttle_date ) {
            $passengers = (int) get_post_meta( $booking_id, '_sp_booking_passengers', true );
            $capacity   = (int) get_post_meta( $shuttle_id, '_sp_slot_capacity', true );
            $booked     = $this->get_slot_passengers( $shuttle_id, $shuttle_date, $booking_id );
            if ( $booked + $passengers > $capacity ) {
                wp_send_json_error( [ 'message' => __( 'Selected shuttle time no longer has capacity.', 'sovereign-parking' ) ] );
            }
            update_post_meta( $booking_id, '_sp_booking_shuttle_id', $shuttle_id );
            update_post_meta( $booking_id, '_sp_booking_shuttle_date', $shuttle_date );
        }

        if ( $vehicle ) {
            update_post_meta( $booking_id, '_sp_booking_vehicle', $vehicle );
        }
        if ( $phone ) {
            update_post_meta( $booking_id, '_sp_booking_phone', $phone );
            update_user_meta( $user_id, 'phone', $phone );
        }

        do_action( 'sp_booking_updated', $booking_id );

        wp_send_json_success( [ 'message' => __( 'Booking updated successfully.', 'sovereign-parking' ) ] );
    }
}
