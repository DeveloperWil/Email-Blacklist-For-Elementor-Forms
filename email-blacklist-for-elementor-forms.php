<?php
/**
 * Plugin Name: Email Blacklist for Elementor Forms
 * Plugin URI: https://zeropointdevelopment.com
 * Description: Adds a text area control called "Blacklist" to the Elementor Forms control. Blocks outgoing emails if they match with any on the blacklist.
 * Version: 1.0.2
 * Author: Wil Brown
 * Author URI: https://zeropointdevelopment.com/about
 * Text Domain: elementor-forms-blacklist
 * Domain Path: /languages
 * License: GPL2+
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
/**
 * Checks to see if Elementor Pro is installed. Returns admin error and deactivates plugin if Elementor Pro is not installed.
 *
 * @return void
 */
function ebfef_check_for_elementor(){
	if (!function_exists('is_plugin_active')) {
		include_once(ABSPATH . 'wp-admin/includes/plugin.php');
	}
	if( !is_plugin_active( 'elementor-pro/elementor-pro.php' ) ){
		add_action( 'admin_notices', 'ebfef_fail_load' );
		deactivate_plugins( plugin_basename( __FILE__) );
	}
}
add_action( 'plugins_loaded', 'ebfef_check_for_elementor' );

/**
 * Admin error message if Elementor Pro is not installed
 *
 * @return void
 */
function ebfef_fail_load() {
	load_plugin_textdomain( 'elementor-forms-blacklist' );

	$message = '<div class="error">';
	$message .= '<h3>' . esc_html__( 'Elementor Pro plugin is missing.', 'elementor-forms-blacklist' ) . '</h3>';
	$message .= '<p>' . esc_html__( 'You need to install and active the Elementor Pro plugin for Email Blacklist for Elementor Forms to work!.', 'elementor-forms-blacklist' ) . '</p>';
	$message .= '</div>';
	echo $message;
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
    if ( empty( $blacklist ) ) {
        return;
    }
	$blacklist_values = explode( ',', $blacklist );

	$blocked_email_msg = "that email or domain";
	$has_blocked_email = false;
    // Loop through the block list and match with outgoing email value
    foreach( $blacklist_values as $blacklist_value ) {
        $blacklist_value = trim($blacklist_value); // Trim spaces
        if ( empty($blacklist_value) ) {
            continue; // Skip empty blacklist values
        }
        if ( strpos( $field['value'], $blacklist_value ) !== false || $field['value'] === $blacklist_value ) {
            $has_blocked_email = true;
            $blocked_email_msg = '"' . $blacklist_value . '"';
            break; // Stop further checking once a match is found
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
