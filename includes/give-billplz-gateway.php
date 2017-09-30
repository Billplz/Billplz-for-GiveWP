<?php
/**
 * Give Billplz Gateway
 *
 * @package     Give
 * @copyright   Copyright (c) 2017, Billplz
 */
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Give_Billplz_Gateway.
 */
class Give_Billplz_Gateway
{

    /**
     * API Secret Key.
     *
     * @var string
     */
    private static $api_key = '';
    private static $x_signature = '';
    private static $collection_id = '';
    private static $bills_description = '';
    private static $delivery_notification = '';

    const QUERY_VAR = 'billplz_givewp_call';
    const LISTENER_PASSPHRASE = 'billplz_listener_passphrase';

    /**
     * Give_Billplz_Gateway constructor.
     */
    public function __construct()
    {

        add_action('give_gateway_billplz', array($this, 'process_payment'));
        add_action('init', array($this, 'billplz_ipn_listener'));
    }

    /**
     * Get the Billplz API Key.
     *
     * @return string
     */
    public static function get_api_key()
    {

        self::$api_key = trim(give_get_option('billplz_api_key'));

        return self::$api_key;
    }

    /**
     * Get the Billplz X Signature key.
     *
     * @return string
     */
    public static function get_x_signature_key()
    {

        self::$x_signature = trim(give_get_option('billplz_x_signature_key'));

        return self::$x_signature;
    }

    /**
     * Get the Billplz X Signature key.
     *
     * @return string
     */
    public static function get_collection_id()
    {

        self::$collection_id = trim(give_get_option('billplz_collection_id'));

        return self::$collection_id;
    }

    /**
     * Get the Billplz Bills Description.
     *
     * @return string
     */
    public static function get_bills_description()
    {

        self::$bills_description = trim(give_get_option('billplz_bills_description'));

        return self::$bills_description;
    }

    /**
     * Get the Billplz Delivery Notification.
     *
     * @return string
     */
    public static function get_delivery_notification()
    {

        self::$delivery_notification = trim(give_get_option('billplz_delivery_notifcation'));

        return self::$delivery_notification;
    }

    public static function get_listener_url()
    {
        $passphrase = get_option(self::LISTENER_PASSPHRASE, false);
        if (!$passphrase) {
            $passphrase = md5(site_url() . time());
            update_option(self::LISTENER_PASSPHRASE, $passphrase);
        }
        return add_query_arg(self::QUERY_VAR, $passphrase, site_url('/'));
    }

    /**
     *
     * Process Billplz Checkout Submission.
     *
     * @access      public
     * @param $donation_data
     * @return      void
     */
    function process_payment($donation_data)
    {

        // Validate nonce.
        give_validate_nonce($donation_data['gateway_nonce'], 'give-gateway');
       
        // Make sure we don't have any left over errors present.
        give_clear_errors();

        $billplz = new Billplz(self::get_api_key());

        // Any errors?
        $errors = give_get_errors();

        $name = $donation_data['user_info']['first_name'] . ' ' . $donation_data['user_info']['last_name'];
        $description = empty(self::get_bills_description()) ? $donation_data['post_data']['give-form-title'] : self::get_bills_description();
        $deliver = empty(self::get_delivery_notification()) ? '0' : '1';
        $return_url = self::get_listener_url();

        // No errors, proceed.
        if (!$errors) {
            $form_id = intval($donation_data['post_data']['give-form-id']);
            $price_id = isset($donation_data['post_data']['give-price-id']) ? $donation_data['post_data']['give-price-id'] : 0;

            // Setup the payment details
            $payment_data = array(
                'price' => $donation_data['price'],
                'give_form_title' => $donation_data['post_data']['give-form-title'],
                'give_form_id' => $form_id,
                'give_price_id' => $price_id,
                'date' => $donation_data['date'],
                'user_email' => $donation_data['user_email'],
                'purchase_key' => $donation_data['purchase_key'],
                'currency' => give_get_currency(),
                'user_info' => $donation_data['user_info'],
                'status' => 'pending',
                'gateway' => 'billplz',
            );

            // Record the pending payment in Give.
            $payment_id = give_insert_payment($payment_data);

            $billplz
                ->setAmount($donation_data['price'])
                ->setCollection(self::get_collection_id())
                ->setDeliver($deliver)
                ->setDescription($description)
                ->setEmail($donation_data['user_email'])
                ->setName($name)
                ->setPassbackURL($return_url, $return_url)
                ->setReference_1($payment_id)
                ->setReference_1_Label('ID')
                ->create_bill(true);
            
            give_insert_payment_note($payment_id, sprintf(
                    __('Bill URL: %s', 'give'), $billplz->getURL()));
            give_insert_payment_note($payment_id, sprintf(
                    __('Bill Collection: %s', 'give'), self::get_collection_id()));
            give_set_payment_transaction_id($payment_id, $billplz->getID());

            wp_redirect($billplz->getURL());
            exit;
        } else {
            give_send_back_to_checkout('?payment-mode=billplz');
        }
    }

    /**
     * Listen for Billplz Callback and Redirect.
     *
     * @access      public
     * @since       3.0
     * @return      void
     */
    public function billplz_ipn_listener()
    {
        if (!isset($_GET[self::QUERY_VAR]))
            return;
        $passphrase = get_option(self::LISTENER_PASSPHRASE, false);
        if (!$passphrase) {
            return;
        }
        if ($_GET[self::QUERY_VAR] != $passphrase) {
            return;
        }

        if (isset($_GET['billplz']['id'])) {
            $this->handle_redirect();
        } else {
            sleep(10);
            $this->handle_callback();
        }
    }

    /**
     * Handle Billplz Redirect.
     *
     * @since 3.0
     *
     */
    public function handle_redirect()
    {
        try {
            $data = Billplz::getRedirectData(self::get_x_signature_key());
        } catch (\Exception $e) {
            exit($e->getMessage());
        }

        $billplz = new Billplz(self::get_api_key());
        $moreData = $billplz->check_bill($data['id']);

        $this->update_to_db($moreData, $data['paid']);
        if ($data['paid']) {
            $return = add_query_arg(array(
                'payment-confirmation' => 'billplz',
                'payment-id' => $moreData['reference_1'],
                ), get_permalink(give_get_option('success_page')));
        } else {
            $return = give_get_failed_transaction_uri('?payment-id=' . $moreData['reference_1']);
        }
        wp_redirect($return);
        exit;
    }

    /**
     * Handle Billplz Callback.
     *
     * @since 3.0
     *
     */
    public function handle_callback()
    {
        try {
            $data = Billplz::getCallbackData(self::get_x_signature_key());
        } catch (\Exception $e) {
            exit($e->getMessage());
        }

        $billplz = new Billplz(self::get_api_key());
        $moreData = $billplz->check_bill($data['id']);

        $this->update_to_db($moreData, $data['paid']);
    }

    public function update_to_db($moreData, $payment_status = false)
    {

        // Only complete payments once.
        if ('publish' === get_post_status($moreData['reference_1'])) {
            return;
        }

        // Process completed donations.
        if ($payment_status) {
            give_update_payment_status($moreData['reference_1'], 'publish');
        } elseif (!$payment_status) {
            if (!empty($note)) {
                give_insert_payment_note($moreData['reference_1'], $moreData['status']);
            }
        }
    }

    /**
     * Build item title for Billplz.
     *
     * @since 3.0
     *
     * @param $payment_data
     *
     * @return string
     */
    public function give_build_billplz_item_title($payment_data)
    {
        $form_id = intval($payment_data['post_data']['give-form-id']);
        $item_name = $payment_data['post_data']['give-form-title'];

        // Verify has variable prices.
        if (give_has_variable_prices($form_id) && isset($payment_data['post_data']['give-price-id'])) {

            $item_price_level_text = give_get_price_option_name($form_id, $payment_data['post_data']['give-price-id']);
            $price_level_amount = give_get_price_option_amount($form_id, $payment_data['post_data']['give-price-id']);

            // Donation given doesn't match selected level (must be a custom amount).
            if ($price_level_amount != give_maybe_sanitize_amount($payment_data['price'])) {
                $custom_amount_text = give_get_meta($form_id, '_give_custom_amount_text', true);
                // user custom amount text if any, fallback to default if not.
                $item_name .= ' - ' . give_check_variable($custom_amount_text, 'empty', __('Custom Amount', 'give'));
            } elseif (!empty($item_price_level_text)) {
                $item_name .= ' - ' . $item_price_level_text;
            }
        } // End if().
        elseif (give_get_form_price($form_id) !== give_maybe_sanitize_amount($payment_data['price'])) {
            $custom_amount_text = give_get_meta($form_id, '_give_custom_amount_text', true);
            // user custom amount text if any, fallback to default if not.
            $item_name .= ' - ' . give_check_variable($custom_amount_text, 'empty', __('Custom Amount', 'give'));
        }

        return $item_name;
    }
}

return new Give_Billplz_Gateway();
