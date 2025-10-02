<?php
/**
 * Stripe payment integration.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SP_Payment_Stripe extends SP_Service {

    /**
     * Singleton instance.
     *
     * @var SP_Payment_Stripe
     */
    protected static $instance;

    /**
     * Retrieve singleton instance.
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
     * Add query vars used for confirmation.
     */
    public function register_query_vars( $vars ) {
        $vars[] = 'sp_stripe_confirm';
        $vars[] = 'sp_stripe_session';
        $vars[] = 'sp_booking';
        return $vars;
    }

    /**
     * Create checkout session and return checkout URL.
     */
    public function create_checkout_session( $booking_id, $amount, $mode = 'full', $source_url = '' ) {
        $secret = SP_Helpers::get_option( 'stripe_secret' );
        $publishable = SP_Helpers::get_option( 'stripe_publishable' );

        if ( empty( $secret ) || empty( $publishable ) ) {
            return new WP_Error( 'sp_stripe_keys_missing', __( 'Stripe keys are not configured.', 'sovereign-parking' ) );
        }

        if ( $amount <= 0 ) {
            return new WP_Error( 'sp_invalid_amount', __( 'Invalid amount.', 'sovereign-parking' ) );
        }

        $line_description = 'Sovereign Parking - ' . ( 'full' === $mode ? __( 'Full Payment', 'sovereign-parking' ) : __( 'POA Deposit', 'sovereign-parking' ) );
        $success_url      = add_query_arg(
            [
                'sp_stripe_confirm' => 1,
                'sp_booking'        => $booking_id,
                'sp_stripe_session' => '{CHECKOUT_SESSION_ID}',
                'mode'              => $mode,
            ],
            home_url( '/' )
        );
        $cancel_url       = $source_url ? $source_url : home_url( '/' );

        if ( $source_url ) {
            update_post_meta( $booking_id, '_sp_booking_source_url', esc_url_raw( $source_url ) );
        }

        $body = [
            'mode'                              => 'payment',
            'payment_method_types[]'            => 'card',
            'success_url'                       => $success_url,
            'cancel_url'                        => $cancel_url,
            'line_items[0][price_data][currency]'   => 'aud',
            'line_items[0][price_data][unit_amount]' => intval( round( $amount * 100 ) ),
            'line_items[0][price_data][product_data][name]' => $line_description,
            'line_items[0][quantity]'                 => 1,
            'metadata[booking_id]'                   => $booking_id,
            'metadata[payment_mode]'                 => $mode,
        ];

        $response = wp_remote_post(
            'https://api.stripe.com/v1/checkout/sessions',
            [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode( $secret . ':' ),
                ],
                'body'    => $body,
                'timeout' => 60,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( 200 !== $code ) {
            return new WP_Error( 'sp_stripe_error', isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Stripe error', 'sovereign-parking' ) );
        }

        if ( isset( $data['id'] ) ) {
            update_post_meta( $booking_id, '_sp_stripe_session_id', sanitize_text_field( $data['id'] ) );
            update_post_meta( $booking_id, '_sp_stripe_mode', $mode );
        }

        return [
            'url'           => $data['url'],
            'publishableKey'=> $publishable,
            'sessionId'     => $data['id'],
        ];
    }

    /**
     * Handle Stripe confirmation redirect.
     */
    public function handle_confirmation() {
        if ( ! get_query_var( 'sp_stripe_confirm' ) ) {
            return;
        }

        $booking_id = absint( get_query_var( 'sp_booking' ) );
        $session_id = sanitize_text_field( get_query_var( 'sp_stripe_session' ) );
        $mode       = sanitize_text_field( isset( $_GET['mode'] ) ? wp_unslash( $_GET['mode'] ) : 'full' );

        if ( ! $booking_id || empty( $session_id ) ) {
            wp_safe_redirect( home_url( '/' ) );
            exit;
        }

        $secret = SP_Helpers::get_option( 'stripe_secret' );
        if ( empty( $secret ) ) {
            wp_safe_redirect( home_url( '/' ) );
            exit;
        }

        $response = wp_remote_get(
            'https://api.stripe.com/v1/checkout/sessions/' . rawurlencode( $session_id ),
            [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode( $secret . ':' ),
                ],
                'timeout' => 60,
            ]
        );

        if ( is_wp_error( $response ) ) {
            wp_safe_redirect( home_url( '/' ) );
            exit;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $data ) || empty( $data['payment_status'] ) || 'paid' !== $data['payment_status'] ) {
            $redirect = get_post_meta( $booking_id, '_sp_booking_source_url', true );
            wp_safe_redirect( $redirect ? $redirect : home_url( '/' ) );
            exit;
        }

        $payment_status = get_post_meta( $booking_id, '_sp_booking_payment_status', true );
        $mode_meta      = get_post_meta( $booking_id, '_sp_stripe_mode', true );

        if ( 'poa' === get_post_meta( $booking_id, '_sp_booking_payment', true ) || 'deposit' === $mode || 'deposit' === $mode_meta ) {
            update_post_meta( $booking_id, '_sp_booking_payment_status', 'poa_pending' );
            update_post_meta( $booking_id, '_sp_booking_deposit_status', 'paid' );
            update_post_meta( $booking_id, '_sp_booking_deposit_transaction', $session_id );
            wp_update_post(
                [
                    'ID'          => $booking_id,
                    'post_status' => 'sp-poa-pending',
                ]
            );
        } else {
            update_post_meta( $booking_id, '_sp_booking_payment_status', 'paid' );
            update_post_meta( $booking_id, '_sp_booking_transaction', $session_id );
            wp_update_post(
                [
                    'ID'          => $booking_id,
                    'post_status' => 'sp-confirmed',
                ]
            );
        }

        do_action( 'sp_booking_payment_confirmed', $booking_id );

        $redirect = get_post_meta( $booking_id, '_sp_booking_source_url', true );
        if ( empty( $redirect ) ) {
            $redirect = home_url( '/' );
        }
        wp_safe_redirect( add_query_arg( [ 'sp_booking_confirmed' => 1, 'booking' => $booking_id ], $redirect ) );
        exit;
    }
}
