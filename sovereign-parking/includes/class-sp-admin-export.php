<?php
/**
 * Export utilities.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SP_Admin_Export extends SP_Service {

    /**
     * Register hooks.
     */
    public function register_hooks() {
        add_action( 'admin_post_sp_export_filtered', [ $this, 'export_filtered' ] );
    }

    /**
     * Stream CSV for given booking IDs.
     */
    public function stream_csv( $post_ids ) {
        if ( empty( $post_ids ) ) {
            wp_die( esc_html__( 'No bookings selected.', 'sovereign-parking' ) );
        }

        $filename = 'sovereign-bookings-' . gmdate( 'Ymd-His' ) . '.csv';
        header( 'Content-Type: text/csv' );
        header( 'Content-Disposition: attachment; filename=' . $filename );

        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, [ 'Booking ID', 'Customer', 'Email', 'Phone', 'Number Plate', 'Cruise', 'Entry', 'Exit', 'Days', 'Shuttle Slot', 'Passengers', 'Payment Method', 'Payment Status', 'Amount', 'Credit Applied', 'Notes' ] );

        foreach ( $post_ids as $post_id ) {
            $customer_name = get_post_field( 'post_title', $post_id );
            $email         = get_post_meta( $post_id, '_sp_booking_email', true );
            $phone         = get_post_meta( $post_id, '_sp_booking_phone', true );
            $vehicle       = get_post_meta( $post_id, '_sp_booking_vehicle', true );
            $cruise_id     = get_post_meta( $post_id, '_sp_booking_cruise_id', true );
            $cruise_title  = $cruise_id ? get_the_title( $cruise_id ) : '';
            $entry         = get_post_meta( $post_id, '_sp_booking_entry', true );
            $exit          = get_post_meta( $post_id, '_sp_booking_exit', true );
            $days          = get_post_meta( $post_id, '_sp_booking_days', true );
            $slot_id       = get_post_meta( $post_id, '_sp_booking_shuttle_id', true );
            $slot_title    = $slot_id ? get_the_title( $slot_id ) : '';
            $passengers    = get_post_meta( $post_id, '_sp_booking_passengers', true );
            $payment       = get_post_meta( $post_id, '_sp_booking_payment', true );
            $payment_status= get_post_meta( $post_id, '_sp_booking_payment_status', true );
            $amount        = get_post_meta( $post_id, '_sp_booking_amount', true );
            $credit        = get_post_meta( $post_id, '_sp_booking_credit_used', true );
            $notes         = get_post_meta( $post_id, '_sp_booking_notes', true );

            fputcsv(
                $output,
                [
                    get_post_meta( $post_id, '_sp_booking_number', true ),
                    $customer_name,
                    $email,
                    $phone,
                    $vehicle,
                    $cruise_title,
                    $entry,
                    $exit,
                    $days,
                    $slot_title,
                    $passengers,
                    $payment,
                    $payment_status,
                    $amount,
                    $credit,
                    $notes,
                ]
            );
        }

        fclose( $output );
    }

    /**
     * Export filtered bookings.
     */
    public function export_filtered() {
        if ( ! current_user_can( 'edit_sp_bookings' ) ) {
            wp_die( esc_html__( 'Permission denied.', 'sovereign-parking' ) );
        }

        check_admin_referer( 'sp_export_filtered' );

        $args = [
            'post_type'      => SP_Booking_CPT::POST_TYPE,
            'posts_per_page' => -1,
        ];

        if ( ! empty( $_GET['sp_cruise_filter'] ) ) {
            $args['meta_query'][] = [
                'key'   => '_sp_booking_cruise_id',
                'value' => intval( $_GET['sp_cruise_filter'] ),
            ];
        }

        if ( ! empty( $_GET['sp_payment_filter'] ) ) {
            $args['meta_query'][] = [
                'key'   => '_sp_booking_payment_status',
                'value' => sanitize_text_field( wp_unslash( $_GET['sp_payment_filter'] ) ),
            ];
        }

        $posts = get_posts( $args );
        $ids   = wp_list_pluck( $posts, 'ID' );
        $this->stream_csv( $ids );
        exit;
    }
}
