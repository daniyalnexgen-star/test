<?php
/**
 * Booking custom post type.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SP_Booking_CPT extends SP_Service {

    const POST_TYPE = 'sp_booking';

    /**
     * Register hooks.
     */
    public function register_hooks() {
        add_action( 'init', [ $this, 'register_post_type' ] );
        add_action( 'init', [ $this, 'register_meta' ] );
        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ $this, 'columns' ] );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ $this, 'render_column' ], 10, 2 );
        add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', [ $this, 'sortable_columns' ] );
        add_action( 'pre_get_posts', [ $this, 'sort_columns' ] );
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post_' . self::POST_TYPE, [ $this, 'save_meta' ], 10, 2 );
        add_filter( 'display_post_states', [ $this, 'post_states' ], 10, 2 );
        add_filter( 'post_type_link', [ $this, 'prevent_frontend_link' ], 10, 2 );
    }

    /**
     * Register post type.
     */
    public function register_post_type() {
        $labels = [
            'name'               => __( 'Bookings', 'sovereign-parking' ),
            'singular_name'      => __( 'Booking', 'sovereign-parking' ),
            'add_new'            => __( 'Add Booking', 'sovereign-parking' ),
            'add_new_item'       => __( 'Add New Booking', 'sovereign-parking' ),
            'edit_item'          => __( 'Edit Booking', 'sovereign-parking' ),
            'new_item'           => __( 'New Booking', 'sovereign-parking' ),
            'view_item'          => __( 'View Booking', 'sovereign-parking' ),
            'search_items'       => __( 'Search Bookings', 'sovereign-parking' ),
            'not_found'          => __( 'No bookings found', 'sovereign-parking' ),
            'not_found_in_trash' => __( 'No bookings found in Trash', 'sovereign-parking' ),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'supports'           => [ 'title', 'editor' ],
            'capability_type'    => [ 'sp_booking', 'sp_bookings' ],
            'map_meta_cap'       => true,
        ];

        register_post_type( self::POST_TYPE, $args );

        $statuses = [
            'sp-pending'     => __( 'Pending Payment', 'sovereign-parking' ),
            'sp-confirmed'   => __( 'Confirmed', 'sovereign-parking' ),
            'sp-cancelled'   => __( 'Cancelled', 'sovereign-parking' ),
            'sp-poa-pending' => __( 'POA – Pending', 'sovereign-parking' ),
            'sp-poa-paid'    => __( 'POA – Paid', 'sovereign-parking' ),
        ];

        foreach ( $statuses as $status => $label ) {
            register_post_status(
                $status,
                [
                    'label'                     => $label,
                    'public'                    => false,
                    'internal'                  => true,
                    'show_in_admin_all_list'    => true,
                    'show_in_admin_status_list' => true,
                    'label_count'               => _n_noop( $label . ' <span class="count">(%s)</span>', $label . ' <span class="count">(%s)</span>', 'sovereign-parking' ),
                ]
            );
        }
    }

    /**
     * Register booking meta.
     */
    public function register_meta() {
        $meta_fields = [
            '_sp_booking_number'      => 'string',
            '_sp_booking_user_id'     => 'integer',
            '_sp_booking_cruise_id'   => 'integer',
            '_sp_booking_entry'       => 'string',
            '_sp_booking_exit'        => 'string',
            '_sp_booking_days'        => 'integer',
            '_sp_booking_passengers'  => 'integer',
            '_sp_booking_shuttle_id'  => 'integer',
            '_sp_booking_shuttle_date'=> 'string',
            '_sp_booking_amount'      => 'number',
            '_sp_booking_payment'     => 'string',
            '_sp_booking_payment_status' => 'string',
            '_sp_booking_hold_amount' => 'number',
            '_sp_booking_vehicle'     => 'string',
            '_sp_booking_phone'       => 'string',
            '_sp_booking_email'       => 'string',
            '_sp_booking_credit_used' => 'number',
            '_sp_booking_notes'       => 'string',
        ];

        foreach ( $meta_fields as $key => $type ) {
            register_post_meta(
                self::POST_TYPE,
                $key,
                [
                    'type'         => $type,
                    'single'       => true,
                    'show_in_rest' => true,
                    'auth_callback'=> '__return_true',
                ]
            );
        }
    }

    /**
     * Add meta boxes for booking details.
     */
    public function add_meta_boxes() {
        add_meta_box( 'sp_booking_details', __( 'Booking Details', 'sovereign-parking' ), [ $this, 'render_meta_box' ], self::POST_TYPE, 'normal', 'high' );
        add_meta_box( 'sp_booking_customer', __( 'Customer Details', 'sovereign-parking' ), [ $this, 'render_customer_meta_box' ], self::POST_TYPE, 'side', 'default' );
    }

    /**
     * Render booking meta box.
     */
    public function render_meta_box( $post ) {
        wp_nonce_field( 'sp_save_booking', 'sp_booking_nonce' );

        $cruise_id  = get_post_meta( $post->ID, '_sp_booking_cruise_id', true );
        $entry      = get_post_meta( $post->ID, '_sp_booking_entry', true );
        $exit       = get_post_meta( $post->ID, '_sp_booking_exit', true );
        $days       = get_post_meta( $post->ID, '_sp_booking_days', true );
        $passengers = get_post_meta( $post->ID, '_sp_booking_passengers', true );
        $shuttle_id = get_post_meta( $post->ID, '_sp_booking_shuttle_id', true );
        $shuttle_date = get_post_meta( $post->ID, '_sp_booking_shuttle_date', true );
        $amount     = get_post_meta( $post->ID, '_sp_booking_amount', true );
        $payment    = get_post_meta( $post->ID, '_sp_booking_payment', true );
        $status     = get_post_meta( $post->ID, '_sp_booking_payment_status', true );
        $credit     = get_post_meta( $post->ID, '_sp_booking_credit_used', true );

        $cruises = get_posts(
            [
                'post_type'      => SP_Cruise_CPT::POST_TYPE,
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'orderby'        => 'title',
                'order'          => 'ASC',
            ]
        );

        $slots = get_posts(
            [
                'post_type'      => SP_Shuttle_Slot_CPT::POST_TYPE,
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'orderby'        => 'meta_value',
                'meta_key'       => '_sp_slot_start',
                'order'          => 'ASC',
            ]
        );
        ?>
        <p>
            <label for="sp_booking_cruise"><strong><?php esc_html_e( 'Cruise', 'sovereign-parking' ); ?></strong></label><br />
            <select name="sp_booking_cruise" id="sp_booking_cruise" class="widefat">
                <option value="">—</option>
                <?php foreach ( $cruises as $cruise ) : ?>
                    <option value="<?php echo esc_attr( $cruise->ID ); ?>" <?php selected( $cruise_id, $cruise->ID ); ?>><?php echo esc_html( $cruise->post_title ); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="sp_booking_entry"><strong><?php esc_html_e( 'Entry Date & Time', 'sovereign-parking' ); ?></strong></label><br />
            <input type="datetime-local" name="sp_booking_entry" id="sp_booking_entry" class="widefat" value="<?php echo esc_attr( $this->format_local_datetime( $entry ) ); ?>" />
        </p>
        <p>
            <label for="sp_booking_exit"><strong><?php esc_html_e( 'Exit Date & Time', 'sovereign-parking' ); ?></strong></label><br />
            <input type="datetime-local" name="sp_booking_exit" id="sp_booking_exit" class="widefat" value="<?php echo esc_attr( $this->format_local_datetime( $exit ) ); ?>" />
        </p>
        <p>
            <label for="sp_booking_passengers"><strong><?php esc_html_e( 'Passengers', 'sovereign-parking' ); ?></strong></label><br />
            <input type="number" min="1" name="sp_booking_passengers" id="sp_booking_passengers" class="small-text" value="<?php echo esc_attr( $passengers ?: 1 ); ?>" />
        </p>
        <p>
            <label for="sp_booking_shuttle"><strong><?php esc_html_e( 'Shuttle Slot', 'sovereign-parking' ); ?></strong></label><br />
            <select name="sp_booking_shuttle" id="sp_booking_shuttle" class="widefat">
                <option value="">—</option>
                <?php foreach ( $slots as $slot ) :
                    $start = get_post_meta( $slot->ID, '_sp_slot_start', true );
                    $end   = get_post_meta( $slot->ID, '_sp_slot_end', true );
                    ?>
                    <option value="<?php echo esc_attr( $slot->ID ); ?>" <?php selected( $shuttle_id, $slot->ID ); ?>><?php echo esc_html( $slot->post_title ?: ( $start . ' – ' . $end ) ); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="sp_booking_shuttle_date"><strong><?php esc_html_e( 'Shuttle Date', 'sovereign-parking' ); ?></strong></label><br />
            <input type="date" name="sp_booking_shuttle_date" id="sp_booking_shuttle_date" class="widefat" value="<?php echo esc_attr( $shuttle_date ? gmdate( 'Y-m-d', strtotime( $shuttle_date ) ) : '' ); ?>" />
        </p>
        <p>
            <label for="sp_booking_amount"><strong><?php esc_html_e( 'Amount', 'sovereign-parking' ); ?></strong></label><br />
            <input type="number" step="0.01" name="sp_booking_amount" id="sp_booking_amount" class="small-text" value="<?php echo esc_attr( $amount ); ?>" />
        </p>
        <p>
            <label for="sp_booking_payment"><strong><?php esc_html_e( 'Payment Method', 'sovereign-parking' ); ?></strong></label><br />
            <select name="sp_booking_payment" id="sp_booking_payment" class="widefat">
                <option value="stripe" <?php selected( $payment, 'stripe' ); ?>><?php esc_html_e( 'Stripe', 'sovereign-parking' ); ?></option>
                <option value="paypal" <?php selected( $payment, 'paypal' ); ?>><?php esc_html_e( 'PayPal', 'sovereign-parking' ); ?></option>
                <option value="poa" <?php selected( $payment, 'poa' ); ?>><?php esc_html_e( 'Pay on Arrival', 'sovereign-parking' ); ?></option>
            </select>
        </p>
        <p>
            <label for="sp_booking_payment_status"><strong><?php esc_html_e( 'Payment Status', 'sovereign-parking' ); ?></strong></label><br />
            <select name="sp_booking_payment_status" id="sp_booking_payment_status" class="widefat">
                <option value="pending" <?php selected( $status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'sovereign-parking' ); ?></option>
                <option value="paid" <?php selected( $status, 'paid' ); ?>><?php esc_html_e( 'Paid', 'sovereign-parking' ); ?></option>
                <option value="poa_pending" <?php selected( $status, 'poa_pending' ); ?>><?php esc_html_e( 'POA – Pending', 'sovereign-parking' ); ?></option>
                <option value="poa_paid" <?php selected( $status, 'poa_paid' ); ?>><?php esc_html_e( 'POA – Paid', 'sovereign-parking' ); ?></option>
                <option value="refunded" <?php selected( $status, 'refunded' ); ?>><?php esc_html_e( 'Refunded', 'sovereign-parking' ); ?></option>
            </select>
        </p>
        <p>
            <label for="sp_booking_credit_used"><strong><?php esc_html_e( 'Credit Applied', 'sovereign-parking' ); ?></strong></label><br />
            <input type="number" step="0.01" name="sp_booking_credit_used" id="sp_booking_credit_used" class="small-text" value="<?php echo esc_attr( $credit ); ?>" />
        </p>
        <?php
    }

    /**
     * Render customer meta box.
     */
    public function render_customer_meta_box( $post ) {
        $vehicle = get_post_meta( $post->ID, '_sp_booking_vehicle', true );
        $phone   = get_post_meta( $post->ID, '_sp_booking_phone', true );
        $email   = get_post_meta( $post->ID, '_sp_booking_email', true );
        ?>
        <p><strong><?php esc_html_e( 'Vehicle Plate', 'sovereign-parking' ); ?></strong><br />
            <input type="text" name="sp_booking_vehicle" class="widefat" value="<?php echo esc_attr( $vehicle ); ?>" />
        </p>
        <p><strong><?php esc_html_e( 'Phone', 'sovereign-parking' ); ?></strong><br />
            <input type="text" name="sp_booking_phone" class="widefat" value="<?php echo esc_attr( $phone ); ?>" />
        </p>
        <p><strong><?php esc_html_e( 'Email', 'sovereign-parking' ); ?></strong><br />
            <input type="email" name="sp_booking_email" class="widefat" value="<?php echo esc_attr( $email ); ?>" />
        </p>
        <?php
    }

    /**
     * Save booking meta.
     */
    public function save_meta( $post_id, $post ) {
        if ( ! isset( $_POST['sp_booking_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sp_booking_nonce'] ) ), 'sp_save_booking' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( self::POST_TYPE !== $post->post_type ) {
            return;
        }

        $entry     = isset( $_POST['sp_booking_entry'] ) ? SP_Helpers::sanitize_datetime( wp_unslash( $_POST['sp_booking_entry'] ) ) : '';
        $exit      = isset( $_POST['sp_booking_exit'] ) ? SP_Helpers::sanitize_datetime( wp_unslash( $_POST['sp_booking_exit'] ) ) : '';
        $days      = SP_Helpers::calculate_days( $entry, $exit );
        $passengers= isset( $_POST['sp_booking_passengers'] ) ? intval( $_POST['sp_booking_passengers'] ) : 1;
        $shuttle   = isset( $_POST['sp_booking_shuttle'] ) ? intval( $_POST['sp_booking_shuttle'] ) : 0;
        $shuttle_date = isset( $_POST['sp_booking_shuttle_date'] ) ? sanitize_text_field( wp_unslash( $_POST['sp_booking_shuttle_date'] ) ) : '';
        $amount    = isset( $_POST['sp_booking_amount'] ) ? floatval( $_POST['sp_booking_amount'] ) : 0;
        $payment   = isset( $_POST['sp_booking_payment'] ) ? sanitize_text_field( wp_unslash( $_POST['sp_booking_payment'] ) ) : 'stripe';
        $status    = isset( $_POST['sp_booking_payment_status'] ) ? sanitize_text_field( wp_unslash( $_POST['sp_booking_payment_status'] ) ) : 'pending';
        $credit    = isset( $_POST['sp_booking_credit_used'] ) ? floatval( $_POST['sp_booking_credit_used'] ) : 0;
        $vehicle   = isset( $_POST['sp_booking_vehicle'] ) ? sanitize_text_field( wp_unslash( $_POST['sp_booking_vehicle'] ) ) : '';
        $phone     = isset( $_POST['sp_booking_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['sp_booking_phone'] ) ) : '';
        $email     = isset( $_POST['sp_booking_email'] ) ? sanitize_email( wp_unslash( $_POST['sp_booking_email'] ) ) : '';
        $cruise_id = isset( $_POST['sp_booking_cruise'] ) ? intval( $_POST['sp_booking_cruise'] ) : 0;

        update_post_meta( $post_id, '_sp_booking_cruise_id', $cruise_id );
        update_post_meta( $post_id, '_sp_booking_entry', $entry );
        update_post_meta( $post_id, '_sp_booking_exit', $exit );
        update_post_meta( $post_id, '_sp_booking_days', $days );
        update_post_meta( $post_id, '_sp_booking_passengers', $passengers );
        update_post_meta( $post_id, '_sp_booking_shuttle_id', $shuttle );
        update_post_meta( $post_id, '_sp_booking_shuttle_date', $shuttle_date );
        update_post_meta( $post_id, '_sp_booking_amount', $amount );
        update_post_meta( $post_id, '_sp_booking_payment', $payment );
        update_post_meta( $post_id, '_sp_booking_payment_status', $status );
        update_post_meta( $post_id, '_sp_booking_credit_used', $credit );
        update_post_meta( $post_id, '_sp_booking_vehicle', $vehicle );
        update_post_meta( $post_id, '_sp_booking_phone', $phone );
        update_post_meta( $post_id, '_sp_booking_email', $email );

        if ( 'poa_pending' === $status ) {
            wp_update_post(
                [
                    'ID'          => $post_id,
                    'post_status' => 'sp-poa-pending',
                ]
            );
        } elseif ( 'poa_paid' === $status ) {
            wp_update_post(
                [
                    'ID'          => $post_id,
                    'post_status' => 'sp-poa-paid',
                ]
            );
        } elseif ( 'paid' === $status ) {
            wp_update_post(
                [
                    'ID'          => $post_id,
                    'post_status' => 'sp-confirmed',
                ]
            );
        } else {
            wp_update_post(
                [
                    'ID'          => $post_id,
                    'post_status' => 'sp-pending',
                ]
            );
        }
    }

    /**
     * Format for datetime-local.
     */
    protected function format_local_datetime( $value ) {
        if ( empty( $value ) ) {
            return '';
        }

        $timestamp = strtotime( $value );
        if ( ! $timestamp ) {
            return '';
        }

        return gmdate( 'Y-m-d\TH:i', $timestamp );
    }

    /**
     * Admin columns.
     */
    public function columns( $columns ) {
        $columns['sp_booking_number'] = __( 'Booking #', 'sovereign-parking' );
        $columns['sp_booking_cruise'] = __( 'Cruise', 'sovereign-parking' );
        $columns['sp_booking_dates']  = __( 'Dates', 'sovereign-parking' );
        $columns['sp_booking_pass']   = __( 'Passengers', 'sovereign-parking' );
        $columns['sp_booking_payment']= __( 'Payment', 'sovereign-parking' );
        return $columns;
    }

    /**
     * Render column content.
     */
    public function render_column( $column, $post_id ) {
        switch ( $column ) {
            case 'sp_booking_number':
                echo esc_html( get_post_meta( $post_id, '_sp_booking_number', true ) );
                break;
            case 'sp_booking_cruise':
                $cruise_id = get_post_meta( $post_id, '_sp_booking_cruise_id', true );
                if ( $cruise_id ) {
                    $cruise = get_post( $cruise_id );
                    echo esc_html( $cruise ? $cruise->post_title : '' );
                }
                break;
            case 'sp_booking_dates':
                $entry = get_post_meta( $post_id, '_sp_booking_entry', true );
                $exit  = get_post_meta( $post_id, '_sp_booking_exit', true );
                if ( $entry && $exit ) {
                    echo esc_html( get_date_from_gmt( $entry, get_option( 'date_format' ) ) . ' → ' . get_date_from_gmt( $exit, get_option( 'date_format' ) ) );
                }
                break;
            case 'sp_booking_pass':
                echo esc_html( get_post_meta( $post_id, '_sp_booking_passengers', true ) );
                break;
            case 'sp_booking_payment':
                $method = get_post_meta( $post_id, '_sp_booking_payment', true );
                $status = get_post_meta( $post_id, '_sp_booking_payment_status', true );
                echo esc_html( ucfirst( $method ) . ' / ' . $status );
                break;
        }
    }

    /**
     * Sortable columns.
     */
    public function sortable_columns( $columns ) {
        $columns['sp_booking_dates'] = 'sp_booking_dates';
        return $columns;
    }

    /**
     * Apply ordering for custom columns.
     */
    public function sort_columns( $query ) {
        if ( ! is_admin() || ! $query->is_main_query() ) {
            return;
        }

        if ( self::POST_TYPE !== $query->get( 'post_type' ) ) {
            return;
        }

        if ( 'sp_booking_dates' === $query->get( 'orderby' ) ) {
            $query->set( 'meta_key', '_sp_booking_entry' );
            $query->set( 'orderby', 'meta_value' );
        }
    }

    /**
     * Show custom state labels.
     */
    public function post_states( $states, $post ) {
        if ( self::POST_TYPE !== $post->post_type ) {
            return $states;
        }

        $payment_status = get_post_meta( $post->ID, '_sp_booking_payment_status', true );
        if ( $payment_status ) {
            $states[] = sprintf( __( 'Payment: %s', 'sovereign-parking' ), ucfirst( str_replace( '_', ' ', $payment_status ) ) );
        }

        return $states;
    }

    /**
     * Prevent view link from exposing front-end.
     */
    public function prevent_frontend_link( $link, $post ) {
        if ( self::POST_TYPE === $post->post_type ) {
            return admin_url( 'post.php?post=' . $post->ID . '&action=edit' );
        }

        return $link;
    }
}
