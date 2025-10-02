<?php
/**
 * Calendar overview template.
 */
$month = isset( $_GET['month'] ) ? intval( $_GET['month'] ) : gmdate( 'n' );
$year  = isset( $_GET['year'] ) ? intval( $_GET['year'] ) : gmdate( 'Y' );
$start = strtotime( sprintf( '%d-%02d-01', $year, $month ) );
$end   = strtotime( sprintf( '%d-%02d-%02d 23:59:59', $year, $month, gmdate( 't', $start ) ) );

$bookings = get_posts(
    [
        'post_type'      => SP_Booking_CPT::POST_TYPE,
        'post_status'    => [ 'sp-confirmed', 'sp-poa-pending', 'sp-poa-paid', 'sp-pending' ],
        'posts_per_page' => -1,
        'meta_query'     => [
            [
                'key'     => '_sp_booking_entry',
                'value'   => [ gmdate( 'Y-m-d H:i:s', $start ), gmdate( 'Y-m-d H:i:s', $end ) ],
                'compare' => 'BETWEEN',
                'type'    => 'DATETIME',
            ],
        ],
    ]
);

$calendar = [];
foreach ( $bookings as $booking ) {
    $entry = get_post_meta( $booking->ID, '_sp_booking_entry', true );
    if ( ! $entry ) {
        continue;
    }
    $day = gmdate( 'j', strtotime( $entry ) );
    if ( ! isset( $calendar[ $day ] ) ) {
        $calendar[ $day ] = [
            'count'     => 0,
            'bookings'  => [],
            'poa'       => 0,
            'confirmed' => 0,
        ];
    }
    $calendar[ $day ]['count']++;
    $payment_status = get_post_meta( $booking->ID, '_sp_booking_payment_status', true );
    if ( 'poa_pending' === $payment_status || 'poa_paid' === $payment_status ) {
        $calendar[ $day ]['poa']++;
    }
    if ( 'paid' === $payment_status ) {
        $calendar[ $day ]['confirmed']++;
    }
    $calendar[ $day ]['bookings'][] = $booking;
}
?>
<div class="wrap sp-calendar">
    <h1><?php esc_html_e( 'Arrivals Calendar', 'sovereign-parking' ); ?></h1>
    <form method="get" class="sp-calendar-filter">
        <input type="hidden" name="page" value="sp-calendar" />
        <label>
            <?php esc_html_e( 'Month', 'sovereign-parking' ); ?>
            <input type="number" name="month" min="1" max="12" value="<?php echo esc_attr( $month ); ?>" />
        </label>
        <label>
            <?php esc_html_e( 'Year', 'sovereign-parking' ); ?>
            <input type="number" name="year" min="2024" max="2100" value="<?php echo esc_attr( $year ); ?>" />
        </label>
        <?php submit_button( __( 'Update', 'sovereign-parking' ), 'secondary', '', false ); ?>
    </form>

    <table class="widefat fixed">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Date', 'sovereign-parking' ); ?></th>
                <th><?php esc_html_e( 'Bookings', 'sovereign-parking' ); ?></th>
                <th><?php esc_html_e( 'POA', 'sovereign-parking' ); ?></th>
                <th><?php esc_html_e( 'Confirmed', 'sovereign-parking' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php for ( $day = 1; $day <= gmdate( 't', $start ); $day++ ) :
                $date_key = $day;
                $data     = isset( $calendar[ $date_key ] ) ? $calendar[ $date_key ] : [ 'count' => 0, 'poa' => 0, 'confirmed' => 0 ];
                ?>
                <tr>
                    <td><?php echo esc_html( gmdate( 'j M Y', strtotime( sprintf( '%d-%02d-%02d', $year, $month, $day ) ) ) ); ?></td>
                    <td><?php echo esc_html( $data['count'] ); ?></td>
                    <td><?php echo esc_html( $data['poa'] ); ?></td>
                    <td><?php echo esc_html( $data['confirmed'] ); ?></td>
                </tr>
            <?php endfor; ?>
        </tbody>
    </table>
</div>
