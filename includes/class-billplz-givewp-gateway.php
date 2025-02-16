<?php

use Give\Donations\Models\Donation;
use Give\Framework\PaymentGateways\Commands\GatewayCommand;
use Give\Framework\PaymentGateways\Commands\PaymentRefunded;
use Give\Framework\PaymentGateways\Commands\RedirectOffsite;
use Give\Framework\PaymentGateways\PaymentGateway;

if ( !defined( 'ABSPATH' ) ) exit;

/**
 * @inheritDoc
 */
class Billplz_GiveWP_Gateway extends PaymentGateway {
    /**
     * @inheritDoc
     */
    public static function id(): string
    {
        return 'billplz';
    }

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return self::id();
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return __( 'Billplz', 'billplz-givewp' );
    }

    /**
     * @inheritDoc
     */
    public function getPaymentMethodLabel(): string
    {
        return __( 'Billplz', 'billplz-givewp' );
    }

    // Enqueue scripts for V3 donation forms
    public function enqueueScript( int $formId )
    {
        wp_enqueue_script( 'billplz-givewp', BILLPLZ_GIVEWP_URL . 'assets/js/gateway.js', array( 'react', 'wp-element' ), BILLPLZ_GIVEWP_VERSION, true );
    }

    // Send form settings to the JS gateway counterpart
    public function formSettings(int $formId): array
    {
        return [
            'message' => __( 'You will be redirected to Billplz.com to complete the donation!', 'billplz-givewp' ),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getLegacyFormFieldMarkup( int $formId, array $args ): string
    {
        return "<div class=\"billplz-givewp-help-text\">
                    <p>" . esc_html__( 'You will be redirected to Billplz.com to complete the donation!', 'billplz-givewp' ) . "</p>
                </div>";
    }

    /**
     * @inheritDoc
     */
    public function createPayment( Donation $donation, $gatewayData ): RedirectOffsite
    {
    }

    /**
     * @inerhitDoc
     */
    public function refundDonation( Donation $donation ): PaymentRefunded
    {
        return new PaymentRefunded();
    }
}
