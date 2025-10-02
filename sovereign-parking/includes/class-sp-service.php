<?php
/**
 * Base service implementation.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class SP_Service implements SP_Service_Interface {

    /**
     * Default empty hook registrar to allow overriding as needed.
     */
    public function register_hooks() {}
}
