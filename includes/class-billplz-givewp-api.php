<?php
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * API class.
 * 
 * @since 3.0.0
 * @since 4.0.0 Renamed from `BillplzGiveAPI`.
 */
class Billplz_GiveWP_API extends Billplz_GiveWP_Client {
    /**
     * Constructor.
     */
    public function __construct( $api_key, $sandbox = false ) {
        $this->api_key = $api_key;
        $this->sandbox = $sandbox;
    }

    /**
     * Create a bill.
     */
    public function create_bill( array $params ) {
        return $this->post( 'v3/bills', $params );
    }

}
