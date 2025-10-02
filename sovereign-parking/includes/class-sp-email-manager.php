<?php
/**
 * Email templates and sending logic.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SP_Email_Manager extends SP_Service {

    /**
     * Register hooks.
     */
    public function register_hooks() {
        add_action( 'sp_booking_payment_confirmed', [ $this, 'send_confirmation' ] );
    }

    /**
     * Send confirmation email.
     */
    public function send_confirmation( $booking_id ) {
        $email = get_post_meta( $booking_id, '_sp_booking_email', true );
        if ( empty( $email ) ) {
            return;
        }

        $subject = __( 'Your Sovereign Parking Booking is Confirmed', 'sovereign-parking' );
        $headers = [];
        $from    = SP_Helpers::get_option( 'confirmation_email', get_bloginfo( 'admin_email' ) );
        if ( $from ) {
            $headers[] = 'From: Sovereign Parking <' . sanitize_email( $from ) . '>';
            $headers[] = 'Reply-To: Sovereign Parking <hello@sovereignparking.com.au>';
        }

        $body = $this->build_email_body( $booking_id );
        wp_mail( $email, $subject, $body, array_merge( $headers, [ 'Content-Type: text/html; charset=UTF-8' ] ) );
    }

    /**
     * Build confirmation email body based on spec.
     */
    protected function build_email_body( $booking_id ) {
        $customer_name = get_post_field( 'post_title', $booking_id );
        $cruise_id     = get_post_meta( $booking_id, '_sp_booking_cruise_id', true );
        $cruise_name   = $cruise_id ? get_the_title( $cruise_id ) : '';
        $entry         = get_post_meta( $booking_id, '_sp_booking_entry', true );
        $exit          = get_post_meta( $booking_id, '_sp_booking_exit', true );
        $slot_id       = get_post_meta( $booking_id, '_sp_booking_shuttle_id', true );
        $slot_label    = $slot_id ? get_the_title( $slot_id ) : '';
        $passengers    = get_post_meta( $booking_id, '_sp_booking_passengers', true );
        $vehicle       = get_post_meta( $booking_id, '_sp_booking_vehicle', true );
        $booking_no    = get_post_meta( $booking_id, '_sp_booking_number', true );
        $entry_human   = $entry ? get_date_from_gmt( $entry, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) : '';
        $exit_human    = $exit ? get_date_from_gmt( $exit, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) : '';

        ob_start();
        ?>
        <div style="font-family:Arial, sans-serif; font-size:14px; color:#222;">
            <p><?php printf( __( 'Dear %s,', 'sovereign-parking' ), esc_html( $customer_name ) ); ?></p>
            <p><?php esc_html_e( "That’s it! You’ve officially ticked parking off your list – your spot at Sovereign Parking is confirmed and ready for you.", 'sovereign-parking' ); ?></p>
            <p><?php esc_html_e( 'On cruise day, just head to 9 Harris Rd, Pinkenba. Drive straight into our concreted all-weather drop-off area, where our team will get you sorted quickly and easily. Feel free to relax in our comfy waiting room, grab a tea or coffee, and take five while we handle the rest. Once we are loaded, it’s only a quick 4km trip to the terminal.', 'sovereign-parking' ); ?></p>
            <p><?php esc_html_e( 'Your car is in good hands so you can put your energy where it matters – starting your holiday calm, stress-free, and on time.', 'sovereign-parking' ); ?></p>
            <p><?php esc_html_e( 'If you have any questions or need to update your booking, please DO NOT reply to this email. Email hello@sovereignparking.com.au or call or text us on 0468 472 757. We’re here to help.', 'sovereign-parking' ); ?></p>
            <p><?php esc_html_e( 'See you soon,', 'sovereign-parking' ); ?><br /><?php esc_html_e( 'The Sovereign Parking Team', 'sovereign-parking' ); ?></p>
            <h3><?php esc_html_e( 'Booking Summary', 'sovereign-parking' ); ?></h3>
            <ul>
                <li><strong><?php esc_html_e( 'Booking Number:', 'sovereign-parking' ); ?></strong> <?php echo esc_html( $booking_no ); ?></li>
                <li><strong><?php esc_html_e( 'Cruise:', 'sovereign-parking' ); ?></strong> <?php echo esc_html( $cruise_name ); ?></li>
                <li><strong><?php esc_html_e( 'Entry:', 'sovereign-parking' ); ?></strong> <?php echo esc_html( $entry_human ); ?></li>
                <li><strong><?php esc_html_e( 'Exit:', 'sovereign-parking' ); ?></strong> <?php echo esc_html( $exit_human ); ?></li>
                <li><strong><?php esc_html_e( 'Shuttle Slot:', 'sovereign-parking' ); ?></strong> <?php echo esc_html( $slot_label ); ?></li>
                <li><strong><?php esc_html_e( 'Passengers:', 'sovereign-parking' ); ?></strong> <?php echo esc_html( $passengers ); ?></li>
                <li><strong><?php esc_html_e( 'Vehicle Plate:', 'sovereign-parking' ); ?></strong> <?php echo esc_html( $vehicle ); ?></li>
            </ul>
            <hr />
            <h3><?php esc_html_e( 'Important Information', 'sovereign-parking' ); ?></h3>
            <p><strong><?php esc_html_e( 'Shuttle Transfers – Courtesy Note', 'sovereign-parking' ); ?></strong><br /><?php esc_html_e( 'During peak times, we kindly suggest dropping passengers and luggage at the cruise terminal before parking. This courtesy step helps free up shuttle capacity, allowing more transfers to run smoothly and making additional time slots available for all guests.', 'sovereign-parking' ); ?></p>
            <p><strong><?php esc_html_e( 'Timing Disclaimer', 'sovereign-parking' ); ?></strong><br /><?php esc_html_e( 'We do our best to get you to the cruise terminal within your selected shuttle time slot. However, we cannot control external factors such as traffic congestion or roadworks. Access to the terminal is via a single road in and out, so while rare, delays may occur.', 'sovereign-parking' ); ?></p>
            <p><strong><?php esc_html_e( 'Accessibility', 'sovereign-parking' ); ?></strong><br /><?php esc_html_e( 'Currently, our car park does not have accommodations for guests with mobility limitations. If you’re travelling with someone who needs assistance, we recommend dropping them at the cruise terminal before parking.', 'sovereign-parking' ); ?></p>
            <p><strong><?php esc_html_e( 'Passenger & Luggage Drop-off', 'sovereign-parking' ); ?></strong><br /><?php esc_html_e( 'Where possible, please drop passengers and luggage at the terminal first (see courtesy note above).', 'sovereign-parking' ); ?></p>
            <p><strong><?php esc_html_e( 'Parking Days & Hours', 'sovereign-parking' ); ?></strong><br /><?php esc_html_e( 'We operate 6:00am–2:00pm and only on days when cruises are scheduled. To check availability, see the Cruise Ship Schedule on our website.', 'sovereign-parking' ); ?></p>
            <p><strong><?php esc_html_e( 'Keys', 'sovereign-parking' ); ?></strong><br /><?php esc_html_e( 'For insurance and liability purposes, our staff will park your vehicle on your behalf. We’ll collect your keys on arrival to ensure proper handling and security.', 'sovereign-parking' ); ?></p>
            <p><strong><?php esc_html_e( 'Refund & Cancellations Policy', 'sovereign-parking' ); ?></strong><br /><?php esc_html_e( 'We understand that plans can change, so we encourage you to carefully review your booking details and parking location before confirming.', 'sovereign-parking' ); ?></p>
            <ul>
                <li><?php esc_html_e( 'Incorrect Bookings / Wrong Location: Unfortunately, we are unable to provide refunds for bookings made in error or for arriving at the wrong parking location.', 'sovereign-parking' ); ?></li>
                <li><?php esc_html_e( 'Cancellations Within 48 Hours: As we prepare in advance for your arrival, refunds are not available within 48 hours of your booking start time.', 'sovereign-parking' ); ?></li>
                <li><?php esc_html_e( 'Stripe Fees & Deposits: For fully paid bookings, any refund will have third-party Stripe processing fees deducted. The $10 holding deposit for Pay on Arrival bookings is non-refundable.', 'sovereign-parking' ); ?></li>
                <li><?php esc_html_e( 'Credits: Where a refund is not possible, we are happy to issue a credit to your Sovereign Parking account. Credits can be applied to your next booking and never expire.', 'sovereign-parking' ); ?></li>
            </ul>
            <p><?php esc_html_e( 'Harris Road is a cul-de-sac, and we are the last business on the left. Look for the pink flags!', 'sovereign-parking' ); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }
}
