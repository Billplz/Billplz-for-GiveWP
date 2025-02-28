<?php

use Give\Donations\Models\Donation;
use Give\Donations\Models\DonationNote;
use Give\Donations\ValueObjects\DonationStatus;
use Give\Framework\Http\Response\Types\RedirectResponse;
use Give\Framework\PaymentGateways\Commands\GatewayCommand;
use Give\Framework\PaymentGateways\Commands\PaymentComplete;
use Give\Framework\PaymentGateways\Commands\PaymentRefunded;
use Give\Framework\PaymentGateways\Commands\RedirectOffsite;
use Give\Framework\PaymentGateways\DonationSummary;
use Give\Framework\PaymentGateways\Exceptions\PaymentGatewayException;
use Give\Framework\PaymentGateways\Log\PaymentGatewayLog;
use Give\Framework\PaymentGateways\PaymentGateway;

if ( !defined( 'ABSPATH' ) ) exit;

/**
 * @inheritDoc
 */
class Billplz_GiveWP_Gateway extends PaymentGateway {
    public $routeMethods = [
        'handleIpnNotification',
    ];

    public $secureRouteMethods = [
        'handleSuccessPaymentReturn',
        'handleCancelledPaymentReturn',
    ];

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

    /**
     * Enqueue scripts for V3 donation forms.
     * 
     * @since 4.0.0
     */
    public function enqueueScript( int $formId )
    {
        wp_enqueue_script( 'billplz-givewp', BILLPLZ_GIVEWP_URL . 'assets/js/gateway.js', array( 'react', 'wp-element' ), BILLPLZ_GIVEWP_VERSION, true );
    }

    /**
     * Send form settings to the JS gateway counterpart.
     * 
     * @since 4.0.0
     */
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
        try {
            $sandbox = give_is_test_mode();
            $donorName = billplz_givewp_get_donor_fullname( $donation );

            $is_custom_donation_enabled = $this->isCustomDonationEnabled( $donation );

            if ( $is_custom_donation_enabled ) {
                $api_key = give_get_meta( $donation->formId, 'billplz_api_key', true );
                $collection_id = give_get_meta( $donation->formId, 'billplz_collection_id', true );
                $description = give_get_meta( $donation->formId, 'billplz_description', true );
                $reference_1_label = give_get_meta( $donation->formId, 'billplz_reference_1_label', true );
                $reference_2_label = give_get_meta( $donation->formId, 'billplz_reference_2_label', true );
                $reference_1_value = give_get_meta( $donation->formId, 'billplz_reference_1', true );
                $reference_2_value = give_get_meta( $donation->formId, 'billplz_reference_2', true );
            } else {
                $api_key = give_get_option( 'billplz_api_key' );
                $collection_id = give_get_option( 'billplz_collection_id' );
                $description = give_get_option( 'billplz_description' );
                $reference_1_label = give_get_option( 'billplz_reference_1_label' );
                $reference_2_label = give_get_option( 'billplz_reference_2_label' );
                $reference_1_value = give_get_option( 'billplz_reference_1' );
                $reference_2_value = give_get_option( 'billplz_reference_2' );
            }

            if ( !$description ) {
                $description = (new DonationSummary($donation))->getSummary();
            }

            // Limit string length
            $description = mb_substr( $description, 0, 200 );
            $reference_1_label = mb_substr( $reference_1_label, 0, 20 );
            $reference_2_label = mb_substr( $reference_2_label, 0, 20 );
            $reference_1_value = mb_substr( $reference_1_value, 0, 120 );
            $reference_2_value = mb_substr( $reference_2_value, 0, 120 );

            $redirect_url = $this->generateSecureGatewayRouteUrl( 'handleSuccessPaymentReturn', $donation->id, array(
                'donation-id' => $donation->id,
                'give-return-url' => $gatewayData['successUrl'],
            ) );

            $callback_url = $this->generateGatewayRouteUrl( 'handleIpnNotification', array(
                'donation-id' => $donation->id,
            ) );

            if ( !$collection_id ) {
                throw new Exception( __( 'Missing collection ID.', 'billplz-givewp' ) );
            }

            if ( !$donorName ) {
                throw new PaymentGatewayException( __( 'Name is required.', 'billplz-givewp' ) );
            }

            if ( !$donation->email && !$donation->phone ) {
                throw new PaymentGatewayException( __( 'Email or phone is required.', 'billplz-givewp' ) );
            }

            if ( !$description ) {
                throw new Exception( __( 'Bill description is required.', 'billplz-givewp' ) );
            }

            $params = array(
                'collection_id' => $collection_id,
                'email' => $donation->email,
                'mobile' => $donation->phone,
                'name' => $donorName,
                'amount' => $donation->amount->formatToMinorAmount(),
                'redirect_url' => $redirect_url,
                'callback_url' => $callback_url,
                'description' => $description,
                'reference_1_label' => $reference_1_label,
                'reference_1' => $reference_1_value,
                'reference_2_label' => $reference_2_label,
                'reference_2' => $reference_2_value,
            );
            
            $billplz = new Billplz_GiveWP_API( $api_key, $sandbox );
            list( $code, $response ) = $billplz->create_bill( $params );

            if ( $code === 200 ) {
                $bill_url = isset( $response['url'] ) ? $response['url'] : null;

                if ( !$bill_url ) {
                    throw new Exception( __( 'Unable to redirect to the bill payment page.', 'billplz-givewp' ) );
                }

                return new RedirectOffsite( $bill_url );
            }

            $error_message = isset( $response['error']['message'] ) ? $response['error']['message'] : null;

            if ( $error_message ) {
                if ( is_array( $error_message ) ) {
                    throw new Exception( implode( ', ', $error_message ), $code );
                } else {
                    throw new Exception( $error_message, $code );
                }
            }
        } catch ( Exception $e ) {
            $errorMessage = $e->getMessage();

            $donation->status = DonationStatus::FAILED();
            $donation->save();

            DonationNote::create([
                'donationId' => $donation->id,
                'content' => sprintf( esc_html__('Donation failed. Reason: %s', 'billplz-givewp'), $errorMessage ),
            ]);

            throw new PaymentGatewayException( $errorMessage );
        }
    }

    /**
     * Check if custom donation is enabled.
     * 
     * @since 4.0.0
     * 
     * @return true
     */
    private function isCustomDonationEnabled( Donation $donation ) {
        $custom_donation_settings = give_get_meta( $donation->formId, 'billplz_customize_billplz_donations', true, 'global' );

        return give_is_setting_enabled( $custom_donation_settings, 'enabled' );
    }

    /**
     * Update donation payment status.
     */
    private function updatePaymentStatus( array $ipnResponse ) {
    }

    /**
     * Handle payment redirect after successful payment.
     *
     * @since 4.0.0
     */
    protected function handleSuccessPaymentReturn( array $data ): RedirectResponse
    {
        try {
            $donation = Donation::find( $data['donation-id'] );

            if ( !$donation ) {
                throw new Exception( __( 'Donation not found.', 'billplz-givewp' ) );
            }

            if ( !$donation->status->isComplete() ) {
                $response = $this->getIpnResponse( $donation );
    
                if ( $response ) {
                    $formattedResponse = array();

                    // Format API response
                    // ie: `billplzpaid_at` to `paid_at`
                    foreach ( $response as $key => $value) {
                        $newKey = str_replace( 'billplz', '', $key );
                        $formattedResponse[ $newKey ] = $value;
                    }

                    $this->handlePaymentCompletion( $donation, $formattedResponse );
                }
            }
        } catch ( Exception $e ) {
            PaymentGatewayLog::error( 'Billplz for GiveWP - IPN (redirect) failed.', [
                'error' => $e->getMessage(),
            ] );
        }

        return new RedirectResponse( esc_url_raw( $data['give-return-url'] ) );
    }

    /**
     * Handle payment callback after successful payment.
     *
     * @since 4.0.0
     */
    public function handleIpnNotification( array $data ) {
        try {
            $donation = Donation::find( $data['donation-id'] );

            if ( !$donation ) {
                throw new Exception( __( 'Donation not found.', 'billplz-givewp' ) );
            }

            if ( !$donation->status->isComplete() ) {
                $response = $this->getIpnResponse( $donation );
    
                if ( $response ) {
                    $this->handlePaymentCompletion( $donation, $response );
                }
            }

            wp_send_json_success( null, 200 );
        } catch ( Exception $e ) {
            PaymentGatewayLog::error( 'Billplz for GiveWP - IPN (callback) failed.', [
                'error' => $e->getMessage(),
            ] );

            wp_send_json_error( array(
                'message' => $e->getMessage(),
            ), 400 );
        }

        wp_send_json_error( null, 400 );
    }

    /**
     * Get IPN response (callback or redirect).
     * 
     * @since 4.0.0
     * 
     * @return array|bool
     * @throws Exception
     */
    private function getIpnResponse( Donation $donation ) {
        $sandbox = give_is_test_mode();
        $is_custom_donation_enabled = $this->isCustomDonationEnabled( $donation );

        if ( $is_custom_donation_enabled ) {
            $api_key = give_get_meta( $donation->formId, 'billplz_api_key', true );
            $xsignature_key = give_get_meta( $donation->formId, 'billplz_x_signature_key', true );
        } else {
            $api_key = give_get_option( 'billplz_api_key' );
            $xsignature_key = give_get_option( 'billplz_x_signature_key' );
        }

        if ( !$api_key ) {
            throw new Exception( __( 'Missing API key.', 'billplz-givewp' ) );
        }

        if ( !$xsignature_key ) {
            throw new Exception( __( 'Missing X-Signature key.', 'billplz-givewp' ) );
        }

        $billplz = new Billplz_GiveWP_API( $api_key, $sandbox );
        $response = $billplz->get_ipn_response();

        if ( $billplz->validate_ipn_response( $response, $xsignature_key ) ) {
            return $response;
        }

        throw new Exception( __( 'Unable to retrieve IPN response.', 'billplz-givewp' ) );
    }

    /**
     * Handle payment completion; uppdate payment status.
     * 
     * @since 4.0.0
     */
    private function handlePaymentCompletion( Donation $donation, array $response ) {
        $donation_status = DonationStatus::PENDING();
        $payment_status = __( 'Pending', 'billplz-givewp' ); // Billplz payment status

        // If Extra Payment Completion Information option is enabled in Billplz, get the transaction status
        if ( isset( $response['transaction_status'] ) ) {
            switch ( $response['transaction_status'] ) {
                case 'completed':
                    $donation_status = DonationStatus::COMPLETE();
                    $payment_status = __( 'Paid', 'billplz-givewp' );
                    break;

                case 'failed':
                    $donation_status = DonationStatus::FAILED();
                    $payment_status = __( 'Failed', 'billplz-givewp' );
                    break;
            }
        } elseif ( (bool) $response['paid'] ) {
            $donation_status = DonationStatus::COMPLETE();
            $payment_status = __( 'Paid', 'billplz-givewp' );
        }

        $donation->status = $donation_status;
        $donation->gatewayTransactionId = $response['id'];
        $donation->save();

        $sandbox = give_is_test_mode();

        $sandbox_label = $sandbox
            ? __( 'Yes', 'billplz-givewp' )
            : __( 'No', 'billplz-givewp' );

        // Create a donation note to display transaction details
        DonationNote::create( [
            'donationId' => $donation->id,
            'content' => sprintf(
                esc_html__(
                    "Billplz Payment Details\n\nBill ID: %1\$s\nPayment Status: %2\$s\nSandbox: %3\$s",
                    'billplz-givewp'
                ),
                $response['id'],
                $payment_status,
                $sandbox_label
            )
        ] );
    }

    /**
     * @inerhitDoc
     */
    public function refundDonation( Donation $donation ): PaymentRefunded
    {
        return new PaymentRefunded();
    }
}
