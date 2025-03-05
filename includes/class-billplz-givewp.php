<?php

use Give\Donations\Models\Donation;
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
        add_action( 'give_billplz_cc_form', array( $this, 'add_billing_form' ) );
        add_filter( 'give_payment_confirm_billplz', array( $this, 'success_page_content' ) );
        add_filter( 'give_payment_details_transaction_id-billplz', array( $this, 'link_transaction_id' ), 10, 2 );
    }

    /**
     * Register Billplz as a payment method in GiveWP.
     * 
     * @since 4.0.0
     */
    public function register_gateway( PaymentGatewayRegister $registrar ) {
        include_once BILLPLZ_GIVEWP_PATH . 'includes/class-billplz-givewp-gateway.php';

        $registrar->registerGateway( Billplz_GiveWP_Gateway::class );
    }

    /**
     * Remove Billplz from the gateway list if it is disabled or currency is not supported.
     * 
     * @since 3.0.2
     */
    public function filter_gateway( $gateways, $form_id ) {
        // Skip gateway filtering on create Give form donation page
        if ( false !== strpos( $_SERVER['REQUEST_URI'], '/wp-admin/post-new.php?post_type=give_forms' ) ) {
            return $gateways;
        }

        $is_supported_currency = give_get_currency( $form_id ) === 'MYR'; // returns global or specific form currency
        $is_enabled = $form_id ? give_is_setting_enabled( give_get_meta( $form_id, 'billplz_customize_billplz_donations', true, 'global' ), [ 'enabled', 'global' ] ) : false;

        if ( !$is_supported_currency || !$is_enabled ) {
            unset( $gateways['billplz'] );
        }

        return $gateways;
    }

    /**
     * Display billing form if it is enabled.
     * 
     * @since 3.0.2
     */
    public function add_billing_form( $form_id ) {
        $custom_donation_settings = give_get_meta( $form_id, 'billplz_customize_billplz_donations', true, 'global' );

        if ( give_is_setting_enabled( $custom_donation_settings, 'enabled' ) ) {
            $is_collect_billing = give_get_meta( $form_id, 'billplz_collect_billing', true );
        } else {
            $is_collect_billing = give_get_option( 'billplz_collect_billing' );
        }

        if ( $is_collect_billing === 'enabled' ) {
            give_default_cc_address_fields( $form_id );
        }
    }

    /**
     * Payment success page.
     * 
     * @since 3.2.0
     */
    public function success_page_content() {
        $session = give_get_purchase_session();
        $payment_id = give_get_donation_id_by_key( $session['purchase_key'] );
    
        $payment = get_post( $payment_id );

        // Payment is still pending, so show processing indicator to fix the race condition.
        if ( $payment && $payment->post_status === 'pending' ) {
            ob_start();
    
            give_get_template_part( 'payment', 'processing' );
    
            $content = ob_get_clean();
        }

        return $content;
    }

    /**
     * Link the bill ID in the transaction details section.
     * 
     * @since 4.0.0
     * 
     * @param string $transaction_id
     * @param string $payment_id
     */
    public function link_transaction_id( $transaction_id, $payment_id ) {
        $donation = Donation::find( $payment_id );

        if ( $donation->mode->isLive() ) {
            $bill_url = 'https://www.billplz.com/bills/' . $transaction_id;
        } else {
            $bill_url = 'https://www.billplz-sandbox.com/bills/' . $transaction_id;
        }

        return '<a href="' . esc_url( $bill_url ) . '" target="_blank">' . $transaction_id . '</a>';
    }
}

Billplz_GiveWP::get_instance();
