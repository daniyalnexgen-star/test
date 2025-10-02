<?php
/**
 * Shuttle slot custom post type management.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SP_Shuttle_Slot_CPT extends SP_Service {

    const POST_TYPE = 'sp_shuttle_slot';

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
    }

    /**
     * Register CPT.
     */
    public function register_cpt() {
        $labels = [
            'name'          => __( 'Shuttle Slots', 'sovereign-parking' ),
            'singular_name' => __( 'Shuttle Slot', 'sovereign-parking' ),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'supports'           => [ 'title' ],
            'capability_type'    => [ 'sp_shuttle_slot', 'sp_shuttle_slots' ],
            'map_meta_cap'       => true,
        ];

        register_post_type( self::POST_TYPE, $args );
    }

    /**
     * Register meta fields.
     */
    public function register_meta() {
        $meta_args = [
            'type'         => 'string',
            'single'       => true,
            'show_in_rest' => true,
            'auth_callback'=> '__return_true',
        ];

        register_post_meta( self::POST_TYPE, '_sp_slot_start', $meta_args );
        register_post_meta( self::POST_TYPE, '_sp_slot_end', $meta_args );
        register_post_meta( self::POST_TYPE, '_sp_slot_capacity', [
            'type'         => 'integer',
            'single'       => true,
            'show_in_rest' => true,
            'auth_callback'=> '__return_true',
        ] );
    }

    /**
     * Add meta box.
     */
    public function add_meta_boxes() {
        add_meta_box( 'sp_slot_details', __( 'Slot Details', 'sovereign-parking' ), [ $this, 'render_meta_box' ], self::POST_TYPE, 'normal', 'high' );
    }

    /**
     * Render meta box.
     */
    public function render_meta_box( $post ) {
        wp_nonce_field( 'sp_save_slot', 'sp_slot_nonce' );
        $start    = get_post_meta( $post->ID, '_sp_slot_start', true );
        $end      = get_post_meta( $post->ID, '_sp_slot_end', true );
        $capacity = get_post_meta( $post->ID, '_sp_slot_capacity', true );
        ?>
        <p>
            <label for="sp_slot_start"><strong><?php esc_html_e( 'Start Time (HH:MM)', 'sovereign-parking' ); ?></strong></label><br />
            <input type="time" name="sp_slot_start" id="sp_slot_start" value="<?php echo esc_attr( $start ); ?>" required />
        </p>
        <p>
            <label for="sp_slot_end"><strong><?php esc_html_e( 'End Time (HH:MM)', 'sovereign-parking' ); ?></strong></label><br />
            <input type="time" name="sp_slot_end" id="sp_slot_end" value="<?php echo esc_attr( $end ); ?>" required />
        </p>
        <p>
            <label for="sp_slot_capacity"><strong><?php esc_html_e( 'Capacity', 'sovereign-parking' ); ?></strong></label><br />
            <input type="number" name="sp_slot_capacity" id="sp_slot_capacity" value="<?php echo esc_attr( $capacity ?: 11 ); ?>" min="1" />
        </p>
        <?php
    }

    /**
     * Save slot details.
     */
    public function save_meta( $post_id, $post ) {
        if ( ! isset( $_POST['sp_slot_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sp_slot_nonce'] ) ), 'sp_save_slot' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( self::POST_TYPE !== $post->post_type ) {
            return;
        }

        $start    = isset( $_POST['sp_slot_start'] ) ? sanitize_text_field( wp_unslash( $_POST['sp_slot_start'] ) ) : '';
        $end      = isset( $_POST['sp_slot_end'] ) ? sanitize_text_field( wp_unslash( $_POST['sp_slot_end'] ) ) : '';
        $capacity = isset( $_POST['sp_slot_capacity'] ) ? intval( $_POST['sp_slot_capacity'] ) : 11;

        update_post_meta( $post_id, '_sp_slot_start', $start );
        update_post_meta( $post_id, '_sp_slot_end', $end );
        update_post_meta( $post_id, '_sp_slot_capacity', max( 1, $capacity ) );
    }

    /**
     * Columns.
     */
    public function columns( $columns ) {
        $columns['sp_slot_times']    = __( 'Time Window', 'sovereign-parking' );
        $columns['sp_slot_capacity'] = __( 'Capacity', 'sovereign-parking' );
        return $columns;
    }

    /**
     * Render columns.
     */
    public function render_column( $column, $post_id ) {
        switch ( $column ) {
            case 'sp_slot_times':
                $start = get_post_meta( $post_id, '_sp_slot_start', true );
                $end   = get_post_meta( $post_id, '_sp_slot_end', true );
                echo esc_html( trim( $start . ' – ' . $end, ' –' ) );
                break;
            case 'sp_slot_capacity':
                $capacity = get_post_meta( $post_id, '_sp_slot_capacity', true );
                echo esc_html( $capacity ?: 11 );
                break;
        }
    }

    /**
     * Seed default slots on activation.
     */
    public function activate() {
        $existing = get_posts(
            [
                'post_type'      => self::POST_TYPE,
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'fields'         => 'ids',
            ]
        );

        if ( ! empty( $existing ) ) {
            return;
        }

        foreach ( SP_Helpers::default_shuttle_slots() as $key => $slot ) {
            $post_id = wp_insert_post(
                [
                    'post_type'   => self::POST_TYPE,
                    'post_title'  => $slot['label'],
                    'post_status' => 'publish',
                ]
            );

            if ( $post_id && ! is_wp_error( $post_id ) ) {
                update_post_meta( $post_id, '_sp_slot_start', $slot['start'] );
                update_post_meta( $post_id, '_sp_slot_end', $slot['end'] );
                update_post_meta( $post_id, '_sp_slot_capacity', $slot['capacity'] );
            }
        }
    }
}
