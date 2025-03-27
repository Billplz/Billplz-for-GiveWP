<?php
/**
 * Plugin Name: Billplz for GiveWP
 * Plugin URI: https://github.com/Billplz/billplz-for-givewp
 * Description: Billplz payment integration for GiveWP.
 * Version: 4.0.0
 * Requires at least: 4.6
 * Requires PHP: 7.0
 * Author: Billplz Sdn Bhd
 * Author URI: https://www.billplz.com/
 * Text Domain: billplz-for-givewp
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Requires Plugins: give
 */

if ( !defined( 'ABSPATH' ) ) exit;

if ( !defined( 'BILLPLZ_GIVEWP_FILE' ) ) {
    define( 'BILLPLZ_GIVEWP_FILE', __FILE__ );
}

if ( !defined( 'BILLPLZ_GIVEWP_VERSION' ) ) {
    define( 'BILLPLZ_GIVEWP_VERSION', '4.0.0' );
}

// Plugin core class
if ( !class_exists( 'Billplz_GiveWP' ) ) {
    require_once plugin_dir_path( BILLPLZ_GIVEWP_FILE ) . 'includes/class-billplz-givewp.php';
}
