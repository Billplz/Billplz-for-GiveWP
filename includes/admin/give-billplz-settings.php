<?php

/**
 * Class Give_Billplz_Settings
 *
 * @since 3.0.2
 */
class Give_Billplz_Settings
{

    /**
     * @access private
     * @var Give_Billplz_Settings $instance
     */
    private static $instance;

    /**
     * @access private
     * @var string $section_id
     */
    private $section_id;

    /**
     * @access private
     *
     * @var string $section_label
     */
    private $section_label;

    /**
     * Give_Billplz_Settings constructor.
     */
    private function __construct()
    {
    }

    /**
     * get class object.
     *
     * @return Give_Billplz_Settings
     */
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

        $this->section_id = 'billplz';
        $this->section_label = __('Billplz', 'give-billplz');

        if (is_admin()) {
            // Add settings.
            add_filter('give_get_settings_gateways', array($this, 'add_settings'), 99);
            add_filter('give_get_sections_gateways', array($this, 'add_sections'), 99);
        }
    }

    /**
     * Add setting section.
     *
     * @param array $sections Array of section.
     *
     * @return array
     */
    public function add_sections($sections)
    {
        $sections[$this->section_id] = $this->section_label;

        return $sections;
    }

    /**
     * Add plugin settings.
     *
     * @param array $settings Array of setting fields.
     *
     * @return array
     */
    public function add_settings($settings)
    {
        $current_section = give_get_current_setting_section();

        if ($current_section != 'billplz') {
            return $settings;
        }

        $give_billplz_settings = array(
            array(
                'name' => __('Billplz Settings', 'give-billplz'),
                'id' => 'give_title_gateway_billplz',
                'type' => 'title',
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
                'name'    => __('Billplz Donation Instructions', 'give-billplz'),
                'desc'    => __('The Billplz Donation Instructions are a chance for you to educate the donor on how to best submit donations. These instructions appear directly on the form, and after submission of the form. Note: You may also customize the instructions on individual forms as needed.', 'give-billplz'),
                'id'      => 'global_billplz_donation_content',
                'default' => Give_Billplz_Gateway::get_default_billplz_donation_content(),
                'type'    => 'wysiwyg',
                'options' => [
                    'textarea_rows' => 6,
                ],
            ),
            array(
                'type' => 'sectionend',
                'id' => 'give_title_gateway_billplz',
            ),
        );

        return array_merge($settings, $give_billplz_settings);
    }
}

Give_Billplz_Settings::get_instance()->setup_hooks();
