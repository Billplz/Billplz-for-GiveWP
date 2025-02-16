<?php

use Give\Donations\Models\Donation;
use Give\Framework\PaymentGateways\Commands\GatewayCommand;
use Give\Framework\PaymentGateways\Commands\PaymentRefunded;
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
        return __('Billplz', 'billplz-givewp');
    }

    /**
     * @inheritDoc
     */
    public function getPaymentMethodLabel(): string
    {
        return __('Billplz', 'billplz-givewp');
    }

    /**
     * @inheritDoc
     */
    public function createPayment(Donation $donation, $gatewayData): GatewayCommand
    {
    }

    /**
     * @inerhitDoc
     */
    public function refundDonation(Donation $donation): PaymentRefunded
    {
    }
}

new Billplz_GiveWP_Gateway();
