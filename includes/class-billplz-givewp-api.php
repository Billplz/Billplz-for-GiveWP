<?php
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * API class.
 * 
 * @since 4.0.0
 */
class Billplz_GiveWP_API extends Billplz_GiveWP_Client {
    /**
     * Create a bill.
     * 
     * @since 4.0.0
     */
    public function create_bill( array $params ) {
        return $this->post( 'v3/bills', $params );
    }
}
