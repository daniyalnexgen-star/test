<?php
/**
 * Customer credit ledger manager.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SP_Customer_Credits extends SP_Service {

    const TABLE = 'sp_credit_ledger';

    /**
     * Singleton instance.
     *
     * @var SP_Customer_Credits
     */
    protected static $instance;

    /**
     * Retrieve singleton.
     */
    public static function instance() {
        if ( null === static::$instance ) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Register hooks.
     */
    public function register_hooks() {
        add_action( 'init', [ $this, 'process_form' ] );
    }

    /**
     * Activation - create table.
     */
    public function activate() {
        global $wpdb;
        $table   = $wpdb->prefix . self::TABLE;
        $charset = $wpdb->get_charset_collate();
        $sql     = "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            booking_id BIGINT(20) UNSIGNED DEFAULT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            description TEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Process credit adjustments from admin.
     */
    public function process_form() {
        if ( ! is_admin() || ! isset( $_POST['sp_credit_action'] ) ) {
            return;
        }

        if ( ! current_user_can( 'edit_sp_bookings' ) ) {
            return;
        }

        if ( ! isset( $_POST['sp_add_credit_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sp_add_credit_nonce'] ) ), 'sp_add_credit' ) ) {
            return;
        }

        $user_id     = isset( $_POST['sp_credit_customer'] ) ? intval( $_POST['sp_credit_customer'] ) : 0;
        $amount      = isset( $_POST['sp_credit_amount'] ) ? floatval( $_POST['sp_credit_amount'] ) : 0;
        $description = isset( $_POST['sp_credit_description'] ) ? sanitize_text_field( wp_unslash( $_POST['sp_credit_description'] ) ) : '';

        if ( ! $user_id || 0 === $amount ) {
            return;
        }

        $this->add_credit( $user_id, $amount, $description );
        wp_safe_redirect( add_query_arg( [ 'page' => 'sp-credits', 'updated' => 'true' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    /**
     * Add credit entry and update user balance.
     */
    public function add_credit( $user_id, $amount, $description = '', $booking_id = null ) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;

        $wpdb->insert(
            $table,
            [
                'user_id'    => $user_id,
                'booking_id' => $booking_id,
                'amount'     => $amount,
                'description'=> $description,
                'created_at' => current_time( 'mysql', true ),
            ],
            [ '%d', '%d', '%f', '%s', '%s' ]
        );

        $balance = $this->get_balance( $user_id ) + $amount;
        update_user_meta( $user_id, '_sp_credit_balance', $balance );
    }

    /**
     * Deduct credit when used.
     */
    public function deduct_credit( $user_id, $amount, $booking_id = null ) {
        $this->add_credit( $user_id, -abs( $amount ), __( 'Applied to booking', 'sovereign-parking' ), $booking_id );
    }

    /**
     * Get balance for user.
     */
    public function get_balance( $user_id ) {
        $balance = get_user_meta( $user_id, '_sp_credit_balance', true );
        return $balance ? floatval( $balance ) : 0.0;
    }

    /**
     * Recent ledger entries.
     */
    public function get_recent_ledger( $limit = 20 ) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d", $limit ) );
    }
}
