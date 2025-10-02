<?php
/**
 * Plugin settings page.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SP_Settings extends SP_Service {

    /**
     * Register hooks.
     */
    public function register_hooks() {
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    /**
     * Register plugin settings.
     */
    public function register_settings() {
        register_setting( 'sp_settings_group', 'sp_settings', [ $this, 'sanitize_settings' ] );

        add_settings_section( 'sp_payments', __( 'Payment Settings', 'sovereign-parking' ), '__return_false', 'sp_settings' );

        add_settings_field( 'stripe_publishable', __( 'Stripe Publishable Key', 'sovereign-parking' ), [ $this, 'text_field' ], 'sp_settings', 'sp_payments', [ 'key' => 'stripe_publishable' ] );
        add_settings_field( 'stripe_secret', __( 'Stripe Secret Key', 'sovereign-parking' ), [ $this, 'text_field' ], 'sp_settings', 'sp_payments', [ 'key' => 'stripe_secret' ] );
        add_settings_field( 'paypal_client_id', __( 'PayPal Client ID', 'sovereign-parking' ), [ $this, 'text_field' ], 'sp_settings', 'sp_payments', [ 'key' => 'paypal_client_id' ] );
        add_settings_field( 'paypal_secret', __( 'PayPal Secret', 'sovereign-parking' ), [ $this, 'text_field' ], 'sp_settings', 'sp_payments', [ 'key' => 'paypal_secret' ] );
        add_settings_field( 'poa_deposit_amount', __( 'POA Deposit Amount', 'sovereign-parking' ), [ $this, 'number_field' ], 'sp_settings', 'sp_payments', [ 'key' => 'poa_deposit_amount', 'default' => 10 ] );
        add_settings_field( 'confirmation_email', __( 'Confirmation Email Sender', 'sovereign-parking' ), [ $this, 'text_field' ], 'sp_settings', 'sp_payments', [ 'key' => 'confirmation_email', 'description' => __( 'Email address used as the from/reply for confirmations.', 'sovereign-parking' ) ] );
    }

    /**
     * Sanitize settings.
     */
    public function sanitize_settings( $values ) {
        $clean = [];
        $fields = [
            'stripe_publishable' => 'sanitize_text_field',
            'stripe_secret'      => 'sanitize_text_field',
            'paypal_client_id'   => 'sanitize_text_field',
            'paypal_secret'      => 'sanitize_text_field',
            'poa_deposit_amount' => 'floatval',
            'confirmation_email' => 'sanitize_email',
        ];

        foreach ( $fields as $key => $callback ) {
            if ( isset( $values[ $key ] ) ) {
                $clean[ $key ] = call_user_func( $callback, $values[ $key ] );
            }
        }

        return $clean;
    }

    /**
     * Render text field.
     */
    public function text_field( $args ) {
        $options = get_option( 'sp_settings', [] );
        $key     = $args['key'];
        $value   = isset( $options[ $key ] ) ? $options[ $key ] : '';
        printf( '<input type="text" class="regular-text" name="sp_settings[%1$s]" value="%2$s" />', esc_attr( $key ), esc_attr( $value ) );
        if ( ! empty( $args['description'] ) ) {
            printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
        }
    }

    /**
     * Render number field.
     */
    public function number_field( $args ) {
        $options = get_option( 'sp_settings', [] );
        $key     = $args['key'];
        $value   = isset( $options[ $key ] ) ? $options[ $key ] : ( isset( $args['default'] ) ? $args['default'] : 0 );
        printf( '<input type="number" step="0.01" class="small-text" name="sp_settings[%1$s]" value="%2$s" />', esc_attr( $key ), esc_attr( $value ) );
    }
}
