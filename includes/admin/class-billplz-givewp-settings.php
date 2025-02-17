<?php

/**
 * Gateway settings class.
 * 
 * @since 3.0.0
 * @since 4.0.0 Renamed from `Give_Billplz_Settings`.
 */
class Billplz_GiveWP_Settings {
    /**
     * Constructor.
     */
    public function __construct() {
        add_filter( 'give_get_sections_gateways', array( $this, 'register_sections' ) );
        add_filter( 'give_get_settings_gateways', array( $this, 'register_settings' ) );
    }

    /**
     * Register sections.
     * 
     * @since 3.0.0
     * 
     * @param array $sections
     * @return array
     */
    public function register_sections( $sections ) {
        $sections['billplz'] = __( 'Billplz', 'billplz-givewp' );

        return $sections;
    }

    /**
     * Register settings.
     * 
     * @since 3.0.0
     * 
     * @param array $settings
     * @return array
     */
    public function register_settings( $settings ) {
        $section = give_get_current_setting_section();

        if ( $section !== 'billplz' ) {
            return $settings;
        }

        return array(
            array(
                'name' => __( 'Billplz Settings', 'billplz-givewp' ),
                'id' => 'give_title_gateway_billplz',
                'type' => 'title',
            ),
            array(
                'name' => __( 'API Secret Key', 'billplz-givewp' ),
                'desc' => __( 'Enter your API Secret Key, found in your Billplz Account Settings.', 'billplz-givewp' ),
                'id' => 'billplz_api_key',
                'type' => 'text',
            ),
            array(
                'name' => __( 'Collection ID', 'billplz-givewp' ),
                'desc' => __( 'Enter your Billing Collection ID.', 'billplz-givewp' ),
                'id' => 'billplz_collection_id',
                'type' => 'text',
            ),
            array(
                'name' => __( 'X Signature Key', 'billplz-givewp' ),
                'desc' => __( 'Enter your X Signature Key, found in your Billplz Account Settings.', 'billplz-givewp' ),
                'id' => 'billplz_x_signature_key',
                'type' => 'text',
            ),
            array(
                'name' => __( 'Bill Description', 'billplz-givewp' ),
                'desc' => __( 'Enter description to be included in the bill.', 'billplz-givewp' ),
                'id' => 'billplz_description',
                'type' => 'text',
            ),
            array(
                'name' => __( 'Reference 1 Label', 'billplz-givewp' ),
                'desc' => __( 'Enter reference 1 label.', 'billplz-givewp' ),
                'id' => 'billplz_reference_1_label',
                'type' => 'text',
            ),
            array(
                'name' => __( 'Reference 1', 'billplz-givewp' ),
                'desc' => __( 'Enter reference 1.', 'billplz-givewp' ),
                'id' => 'billplz_reference_1',
                'type' => 'text',
            ),
            array(
                'name' => __( 'Reference 2 Label', 'billplz-givewp' ),
                'desc' => __( 'Enter reference 2 label.', 'billplz-givewp' ),
                'id' => 'billplz_reference_2_label',
                'type' => 'text',
            ),
            array(
                'name' => __( 'Reference 2', 'billplz-givewp' ),
                'desc' => __( 'Enter reference 2.', 'billplz-givewp' ),
                'id' => 'billplz_reference_2',
                'type' => 'text',
            ),
            array(
                'name' => __( 'Billing Fields', 'billplz-givewp' ),
                'desc' => __( 'This option will enable the billing details section for Billplz which requires the donor\'s address to complete the donation. These fields are not required by Billplz to process the transaction, but you may have the need to collect the data.', 'billplz-givewp' ),
                'id' => 'billplz_collect_billing',
                'type' => 'radio_inline',
                'default' => 'disabled',
                'options' => array(
                    'enabled' => __( 'Enabled', 'billplz-givewp' ),
                    'disabled' => __( 'Disabled', 'billplz-givewp' ),
                ),
            ),
            array(
                'type' => 'sectionend',
                'id' => 'give_title_gateway_billplz',
            ),
        );
    }
}

new Billplz_GiveWP_Settings();
