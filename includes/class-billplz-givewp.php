<?php

use Give\Framework\PaymentGateways\PaymentGatewayRegister;

if ( !defined( 'ABSPATH' ) ) exit;

class Billplz_GiveWP {
    private static $_instance;

    // Get an instance of the plugin
    public static function get_instance() {
        if ( self::$_instance === null ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    // Constructor
    public function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }

    // Define plugin constants
    private function define_constants() {
        define( 'BILLPLZ_GIVEWP_URL', plugin_dir_url( BILLPLZ_GIVEWP_FILE ) );
        define( 'BILLPLZ_GIVEWP_PATH', plugin_dir_path( BILLPLZ_GIVEWP_FILE ) );
        define( 'BILLPLZ_GIVEWP_BASENAME', plugin_basename( BILLPLZ_GIVEWP_FILE ) );
    }

    // Include required core files
    private function includes() {
        // API
        require_once BILLPLZ_GIVEWP_PATH . 'includes/abstracts/abstract-billplz-givewp-client.php';
        require_once BILLPLZ_GIVEWP_PATH . 'includes/class-billplz-givewp-api.php';

        // Plugin activation
        require_once BILLPLZ_GIVEWP_PATH . 'includes/admin/class-billplz-givewp-activation.php';

        // Plugin settings
        require_once BILLPLZ_GIVEWP_PATH . 'includes/admin/class-billplz-givewp-settings-metabox.php';
        require_once BILLPLZ_GIVEWP_PATH . 'includes/admin/class-billplz-givewp-settings.php';
    }

    // Hook into actions and filters
    private function init_hooks() {
        add_action( 'givewp_register_payment_gateway', array( $this, 'register_gateway' ) );
    }

    // Register Billplz as a payment method in GiveWP
    public function register_gateway( PaymentGatewayRegister $registrar ) {
        include_once BILLPLZ_GIVEWP_PATH . 'includes/class-billplz-givewp-gateway.php';

        $registrar->registerGateway(Billplz_GiveWP_Gateway::class);
    }
}

Billplz_GiveWP::get_instance();
