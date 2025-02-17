<?php
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Admin class.
 * 
 * @since 4.0.0
 */
class Billplz_GiveWP_Admin {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'plugin_action_links_' . BILLPLZ_GIVEWP_BASENAME, array( $this, 'register_settings_link' ) );
    }

    /**
     * Register plugin settings link.
     * 
     * @since 3.0.2
     * @since 4.0.0 Moved from function.
     * 
     * @param array $links
     * @return array
     */
    public function register_settings_link( $links ) {
        $url = admin_url( 'edit.php?post_type=give_forms&page=give-settings&tab=gateways&section=billplz' );
        $label = esc_html__( 'Settings', 'billplz-givewp' );

        $settings_link = '<a href="' . esc_url( $url ) . '">' . $label . '</a>';
        array_unshift( $links, $settings_link );

        return $links;
    }
}

new Billplz_GiveWP_Admin();
