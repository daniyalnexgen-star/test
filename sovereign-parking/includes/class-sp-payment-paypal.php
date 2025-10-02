<?php
/**
 * PayPal integration.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SP_Payment_Paypal extends SP_Service {

    protected static $instance;

    /**
     * Get singleton instance.
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
        add_filter( 'query_vars', [ $this, 'register_query_vars' ] );
        add_action( 'template_redirect', [ $this, 'handle_confirmation' ] );
    }

    /**
     * Register query vars.
     */
    public function register_query_vars( $vars ) {
        $vars[] = 'sp_paypal_confirm';
        $vars[] = 'sp_paypal_token';
        $vars[] = 'sp_booking';
        return $vars;
    }

    /**
     * Create PayPal order and return approval URL.
     */
    public function create_order( $booking_id, $amount, $source_url = '' ) {
        $client_id = SP_Helpers::get_option( 'paypal_client_id' );
        $secret    = SP_Helpers::get_option( 'paypal_secret' );

        if ( empty( $client_id ) || empty( $secret ) ) {
            return new WP_Error( 'sp_paypal_keys_missing', __( 'PayPal credentials missing.', 'sovereign-parking' ) );
        }

        if ( $amount <= 0 ) {
            return new WP_Error( 'sp_invalid_amount', __( 'Invalid amount.', 'sovereign-parking' ) );
        }

        $token = $this->get_access_token( $client_id, $secret );
        if ( is_wp_error( $token ) ) {
            return $token;
        }

        $success_url = add_query_arg(
            [
                'sp_paypal_confirm' => 1,
                'sp_booking'        => $booking_id,
            ],
            home_url( '/' )
        );
        $cancel_url  = $source_url ? $source_url : home_url( '/' );

        $body = [
            'intent'              => 'CAPTURE',
            'purchase_units'      => [
                [
                    'reference_id' => (string) $booking_id,
                    'amount'       => [
                        'currency_code' => 'AUD',
                        'value'         => number_format( $amount, 2, '.', '' ),
                    ],
                ],
            ],
            'application_context' => [
                'return_url' => $success_url,
                'cancel_url' => $cancel_url,
            ],
        ];

        $response = wp_remote_post(
            'https://api-m.paypal.com/v2/checkout/orders',
            [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ],
                'body'    => wp_json_encode( $body ),
                'timeout' => 60,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $data['id'] ) ) {
            return new WP_Error( 'sp_paypal_error', __( 'Unable to create PayPal order.', 'sovereign-parking' ) );
        }

        update_post_meta( $booking_id, '_sp_paypal_order_id', sanitize_text_field( $data['id'] ) );
        if ( $source_url ) {
            update_post_meta( $booking_id, '_sp_booking_source_url', esc_url_raw( $source_url ) );
        }

        foreach ( $data['links'] as $link ) {
            if ( 'approve' === $link['rel'] ) {
                return [ 'url' => $link['href'] ];
            }
        }

        return new WP_Error( 'sp_paypal_no_approval', __( 'PayPal approval link missing.', 'sovereign-parking' ) );
    }

    /**
     * Handle PayPal confirmation.
     */
    public function handle_confirmation() {
        if ( ! get_query_var( 'sp_paypal_confirm' ) ) {
            return;
        }

        $booking_id = absint( get_query_var( 'sp_booking' ) );
        $token      = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

        if ( ! $booking_id || empty( $token ) ) {
            wp_safe_redirect( home_url( '/' ) );
            exit;
        }

        $client_id = SP_Helpers::get_option( 'paypal_client_id' );
        $secret    = SP_Helpers::get_option( 'paypal_secret' );
        $access    = $this->get_access_token( $client_id, $secret );
        if ( is_wp_error( $access ) ) {
            wp_safe_redirect( home_url( '/' ) );
            exit;
        }

        $response = wp_remote_post(
            'https://api-m.paypal.com/v2/checkout/orders/' . rawurlencode( $token ) . '/capture',
            [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $access,
                ],
                'timeout' => 60,
            ]
        );

        if ( is_wp_error( $response ) ) {
            wp_safe_redirect( home_url( '/' ) );
            exit;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $data['status'] ) || 'COMPLETED' !== $data['status'] ) {
            wp_safe_redirect( home_url( '/' ) );
            exit;
        }

        update_post_meta( $booking_id, '_sp_booking_payment_status', 'paid' );
        update_post_meta( $booking_id, '_sp_booking_payment', 'paypal' );
        update_post_meta( $booking_id, '_sp_paypal_transaction', sanitize_text_field( $token ) );
        wp_update_post(
            [
                'ID'          => $booking_id,
                'post_status' => 'sp-confirmed',
            ]
        );

        do_action( 'sp_booking_payment_confirmed', $booking_id );

        $redirect = get_post_meta( $booking_id, '_sp_booking_source_url', true );
        if ( empty( $redirect ) ) {
            $redirect = home_url( '/' );
        }
        wp_safe_redirect( add_query_arg( [ 'sp_booking_confirmed' => 1, 'booking' => $booking_id ], $redirect ) );
        exit;
    }

    /**
     * Retrieve access token from PayPal.
     */
    protected function get_access_token( $client_id, $secret ) {
        $response = wp_remote_post(
            'https://api-m.paypal.com/v1/oauth2/token',
            [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $secret ),
                ],
                'body'    => [ 'grant_type' => 'client_credentials' ],
                'timeout' => 60,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $data['access_token'] ) ) {
            return new WP_Error( 'sp_paypal_token', __( 'Unable to authenticate with PayPal.', 'sovereign-parking' ) );
        }

        return $data['access_token'];
    }
}
