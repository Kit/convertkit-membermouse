<?php
/**
 * Uninstall routine. Runs when the Plugin is deleted
 * at Plugins > Delete.
 *
 * @package ConvertKit_MM
 * @author ConvertKit
 */

// If uninstall.php is not called by WordPress, die.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// Only WordPress and PHP methods can be used. Plugin classes and methods
// are not reliably available due to the Plugin being deactivated and going
// through deletion now.

// Get settings.
$settings = get_option( 'convertkit-mm-options' );

// Bail if no settings exist.
if ( ! $settings ) {
	return;
}

// Revoke Access Token.
if ( array_key_exists( 'access_token', $settings ) && ! empty( $settings['access_token'] ) ) {
	wp_remote_post(
		'https://api.kit.com/v4/oauth/revoke',
		array(
			'headers' => array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json',
			),
			'body'    => wp_json_encode(
				array(
					'client_id' => 'U4aHnnj_QgRrZOdtWUJ6vtpulZSloLKn-7e551T-Exw',
					'token'     => $settings['access_token'],
				)
			),
			'timeout' => 5,
		)
	);
}

// Revoke Refresh Token.
if ( array_key_exists( 'refresh_token', $settings ) && ! empty( $settings['refresh_token'] ) ) {
	wp_remote_post(
		'https://api.kit.com/v4/oauth/revoke',
		array(
			'headers' => array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json',
			),
			'body'    => wp_json_encode(
				array(
					'client_id' => 'U4aHnnj_QgRrZOdtWUJ6vtpulZSloLKn-7e551T-Exw',
					'token'     => $settings['refresh_token'],
				)
			),
			'timeout' => 5,
		)
	);
}

// Remove credentials from settings.
$settings['access_token']  = '';
$settings['refresh_token'] = '';
$settings['token_expires'] = '';
$settings['api-key']       = '';

// Save settings.
update_option( 'convertkit-mm-options', $settings );
