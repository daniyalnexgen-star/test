<?php
/**
 * Enhancements for booking list table.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SP_Admin_Bookings_List extends SP_Service {

    /**
     * Register hooks.
     */
    public function register_hooks() {
        add_action( 'restrict_manage_posts', [ $this, 'filters' ] );
        add_filter( 'parse_query', [ $this, 'apply_filters' ] );
        add_filter( 'bulk_actions-edit-' . SP_Booking_CPT::POST_TYPE, [ $this, 'bulk_actions' ] );
        add_filter( 'handle_bulk_actions-edit-' . SP_Booking_CPT::POST_TYPE, [ $this, 'handle_bulk_action' ], 10, 3 );
    }

    /**
     * Render admin filters.
     */
    public function filters() {
        global $typenow;
        if ( SP_Booking_CPT::POST_TYPE !== $typenow ) {
            return;
        }

        $cruises = get_posts(
            [
                'post_type'      => SP_Cruise_CPT::POST_TYPE,
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'orderby'        => 'title',
                'order'          => 'ASC',
            ]
        );

        $selected_cruise = isset( $_GET['sp_cruise_filter'] ) ? intval( $_GET['sp_cruise_filter'] ) : '';
        echo '<select name="sp_cruise_filter" class="postform">';
        echo '<option value="">' . esc_html__( 'All Cruises', 'sovereign-parking' ) . '</option>';
        foreach ( $cruises as $cruise ) {
            printf( '<option value="%1$d" %2$s>%3$s</option>', esc_attr( $cruise->ID ), selected( $selected_cruise, $cruise->ID, false ), esc_html( $cruise->post_title ) );
        }
        echo '</select>';

        $selected_status = isset( $_GET['sp_payment_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['sp_payment_filter'] ) ) : '';
        $statuses        = [
            'paid'         => __( 'Paid', 'sovereign-parking' ),
            'pending'      => __( 'Pending', 'sovereign-parking' ),
            'poa_pending'  => __( 'POA – Pending', 'sovereign-parking' ),
            'poa_paid'     => __( 'POA – Paid', 'sovereign-parking' ),
            'refunded'     => __( 'Refunded', 'sovereign-parking' ),
        ];
        echo '<select name="sp_payment_filter" class="postform">';
        echo '<option value="">' . esc_html__( 'All Payment Statuses', 'sovereign-parking' ) . '</option>';
        foreach ( $statuses as $key => $label ) {
            printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $key ), selected( $selected_status, $key, false ), esc_html( $label ) );
        }
        echo '</select>';

        $months = wp_get_archives(
            [
                'type'            => 'monthly',
                'format'          => 'custom',
                'post_type'       => SP_Booking_CPT::POST_TYPE,
                'echo'            => 0,
                'order'           => 'DESC',
                'show_post_count' => false,
            ]
        );
        // Default WP month dropdown already present; no further action.
    }

    /**
     * Apply filters to query.
     */
    public function apply_filters( $query ) {
        global $pagenow;
        if ( 'edit.php' !== $pagenow || ! isset( $query->query['post_type'] ) || SP_Booking_CPT::POST_TYPE !== $query->query['post_type'] ) {
            return;
        }

        if ( ! isset( $query->query_vars['meta_query'] ) || ! is_array( $query->query_vars['meta_query'] ) ) {
            $query->query_vars['meta_query'] = [];
        }

        if ( ! empty( $_GET['sp_cruise_filter'] ) ) {
            $query->query_vars['meta_query'][] = [
                'key'   => '_sp_booking_cruise_id',
                'value' => intval( $_GET['sp_cruise_filter'] ),
            ];
        }

        if ( ! empty( $_GET['sp_payment_filter'] ) ) {
            $query->query_vars['meta_query'][] = [
                'key'   => '_sp_booking_payment_status',
                'value' => sanitize_text_field( wp_unslash( $_GET['sp_payment_filter'] ) ),
            ];
        }
    }

    /**
     * Register bulk actions.
     */
    public function bulk_actions( $actions ) {
        $actions['sp_export_csv'] = __( 'Export to CSV', 'sovereign-parking' );
        return $actions;
    }

    /**
     * Handle bulk action.
     */
    public function handle_bulk_action( $redirect_to, $action, $post_ids ) {
        if ( 'sp_export_csv' !== $action ) {
            return $redirect_to;
        }

        $exporter = new SP_Admin_Export();
        $exporter->stream_csv( $post_ids );
        exit;
    }
}
