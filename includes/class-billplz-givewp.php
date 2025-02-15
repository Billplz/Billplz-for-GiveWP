<?php

if (!defined('ABSPATH')) {
    exit;
}

class Give_Billplz_Gateway
{
    private static $instance;

    const QUERY_VAR = 'billplz_givewp_return';
    const LISTENER_PASSPHRASE = 'billplz_givewp_listener_passphrase';

    private function __construct()
    {
        add_action('init', array($this, 'return_listener'));
        add_action('give_gateway_billplz', array($this, 'process_payment'));
        add_action('give_billplz_cc_form', array($this, 'give_billplz_cc_form'));
        add_filter('give_enabled_payment_gateways', array($this, 'give_filter_billplz_gateway'), 10, 2);
        add_filter('give_payment_confirm_billplz', array($this, 'give_billplz_success_page_content'));
    }

    public static function get_instance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function give_filter_billplz_gateway($gateway_list, $form_id)
    {
        if ((false === strpos($_SERVER['REQUEST_URI'], '/wp-admin/post-new.php?post_type=give_forms'))
            && $form_id
            && !give_is_setting_enabled(give_get_meta($form_id, 'billplz_customize_billplz_donations', true, 'global'), array('enabled', 'global'))
        ) {
            unset($gateway_list['billplz']);
        }
        return $gateway_list;
    }

    private function create_payment($purchase_data)
    {

        $form_id = intval($purchase_data['post_data']['give-form-id']);
        $price_id = isset($purchase_data['post_data']['give-price-id']) ? $purchase_data['post_data']['give-price-id'] : '';

        // Collect payment data.
        $insert_payment_data = array(
            'price' => $purchase_data['price'],
            'give_form_title' => $purchase_data['post_data']['give-form-title'],
            'give_form_id' => $form_id,
            'give_price_id' => $price_id,
            'date' => $purchase_data['date'],
            'user_email' => $purchase_data['user_email'],
            'purchase_key' => $purchase_data['purchase_key'],
            'currency' => give_get_currency($form_id, $purchase_data),
            'user_info' => $purchase_data['user_info'],
            'status' => 'pending',
            'gateway' => 'billplz',
        );

        /**
         * Filter the payment params.
         *
         * @since 3.0.2
         *
         * @param array $insert_payment_data
         */
        $insert_payment_data = apply_filters('give_create_payment', $insert_payment_data);

        // Record the pending payment.
        return give_insert_payment($insert_payment_data);
    }

    private function get_billplz($purchase_data)
    {

        $form_id = intval($purchase_data['post_data']['give-form-id']);

        $custom_donation = give_get_meta($form_id, 'billplz_customize_billplz_donations', true, 'global');
        $status = give_is_setting_enabled($custom_donation, 'enabled');

        if ($status) {
            return array(
                'api_key' => give_get_meta($form_id, 'billplz_api_key', true),
                'collection_id' => give_get_meta($form_id, 'billplz_collection_id', true),
                'x_signature' => give_get_meta($form_id, 'billplz_x_signature_key', true),
                'description' => give_get_meta($form_id, 'billplz_description', true, true),
                'reference_1_label' => give_get_meta($form_id, 'billplz_reference_1_label', true),
                'reference_1' => give_get_meta($form_id, 'billplz_reference_1', true),
                'reference_2_label' => give_get_meta($form_id, 'billplz_reference_2_label', true),
                'reference_2' => give_get_meta($form_id, 'billplz_reference_2', true),
            );
        }
        return array(
            'api_key' => give_get_option('billplz_api_key'),
            'collection_id' => give_get_option('billplz_collection_id'),
            'x_signature' => give_get_option('billplz_x_signature_key'),
            'description' => give_get_option('billplz_description', true),
            'reference_1_label' => give_get_option('billplz_reference_1_label'),
            'reference_1' => give_get_option('billplz_reference_1'),
            'reference_2_label' => give_get_option('billplz_reference_2_label'),
            'reference_2' => give_get_option('billplz_reference_2'),
        );
    }

    public static function get_listener_url($payment_id)
    {
        $passphrase = get_option(self::LISTENER_PASSPHRASE, false);
        if (!$passphrase) {
            $passphrase = md5(site_url() . time());
            update_option(self::LISTENER_PASSPHRASE, $passphrase);
        }

        $arg = array(
            self::QUERY_VAR => $passphrase,
            'payment_id' => $payment_id,
        );
        return add_query_arg($arg, site_url('/'));
    }

    public function process_payment($purchase_data)
    {
        // Validate nonce.
        give_validate_nonce($purchase_data['gateway_nonce'], 'give-gateway');

        $payment_id = $this->create_payment($purchase_data);

        // Check payment.
        if (empty($payment_id)) {
            // Record the error.
            give_record_gateway_error(__('Payment Error', 'give-billplz'), sprintf( /* translators: %s: payment data */
                __('Payment creation failed before sending donor to Billplz. Payment data: %s', 'give-billplz'), json_encode($purchase_data)), $payment_id);
            // Problems? Send back.
            give_send_back_to_checkout();
        }

        $billplz_key = $this->get_billplz($purchase_data);

        $name = $purchase_data['user_info']['first_name'] . ' ' . $purchase_data['user_info']['last_name'];

        $parameter = array(
            'collection_id' => trim($billplz_key['collection_id']),
            'email' => $purchase_data['user_email'],
            'name' => empty($name) ? $purchase_data['user_email'] : trim($name),
            'amount' => strval($purchase_data['price'] * 100),
            'callback_url' => self::get_listener_url($payment_id),
            'description' => substr(trim($billplz_key['description']), 0, 120),
        );

        $optional = array(
            'redirect_url' => $parameter['callback_url'],
            'reference_1_label' => substr(trim($billplz_key['reference_1_label']), 0, 20),
            'reference_1' => substr(trim($billplz_key['reference_1']), 0, 120),
            'reference_2_label' => substr(trim($billplz_key['reference_2_label']), 0, 20),
            'reference_2' => substr(trim($billplz_key['reference_2']), 0, 120),
        );

        // PHP 5.6 return empty substr as false. Thus, we need to unset the key.
        if ($optional['reference_1'] === false) {
            unset($optional['reference_1']);
        }
        if ($optional['reference_2'] === false) {
            unset($optional['reference_2']);
        }
        if ($optional['reference_1_label'] === false) {
            unset($optional['reference_1_label']);
        }
        if ($optional['reference_2_label'] === false) {
            unset($optional['reference_2_label']);
        }

        $parameter = apply_filters('give_billplz_bill_mandatory_param', $parameter, $purchase_data['post_data']);
        $optional = apply_filters('give_billplz_bill_optional_param', $optional, $purchase_data['post_data']);

        $connect = new BillplzGiveWPConnect($billplz_key['api_key']);
        $connect->setStaging(give_is_test_mode());
        $billplz = new BillplzGiveAPI($connect);

        list($rheader, $rbody) = $billplz->toArray($billplz->createBill($parameter, $optional));

        if ($rheader !== 200) {
            // Record the error.
            give_record_gateway_error(__('Payment Error', 'give-billplz'), sprintf( /* translators: %s: payment data */
                __('Bill creation failed. Error message: %s', 'give-billplz'), json_encode($rbody)), $payment_id);
            // Problems? Send back.
            give_send_back_to_checkout('?payment-mode=' . $purchase_data['post_data']['give-gateway']);
        }

        give_update_meta($payment_id, 'billplz_id', $rbody['id']);

        wp_redirect($rbody['url']);
        exit;
    }

    public function give_billplz_cc_form($form_id)
    {
        // ob_start();

        $post_billlz_customize_option = give_get_meta($form_id, 'billplz_customize_billplz_donations', true, 'global');

        // Enable Default fields (billing info)
        $post_billplz_cc_fields = give_get_meta($form_id, 'billplz_collect_billing', true);
        $global_billplz_cc_fields = give_get_option('billplz_collect_billing');

        // Output Address fields if global option is on and user hasn't elected to customize this form's offline donation options
        if (
            (give_is_setting_enabled($post_billlz_customize_option, 'global') && give_is_setting_enabled($global_billplz_cc_fields))
            || (give_is_setting_enabled($post_billlz_customize_option, 'enabled') && give_is_setting_enabled($post_billplz_cc_fields))
        ) {
            give_default_cc_address_fields($form_id);
            return true;
        }

        return false;
        // echo ob_get_clean();
    }

    private function publish_payment($payment_id, $data)
    {
        if ('publish' !== get_post_status($payment_id)) {
            give_update_payment_status($payment_id, 'publish');
            if ($data['type'] === 'redirect') {
                give_insert_payment_note($payment_id, "Bill ID: {$data['id']}.");
            } else {
                give_insert_payment_note($payment_id, "Bill ID: {$data['id']}. URL: {$data['url']}");
            }
        }
    }

    public function return_listener()
    {
        if (!isset($_GET[self::QUERY_VAR])) {
            return;
        }

        $passphrase = get_option(self::LISTENER_PASSPHRASE, false);
        if (!$passphrase) {
            return;
        }

        if ($_GET[self::QUERY_VAR] != $passphrase) {
            return;
        }

        if (!isset($_GET['payment_id'])) {
            status_header(403);
            exit;
        }

        $payment_id = preg_replace('/\D/', '', $_GET['payment_id']);
        $form_id = give_get_payment_form_id($payment_id);

        $custom_donation = give_get_meta($form_id, 'billplz_customize_billplz_donations', true, 'global');
        $status = give_is_setting_enabled($custom_donation, 'enabled');

        if ($status) {
            $x_signature = trim(give_get_meta($form_id, 'billplz_x_signature_key', true));
        } else {
            $x_signature = trim(give_get_option('billplz_x_signature_key'));
        }

        try {
            $data = BillplzGiveWPConnect::getXSignature($x_signature);
        } catch (Exception $e) {
            status_header(403);
            exit('Failed X Signature Validation');
        }

        if ($data['id'] !== give_get_meta($payment_id, 'billplz_id', true)) {
            status_header(404);
            exit('No Billplz ID found');
        }

        if ($data['paid'] && give_get_payment_status($payment_id)) {
            $this->publish_payment($payment_id, $data);
        }

        if ($data['type'] === 'redirect') {
            if ($data['paid']) {
                //give_send_to_success_page();
                $return = add_query_arg(array(
                    'payment-confirmation' => 'billplz',
                    'payment-id' => $payment_id,
                ), get_permalink(give_get_option('success_page')));
            } else {
                $return = give_get_failed_transaction_uri('?payment-id=' . $payment_id);
            }

            wp_redirect($return);
        }
        exit;
    }

    public function give_billplz_success_page_content($content)
    {
        if ( ! isset( $_GET['payment-id'] ) && ! give_get_purchase_session() ) {
          return $content;
        }

        $payment_id = isset( $_GET['payment-id'] ) ? absint( $_GET['payment-id'] ) : false;

        if ( ! $payment_id ) {
            $session    = give_get_purchase_session();
            $payment_id = give_get_donation_id_by_key( $session['purchase_key'] );
        }

        $payment = get_post( $payment_id );
        if ( $payment && 'pending' === $payment->post_status ) {

            // Payment is still pending so show processing indicator to fix the race condition.
            ob_start();

            give_get_template_part( 'payment', 'processing' );

            $content = ob_get_clean();

        }

        return $content;
    }
}
Give_Billplz_Gateway::get_instance();
