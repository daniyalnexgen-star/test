<?php
/**
 * Settings page template.
 */
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Sovereign Parking Settings', 'sovereign-parking' ); ?></h1>
    <form method="post" action="options.php">
        <?php
        settings_fields( 'sp_settings_group' );
        do_settings_sections( 'sp_settings' );
        submit_button();
        ?>
    </form>
</div>
