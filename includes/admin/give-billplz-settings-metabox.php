<?php

class Give_Billplz_Settings_Metabox
{
    private static $instance;

    private function __construct()
    {

    }

    public static function get_instance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Setup hooks.
     */
    public function setup_hooks()
    {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_js'));
            add_filter('give_forms_billplz_metabox_fields', array($this, 'give_billplz_add_settings'));
            add_filter('give_metabox_form_data_settings', array($this, 'add_billplz_setting_tab'), 0, 1);
        }
    }

    public function add_billplz_setting_tab($settings)
    {
        if (give_is_gateway_active('billplz')) {
            $settings['billplz_options'] = apply_filters('give_forms_billplz_options', array(
                'id' => 'billplz_options',
                'title' => __('Billplz', 'give'),
                'icon-html' => '<span class="give-icon give-icon-purse"></span>',
                'fields' => apply_filters('give_forms_billplz_metabox_fields', array()),
            ));
        }

        return $settings;
    }

    public function give_billplz_add_settings($settings)
    {

        // Bailout: Do not show offline gateways setting in to metabox if its disabled globally.
        if (in_array('billplz', (array) give_get_option('gateways'))) {
            return $settings;
        }

        $is_gateway_active = give_is_gateway_active('billplz');

        //this gateway isn't active
        if (!$is_gateway_active) {
            //return settings and bounce
            return $settings;
        }

        //Fields
        $check_settings = array(

            array(
                'name' => __('Billplz', 'give-billplz'),
                'desc' => __('Do you want to customize the donation instructions for this form?', 'give-billplz'),
                'id' => 'billplz_customize_billplz_donations',
                'type' => 'radio_inline',
                'default' => 'global',
                'options' => apply_filters('give_forms_content_options_select', array(
                    'global' => __('Global Option', 'give-billplz'),
                    'enabled' => __('Customize', 'give-billplz'),
                    'disabled' => __('Disable', 'give-billplz'),
                )
                ),
            ),
            array(
                'name' => __('API Secret Key', 'give-billplz'),
                'desc' => __('Enter your API Secret Key, found in your Billplz Account Settings.', 'give-billplz'),
                'id' => 'billplz_api_key',
                'type' => 'text',
                'row_classes' => 'give-billplz-key',
            ),
            array(
                'name' => __('Collection ID', 'give-billplz'),
                'desc' => __('Enter your Billing Collection ID.', 'give-billplz'),
                'id' => 'billplz_collection_id',
                'type' => 'text',
                'row_classes' => 'give-billplz-key',
            ),
            array(
                'name' => __('X Signature Key', 'give-billplz'),
                'desc' => __('Enter your X Signature Key, found in your Billplz Account Settings.', 'give-billplz'),
                'id' => 'billplz_x_signature_key',
                'type' => 'text',
                'row_classes' => 'give-billplz-key',
            ),
            array(
                'name' => __('Bill Description', 'give-billplz'),
                'desc' => __('Enter description to be included in the bill.', 'give-billplz'),
                'id' => 'billplz_description',
                'type' => 'text',
                'row_classes' => 'give-billplz-key',
            ),
            array(
                'name' => __('Reference 1 Label', 'give-billplz'),
                'desc' => __('Enter reference 1 label.', 'give-billplz'),
                'id' => 'billplz_reference_1_label',
                'type' => 'text',
                'row_classes' => 'give-billplz-key',
            ),
            array(
                'name' => __('Reference 1', 'give-billplz'),
                'desc' => __('Enter reference 1.', 'give-billplz'),
                'id' => 'billplz_reference_1',
                'type' => 'text',
                'row_classes' => 'give-billplz-key',
            ),
            array(
                'name' => __('Reference 2 Label', 'give-billplz'),
                'desc' => __('Enter reference 2 label.', 'give-billplz'),
                'id' => 'billplz_reference_2_label',
                'type' => 'text',
                'row_classes' => 'give-billplz-key',
            ),
            array(
                'name' => __('Reference 2', 'give-billplz'),
                'desc' => __('Enter reference 2.', 'give-billplz'),
                'id' => 'billplz_reference_2',
                'type' => 'text',
                'row_classes' => 'give-billplz-key',
            ),
            array(
                'name' => __('Billing Fields', 'give-billplz'),
                'desc' => __('This option will enable the billing details section for Billplz which requires the donor\'s address to complete the donation. These fields are not required by Billplz to process the transaction, but you may have the need to collect the data.', 'give-billplz'),
                'id' => 'billplz_collect_billing',
                'row_classes' => 'give-subfield give-hidden',
                'type' => 'radio_inline',
                'default' => 'disabled',
                'options' => array(
                    'enabled' => __('Enabled', 'give-billplz'),
                    'disabled' => __('Disabled', 'give-billplz'),
                ),
            ),
        );

        return array_merge($settings, $check_settings);
    }

    public function enqueue_js($hook)
    {
        if ('post.php' === $hook || $hook === 'post-new.php') {
            wp_enqueue_script('give_billplz_each_form', GIVE_BILLPLZ_PLUGIN_URL . '/includes/js/meta-box.js');
        }
    }

}
Give_Billplz_Settings_Metabox::get_instance()->setup_hooks();
