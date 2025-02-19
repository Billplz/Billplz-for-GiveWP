<?php

use Give\Framework\PaymentGateways\PaymentGatewayRegister;

if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Main class.
 * 
 * @since 4.0.0 Split from main plugin file.
 */
class Billplz_GiveWP {
    /**
     * Class instance.
     * 
     * @var Billplz_GiveWP
     */
    private static $_instance;

    /**
     * Get an instance of the plugin.
     */
    public static function get_instance() {
        if ( self::$_instance === null ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /* 
     * Constructor.
     */
    public function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Define plugin constants.
     * 
     * @since 4.0.0
     */
    private function define_constants() {
        define( 'BILLPLZ_GIVEWP_URL', plugin_dir_url( BILLPLZ_GIVEWP_FILE ) );
        define( 'BILLPLZ_GIVEWP_PATH', plugin_dir_path( BILLPLZ_GIVEWP_FILE ) );
        define( 'BILLPLZ_GIVEWP_BASENAME', plugin_basename( BILLPLZ_GIVEWP_FILE ) );
    }

    /**
     * Include required core files.
     * 
     * @since 4.0.0
     */
    private function includes() {
        // Functions
        require_once BILLPLZ_GIVEWP_PATH . 'includes/functions.php';

        // API
        require_once BILLPLZ_GIVEWP_PATH . 'includes/abstracts/abstract-billplz-givewp-client.php';
        require_once BILLPLZ_GIVEWP_PATH . 'includes/class-billplz-givewp-api.php';

        // Admin
        require_once BILLPLZ_GIVEWP_PATH . 'includes/admin/class-billplz-givewp-admin.php';

        // Plugin settings
        require_once BILLPLZ_GIVEWP_PATH . 'includes/admin/class-billplz-givewp-settings-metabox.php';
        require_once BILLPLZ_GIVEWP_PATH . 'includes/admin/class-billplz-givewp-settings.php';
    }

    /**
     * Hook into actions and filters.
     * 
     * @since 4.0.0
     */
    private function init_hooks() {
        add_action( 'givewp_register_payment_gateway', array( $this, 'register_gateway' ) );
        add_action( 'give_enabled_payment_gateways', array( $this, 'filter_gateway' ), 10, 2 );
    }

    /**
     * Register Billplz as a payment method in GiveWP.
     * 
     * @since 4.0.0
     */
    public function register_gateway( PaymentGatewayRegister $registrar ) {
        include_once BILLPLZ_GIVEWP_PATH . 'includes/class-billplz-givewp-gateway.php';

        $registrar->registerGateway(Billplz_GiveWP_Gateway::class);
    }

    /**
     * Remove Billplz from the gateway list if it is disabled.
     * 
     * @since 3.0.2
     * @since 4.0.0 Renamed from `give_filter_billplz_gateway` and moved from gateway class
     */
    public function filter_gateway( $gateways, $form_id ) {
        // Skip gateway filtering on create Give form donation page
        if ( false === strpos( $_SERVER['REQUEST_URI'], '/wp-admin/post-new.php?post_type=give_forms' ) ) {
            return $gateways;
        }

        if ( $form_id && !give_is_setting_enabled( give_get_meta( $form_id, 'billplz_customize_billplz_donations', true, 'global' ), [ 'enabled', 'global' ] ) ) {
            unset( $gateways['billplz'] );
        }

        return $gateways;
    }
}

Billplz_GiveWP::get_instance();
