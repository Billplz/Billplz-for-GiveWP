<?php
/**
 * Billplz Helper Functions
 *
 * @package     Give
 * @copyright   Copyright (c) 2017, Billplz
 */
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Billplz doesn't use Credit Card.
 *
 * @access      public
 * @since       1.0
 *
 * @param      $form_id
 * @param bool $echo
 *
 * @return string $form
 */
function give_billplz_credit_card_form($form_id, $echo = true)
{

    $billing_fields_enabled = give_get_option('billplz_collect_billing');

    if ($billing_fields_enabled) {
        do_action('give_after_cc_fields');
    } else {
        //Remove Address Fields if user has option enabled
        remove_action('give_after_cc_fields', 'give_default_cc_address_fields');
    }

    return $form;
}
add_action('give_billplz_cc_form', 'give_billplz_credit_card_form');