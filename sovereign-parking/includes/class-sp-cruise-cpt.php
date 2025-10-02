<?php
/**
 * Cruise custom post type.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SP_Cruise_CPT extends SP_Service {

    const POST_TYPE = 'sp_cruise';

    /**
     * Register hooks.
     */
    public function register_hooks() {
        add_action( 'init', [ $this, 'register_cpt' ] );
        add_action( 'init', [ $this, 'register_meta' ] );
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post_' . self::POST_TYPE, [ $this, 'save_meta' ], 10, 2 );
        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ $this, 'columns' ] );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ $this, 'render_column' ], 10, 2 );
        add_filter( 'post_updated_messages', [ $this, 'updated_messages' ] );
    }

    /**
     * Register cruise post type.
     */
    public function register_cpt() {
        $labels = [
            'name'               => __( 'Cruises', 'sovereign-parking' ),
            'singular_name'      => __( 'Cruise', 'sovereign-parking' ),
            'add_new'            => __( 'Add Cruise', 'sovereign-parking' ),
            'add_new_item'       => __( 'Add New Cruise', 'sovereign-parking' ),
            'edit_item'          => __( 'Edit Cruise', 'sovereign-parking' ),
            'new_item'           => __( 'New Cruise', 'sovereign-parking' ),
            'view_item'          => __( 'View Cruise', 'sovereign-parking' ),
            'search_items'       => __( 'Search Cruises', 'sovereign-parking' ),
            'not_found'          => __( 'No cruises found', 'sovereign-parking' ),
            'not_found_in_trash' => __( 'No cruises found in Trash', 'sovereign-parking' ),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'supports'           => [ 'title' ],
            'capability_type'    => [ 'sp_cruise', 'sp_cruises' ],
            'map_meta_cap'       => true,
        ];

        register_post_type( self::POST_TYPE, $args );
    }

    /**
     * Register meta fields for cruises.
     */
    public function register_meta() {
        $meta_args = [
            'type'         => 'string',
            'single'       => true,
            'show_in_rest' => true,
            'auth_callback'=> '__return_true',
        ];

        register_post_meta( self::POST_TYPE, '_sp_cruise_line', $meta_args );
        register_post_meta( self::POST_TYPE, '_sp_departure_datetime', $meta_args );
        register_post_meta( self::POST_TYPE, '_sp_return_datetime', $meta_args );
        register_post_meta( self::POST_TYPE, '_sp_ship_name', $meta_args );
    }

    /**
     * Add meta boxes.
     */
    public function add_meta_boxes() {
        add_meta_box( 'sp_cruise_details', __( 'Cruise Details', 'sovereign-parking' ), [ $this, 'render_meta_box' ], self::POST_TYPE, 'normal', 'high' );
    }

    /**
     * Render cruise details meta box.
     */
    public function render_meta_box( $post ) {
        wp_nonce_field( 'sp_save_cruise', 'sp_cruise_nonce' );

        $cruise_line = get_post_meta( $post->ID, '_sp_cruise_line', true );
        $ship_name   = get_post_meta( $post->ID, '_sp_ship_name', true );
        $departure   = get_post_meta( $post->ID, '_sp_departure_datetime', true );
        $return      = get_post_meta( $post->ID, '_sp_return_datetime', true );
        ?>
        <p>
            <label for="sp_cruise_line"><strong><?php esc_html_e( 'Cruise Line', 'sovereign-parking' ); ?></strong></label><br />
            <input type="text" class="regular-text" name="sp_cruise_line" id="sp_cruise_line" value="<?php echo esc_attr( $cruise_line ); ?>" />
        </p>
        <p>
            <label for="sp_ship_name"><strong><?php esc_html_e( 'Ship Name', 'sovereign-parking' ); ?></strong></label><br />
            <input type="text" class="regular-text" name="sp_ship_name" id="sp_ship_name" value="<?php echo esc_attr( $ship_name ); ?>" />
        </p>
        <p>
            <label for="sp_departure"><strong><?php esc_html_e( 'Departure Date & Time', 'sovereign-parking' ); ?></strong></label><br />
            <input type="datetime-local" class="regular-text" name="sp_departure" id="sp_departure" value="<?php echo esc_attr( $this->format_local_datetime( $departure ) ); ?>" />
        </p>
        <p>
            <label for="sp_return"><strong><?php esc_html_e( 'Return Date & Time', 'sovereign-parking' ); ?></strong></label><br />
            <input type="datetime-local" class="regular-text" name="sp_return" id="sp_return" value="<?php echo esc_attr( $this->format_local_datetime( $return ) ); ?>" />
        </p>
        <?php
    }

    /**
     * Save cruise details.
     */
    public function save_meta( $post_id, $post ) {
        if ( ! isset( $_POST['sp_cruise_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sp_cruise_nonce'] ) ), 'sp_save_cruise' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( self::POST_TYPE !== $post->post_type ) {
            return;
        }

        $cruise_line = isset( $_POST['sp_cruise_line'] ) ? sanitize_text_field( wp_unslash( $_POST['sp_cruise_line'] ) ) : '';
        $ship_name   = isset( $_POST['sp_ship_name'] ) ? sanitize_text_field( wp_unslash( $_POST['sp_ship_name'] ) ) : '';
        $departure   = isset( $_POST['sp_departure'] ) ? SP_Helpers::sanitize_datetime( wp_unslash( $_POST['sp_departure'] ) ) : '';
        $return      = isset( $_POST['sp_return'] ) ? SP_Helpers::sanitize_datetime( wp_unslash( $_POST['sp_return'] ) ) : '';

        update_post_meta( $post_id, '_sp_cruise_line', $cruise_line );
        update_post_meta( $post_id, '_sp_ship_name', $ship_name );
        if ( $departure ) {
            update_post_meta( $post_id, '_sp_departure_datetime', $departure );
        }
        if ( $return ) {
            update_post_meta( $post_id, '_sp_return_datetime', $return );
        }
    }

    /**
     * Format GMT datetime for datetime-local input.
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
     * Columns for admin list.
     */
    public function columns( $columns ) {
        $columns['sp_cruise_line'] = __( 'Cruise Line', 'sovereign-parking' );
        $columns['sp_ship_name']   = __( 'Ship', 'sovereign-parking' );
        $columns['sp_departure']   = __( 'Departure', 'sovereign-parking' );
        $columns['sp_return']      = __( 'Return', 'sovereign-parking' );
        return $columns;
    }

    /**
     * Render column values.
     */
    public function render_column( $column, $post_id ) {
        switch ( $column ) {
            case 'sp_cruise_line':
                echo esc_html( get_post_meta( $post_id, '_sp_cruise_line', true ) );
                break;
            case 'sp_ship_name':
                echo esc_html( get_post_meta( $post_id, '_sp_ship_name', true ) );
                break;
            case 'sp_departure':
                $departure = get_post_meta( $post_id, '_sp_departure_datetime', true );
                echo esc_html( $departure ? get_date_from_gmt( $departure, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) : '—' );
                break;
            case 'sp_return':
                $return = get_post_meta( $post_id, '_sp_return_datetime', true );
                echo esc_html( $return ? get_date_from_gmt( $return, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) : '—' );
                break;
        }
    }

    /**
     * Update messages.
     */
    public function updated_messages( $messages ) {
        $messages[ self::POST_TYPE ] = [
            0  => '',
            1  => __( 'Cruise updated.', 'sovereign-parking' ),
            6  => __( 'Cruise published.', 'sovereign-parking' ),
            7  => __( 'Cruise saved.', 'sovereign-parking' ),
        ];
        return $messages;
    }
}
