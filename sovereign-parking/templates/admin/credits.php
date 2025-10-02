<?php
/**
 * Customer credits management template.
 */
$customers = get_users(
    [
        'role__in' => [ SP_Roles::ROLE_CUSTOMER ],
        'orderby'  => 'display_name',
        'order'    => 'ASC',
    ]
);
$ledger = SP_Customer_Credits::instance()->get_recent_ledger();
?>
<div class="wrap sp-credits">
    <h1><?php esc_html_e( 'Customer Credits', 'sovereign-parking' ); ?></h1>
    <div class="sp-credit-actions">
        <form method="post">
            <?php wp_nonce_field( 'sp_add_credit', 'sp_add_credit_nonce' ); ?>
            <select name="sp_credit_customer">
                <option value=""><?php esc_html_e( 'Select Customer', 'sovereign-parking' ); ?></option>
                <?php foreach ( $customers as $customer ) : ?>
                    <option value="<?php echo esc_attr( $customer->ID ); ?>"><?php echo esc_html( $customer->display_name . ' (' . $customer->user_email . ')' ); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" step="0.01" name="sp_credit_amount" placeholder="<?php esc_attr_e( 'Amount', 'sovereign-parking' ); ?>" />
            <input type="text" name="sp_credit_description" placeholder="<?php esc_attr_e( 'Description', 'sovereign-parking' ); ?>" />
            <button class="button button-primary" name="sp_credit_action" value="add"><?php esc_html_e( 'Apply Credit', 'sovereign-parking' ); ?></button>
        </form>
    </div>
    <h2><?php esc_html_e( 'Recent Ledger Entries', 'sovereign-parking' ); ?></h2>
    <table class="widefat fixed">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Date', 'sovereign-parking' ); ?></th>
                <th><?php esc_html_e( 'Customer', 'sovereign-parking' ); ?></th>
                <th><?php esc_html_e( 'Amount', 'sovereign-parking' ); ?></th>
                <th><?php esc_html_e( 'Description', 'sovereign-parking' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $ledger as $entry ) :
                $user = get_user_by( 'id', $entry->user_id );
                ?>
                <tr>
                    <td><?php echo esc_html( gmdate( 'd M Y H:i', strtotime( $entry->created_at ) ) ); ?></td>
                    <td><?php echo esc_html( $user ? $user->display_name : '#' . $entry->user_id ); ?></td>
                    <td><?php echo esc_html( SP_Helpers::format_currency( $entry->amount ) ); ?></td>
                    <td><?php echo esc_html( $entry->description ); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
