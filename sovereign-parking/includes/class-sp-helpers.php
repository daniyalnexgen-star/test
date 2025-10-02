<?php
/**
 * Shared helper utilities.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SP_Helpers {

    /**
     * Calculate price based on number of days.
     *
     * @param int $days Number of days.
     *
     * @return float
     */
    public static function calculate_price( $days ) {
        $pricing = [
            1  => 69,
            2  => 89,
            3  => 99,
            4  => 109,
            5  => 119,
            6  => 129,
            7  => 139,
            8  => 149,
            9  => 159,
            10 => 169,
            11 => 179,
            12 => 189,
            13 => 199,
            14 => 209,
            15 => 219,
            16 => 229,
            17 => 239,
            18 => 249,
            19 => 259,
            20 => 269,
            21 => 279,
            22 => 289,
            23 => 299,
            24 => 309,
            25 => 319,
            26 => 329,
            27 => 339,
            28 => 349,
            29 => 359,
        ];

        if ( $days >= 30 ) {
            return 0;
        }

        if ( isset( $pricing[ $days ] ) ) {
            return (float) $pricing[ $days ];
        }

        if ( $days > 20 && $days < 30 ) {
            return 269;
        }

        return 0;
    }

    /**
     * Sanitize datetime string.
     *
     * @param string $value Raw value.
     *
     * @return string|null
     */
    public static function sanitize_datetime( $value ) {
        $value = sanitize_text_field( $value );

        if ( empty( $value ) ) {
            return null;
        }

        $timestamp = strtotime( $value );
        if ( false === $timestamp ) {
            return null;
        }

        return gmdate( 'Y-m-d H:i:s', $timestamp );
    }

    /**
     * Convert two date strings into day count.
     *
     * @param string $entry Entry datetime.
     * @param string $exit  Exit datetime.
     *
     * @return int
     */
    public static function calculate_days( $entry, $exit ) {
        $entry_ts = strtotime( $entry );
        $exit_ts  = strtotime( $exit );

        if ( ! $entry_ts || ! $exit_ts ) {
            return 0;
        }

        $diff = $exit_ts - $entry_ts;
        if ( $diff < 0 ) {
            return 0;
        }

        return (int) ceil( $diff / DAY_IN_SECONDS );
    }

    /**
     * Retrieve plugin option value.
     *
     * @param string $key     Key.
     * @param mixed  $default Default.
     *
     * @return mixed
     */
    public static function get_option( $key, $default = '' ) {
        $options = get_option( 'sp_settings', [] );

        if ( isset( $options[ $key ] ) && '' !== $options[ $key ] ) {
            return $options[ $key ];
        }

        return $default;
    }

    /**
     * Update plugin option.
     *
     * @param string $key   Key.
     * @param mixed  $value Value.
     */
    public static function update_option( $key, $value ) {
        $options = get_option( 'sp_settings', [] );
        $options[ $key ] = $value;
        update_option( 'sp_settings', $options );
    }

    /**
     * Format currency.
     *
     * @param float $amount Amount.
     *
     * @return string
     */
    public static function format_currency( $amount ) {
        return sprintf( '$%s', number_format_i18n( $amount, 2 ) );
    }

    /**
     * Generate booking number.
     *
     * @return string
     */
    public static function generate_booking_number() {
        return strtoupper( 'SP-' . wp_generate_password( 8, false, false ) );
    }

    /**
     * Return default shuttle slots.
     *
     * @return array
     */
    public static function default_shuttle_slots() {
        $slots = [
            [ 'start' => '09:30', 'end' => '09:49' ],
            [ 'start' => '09:50', 'end' => '10:09' ],
            [ 'start' => '10:10', 'end' => '10:29' ],
            [ 'start' => '10:30', 'end' => '10:49' ],
            [ 'start' => '10:50', 'end' => '11:09' ],
            [ 'start' => '11:10', 'end' => '11:29' ],
            [ 'start' => '11:30', 'end' => '11:49' ],
            [ 'start' => '11:50', 'end' => '12:09' ],
            [ 'start' => '12:10', 'end' => '12:29' ],
            [ 'start' => '12:30', 'end' => '12:49' ],
            [ 'start' => '12:50', 'end' => '13:09' ],
            [ 'start' => '13:10', 'end' => '13:29' ],
            [ 'start' => '13:30', 'end' => '13:49' ],
        ];

        $normalized = [];
        foreach ( $slots as $slot ) {
            $key            = str_replace( ':', '', $slot['start'] ) . '-' . str_replace( ':', '', $slot['end'] );
            $normalized[ $key ] = [
                'label'    => sprintf( '%s–%s', $slot['start'], $slot['end'] ),
                'start'    => $slot['start'],
                'end'      => $slot['end'],
                'capacity' => 11,
            ];
        }

        return $normalized;
    }
}
