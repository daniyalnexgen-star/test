<?php
/**
 * Custom roles and capabilities.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SP_Roles extends SP_Service {

    const ROLE_MANAGER  = 'sp_manager';
    const ROLE_CUSTOMER = 'sp_customer';

    /**
     * Capabilities for manager role.
     *
     * @return array
     */
    protected function manager_caps() {
        return [
            'read'                   => true,
            'read_private_sp_booking' => true,
            'edit_sp_booking'        => true,
            'edit_sp_bookings'       => true,
            'edit_others_sp_bookings'=> true,
            'publish_sp_bookings'    => true,
            'delete_sp_booking'      => true,
            'delete_sp_bookings'     => true,
            'manage_sp_settings'     => true,
        ];
    }

    /**
     * Capabilities for customer role.
     *
     * @return array
     */
    protected function customer_caps() {
        return [
            'read' => true,
        ];
    }

    /**
     * Setup hooks.
     */
    public function register_hooks() {
        add_action( 'init', [ $this, 'add_roles' ] );
    }

    /**
     * Ensure roles exist.
     */
    public function add_roles() {
        add_role( self::ROLE_MANAGER, __( 'Sovereign Parking Manager', 'sovereign-parking' ), $this->manager_caps() );
        add_role( self::ROLE_CUSTOMER, __( 'Sovereign Parking Customer', 'sovereign-parking' ), $this->customer_caps() );
    }

    /**
     * Activation hook.
     */
    public function activate() {
        $this->add_roles();
    }
}
