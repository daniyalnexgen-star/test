<?php
/**
 * Customer portal template.
 */
$user = wp_get_current_user();
?>
<div class="sp-portal">
    <h2><?php esc_html_e( 'Welcome back to Sovereign Parking', 'sovereign-parking' ); ?>, <?php echo esc_html( $user->display_name ); ?></h2>
    <p><?php esc_html_e( 'Manage your upcoming bookings, update vehicle details, or change your shuttle time below.', 'sovereign-parking' ); ?></p>

    <div id="sp-portal-bookings"></div>
</div>
