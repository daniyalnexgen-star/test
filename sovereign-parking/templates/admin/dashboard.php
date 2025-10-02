<?php
/**
 * Dashboard summary template.
 */
?>
<div class="wrap sp-dashboard">
    <h1><?php esc_html_e( 'Sovereign Parking Dashboard', 'sovereign-parking' ); ?></h1>
    <div class="sp-dashboard-grid">
        <div class="sp-card">
            <h2><?php esc_html_e( 'Total Bookings', 'sovereign-parking' ); ?></h2>
            <p class="sp-metric"><?php echo esc_html( $total ); ?></p>
        </div>
        <div class="sp-card">
            <h2><?php esc_html_e( 'Confirmed', 'sovereign-parking' ); ?></h2>
            <p class="sp-metric"><?php echo esc_html( $confirmed ); ?></p>
        </div>
        <div class="sp-card">
            <h2><?php esc_html_e( 'Pending Payment', 'sovereign-parking' ); ?></h2>
            <p class="sp-metric"><?php echo esc_html( $pending ); ?></p>
        </div>
        <div class="sp-card">
            <h2><?php esc_html_e( 'POA – Pending', 'sovereign-parking' ); ?></h2>
            <p class="sp-metric"><?php echo esc_html( $poa_pending ); ?></p>
        </div>
        <div class="sp-card">
            <h2><?php esc_html_e( 'POA – Paid', 'sovereign-parking' ); ?></h2>
            <p class="sp-metric"><?php echo esc_html( $poa_paid ); ?></p>
        </div>
    </div>
    <p>
        <?php
        printf(
            '<a class="button button-primary" href="%s">%s</a>',
            esc_url( admin_url( 'edit.php?post_type=' . SP_Booking_CPT::POST_TYPE ) ),
            esc_html__( 'Manage Bookings', 'sovereign-parking' )
        );
        ?>
    </p>
</div>
