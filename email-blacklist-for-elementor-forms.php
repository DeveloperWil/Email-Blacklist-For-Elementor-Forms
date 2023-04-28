<?php
/**
 * Plugin Name: Email Blacklist for Elementor Forms
 * Plugin URI: https://zeropointdevelopment.com
 * Description: Adds a text area control called "Blacklist" to the Elementor Forms control. Blocks outgoing emails if they match with any on the blacklist.
 * Version: 1.0.0
 * Author: Wil Brown
 * Author URI: https://zeropointdevelopment.com/about
 * Text Domain: email-blacklist-for-elementor-forms
 * License: GPL2+
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}


/**
 * Add new Blacklist control to the Elementor form widget
 *
 * @param $field
 *
 * @return void
 */
function elementor_forms_blacklist_control( $field ) {
    $field->add_control(
        'blacklist',
        [
            'label' => __( 'Email Blacklist', 'elementor-forms-blacklist' ),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'description' => __( 'Enter a comma-separated list of email addresses to block. e.g. @gmail.com or bob@yandex.com', 'email-blacklist-for-elementor-forms' ),
        ]
    );
}
add_action( 'elementor/element/form/section_form_fields/before_section_end', 'elementor_forms_blacklist_control' );


/**
 * Validate the form email address against the new blacklist.
 *
 * @param $field
 * @param $record
 * @param $ajax_handler
 *
 * @return void
 */
function elementor_forms_blacklist_validation( $field, $record, $ajax_handler ) {
	if(empty( $field)) return;
	// Get blacklist form settings
	$settings = $record->get( 'form_settings' );
	$blacklist = sanitize_textarea_field( $settings['blacklist'] );
	$blacklist_values = explode( ',', $blacklist );

	$blocked_email_msg = "that email or domain";
	$has_blocked_email = false;
	// Loop through the block list and match with outgoing email value
	foreach( $blacklist_values as $blacklist_value ){
		if( ( strpos( $field['value'], $blacklist_value ) || ( $field['value'] === $blacklist_value ) ) && !$has_blocked_email){
			$has_blocked_email = true;
			$blocked_email_msg = '"'. $blacklist_value . '"';
		}
	}

	//If there's a match on the blacklist, show an error.
	if( $has_blocked_email ){
		$ajax_handler->add_error( $field['id'], esc_html__( 'Sorry, '. $blocked_email_msg.' is blocked for this form. Try another email address.' , 'email-blacklist-for-elementor-forms' ) );
		return;
	}

    // Validate email format
    if ( ! is_email( $field['value'] ) ) {
        $ajax_handler->add_error( $field['id'], esc_html__( 'Invalid email format','email-blacklist-for-elementor' ) );
    }

}
add_action( 'elementor_pro/forms/validation/email', 'elementor_forms_blacklist_validation', 10, 3 );