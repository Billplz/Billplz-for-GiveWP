<?php

use Give\Donations\Models\Donation;

if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Returns donor full name.
 * 
 * @since 4.0.0
 */
function billplz_givewp_get_donor_fullname( Donation $donation ): string
{
    return implode( ' ', array( $donation->firstName, $donation->lastName ) );
}
