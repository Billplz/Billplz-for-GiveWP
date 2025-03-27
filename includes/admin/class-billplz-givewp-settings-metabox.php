<?php
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Donation metabox settings class.
 * 
 * @since 3.0.1
 */
class Billplz_GiveWP_Metabox_Settings {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_filter( 'give_metabox_form_data_settings', array( $this, 'register_settings' ) );
        add_filter( 'give_forms_billplz_metabox_fields', array($this, 'register_settings_fields'));
    }

    /**
     * @since 3.0.02
     * 
     * @param string $hook
     */
    public function enqueue_scripts( $hook ) {
        if ( in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
            wp_enqueue_script( 'billplz-givewp-donation-metabox', BILLPLZ_GIVEWP_URL . 'assets/js/donation-metabox.js', array( 'give-admin-scripts' ), BILLPLZ_GIVEWP_VERSION, true );
        }
    }

    /**
     * Register metabox settings.
     * 
     * @since 3.0.2
     * 
     * @param array $settings
     */
    public function register_settings( $settings ) {
        if ( give_is_gateway_active( 'billplz' ) ) {
            $settings[ 'billplz_options' ] = apply_filters( 'give_forms_billplz_options', array(
                'id' => 'billplz_options',
                'title' => __( 'Billplz', 'billplz-for-givewp' ),
                'icon-html' => '<i class="fas fa-wallet"></i>',
                'fields' => apply_filters( 'give_forms_billplz_metabox_fields', array() ),
            ) );
        }

        return $settings;
    }

    /**
     * Register metabox settings fields.
     * 
     * @since 3.0.2
     * 
     * @param array $settings
     */
    public function register_settings_fields( $settings ) {
        $gateways = (array) give_get_option( 'gateways' );

        // Bailout: Do not show Billplz payment setting in to metabox if its disabled globally
        if ( in_array( 'billplz', $gateways ) ) {
            return $settings;
        }

        if ( !give_is_gateway_active( 'billplz' ) ) {
            return $settings;
        }

        $billplz_settings = array(
            array(
                'name' => __( 'Billplz', 'billplz-for-givewp' ),
                'desc' => __( 'Do you want to customize the donation instructions for this form?', 'billplz-for-givewp' ),
                'id' => 'billplz_customize_billplz_donations',
                'type' => 'radio_inline',
                'default' => 'global',
                'options' => apply_filters( 'give_forms_content_options_select', array(
                    'global' => __( 'Global Option', 'billplz-for-givewp' ),
                    'enabled' => __( 'Customize', 'billplz-for-givewp' ),
                    'disabled' => __( 'Disable', 'billplz-for-givewp' ),
                ) ),
            ),
            array(
                'name' => __( 'API Secret Key', 'billplz-for-givewp' ),
                'desc' => __( 'Enter your API Secret Key, found in your Billplz Account Settings.', 'billplz-for-givewp' ),
                'id' => 'billplz_api_key',
                'type' => 'text',
            ),
            array(
                'name' => __( 'Collection ID', 'billplz-for-givewp' ),
                'desc' => __( 'Enter your Billing Collection ID.', 'billplz-for-givewp' ),
                'id' => 'billplz_collection_id',
                'type' => 'text',
            ),
            array(
                'name' => __( 'X Signature Key', 'billplz-for-givewp' ),
                'desc' => __( 'Enter your X Signature Key, found in your Billplz Account Settings.', 'billplz-for-givewp' ),
                'id' => 'billplz_x_signature_key',
                'type' => 'text',
            ),
            array(
                'name' => __( 'Bill Description', 'billplz-for-givewp' ),
                'desc' => __( 'Enter description to be included in the bill.', 'billplz-for-givewp' ),
                'id' => 'billplz_description',
                'type' => 'text',
            ),
            array(
                'name' => __( 'Reference 1 Label', 'billplz-for-givewp' ),
                'desc' => __( 'Enter reference 1 label.', 'billplz-for-givewp' ),
                'id' => 'billplz_reference_1_label',
                'type' => 'text',
            ),
            array(
                'name' => __( 'Reference 1', 'billplz-for-givewp' ),
                'desc' => __( 'Enter reference 1.', 'billplz-for-givewp' ),
                'id' => 'billplz_reference_1',
                'type' => 'text',
            ),
            array(
                'name' => __( 'Reference 2 Label', 'billplz-for-givewp' ),
                'desc' => __( 'Enter reference 2 label.', 'billplz-for-givewp' ),
                'id' => 'billplz_reference_2_label',
                'type' => 'text',
            ),
            array(
                'name' => __( 'Reference 2', 'billplz-for-givewp' ),
                'desc' => __( 'Enter reference 2.', 'billplz-for-givewp' ),
                'id' => 'billplz_reference_2',
                'type' => 'text',
            ),
            array(
                'name' => __( 'Billing Fields', 'billplz-for-givewp' ),
                'desc' => __( 'This option will enable the billing details section for Billplz which requires the donor\'s address to complete the donation. These fields are not required by Billplz to process the transaction, but you may have the need to collect the data.', 'billplz-for-givewp' ),
                'id' => 'billplz_collect_billing',
                'row_classes' => 'give-subfield give-hidden',
                'type' => 'radio_inline',
                'default' => 'disabled',
                'options' => array(
                    'enabled' => __( 'Enabled', 'billplz-for-givewp' ),
                    'disabled' => __( 'Disabled', 'billplz-for-givewp' ),
                ),
            ),
        );;

        return array_merge( $settings, $billplz_settings );
    }
}

new Billplz_GiveWP_Metabox_Settings();
