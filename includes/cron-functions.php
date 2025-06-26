<?php
/**
 * WordPress Cron functions.
 *
 * @package    ConvertKit_MM
 * @author     ConvertKit
 */

/**
 * Refresh the OAuth access token, triggered by WordPress' Cron.
 *
 * @since   1.3.3
 */
function convertkit_mm_refresh_token() {

	// Initialize settings class.
	$settings = new ConvertKit_MM_Settings();

	// Bail if no existing access and refresh token exists.
	if ( ! $settings->has_access_token() ) {
		return;
	}
	if ( ! $settings->has_refresh_token() ) {
		return;
	}

	// Initialize the API.
	$api = new ConvertKit_MM_API(
		CONVERTKIT_OAUTH_CLIENT_ID,
		CONVERTKIT_OAUTH_CLIENT_REDIRECT_URI,
		$settings->get_access_token(),
		$settings->get_refresh_token(),
		$settings->debug_enabled(),
		'cron_refresh_token'
	);

	// Refresh the token.
	$result = $api->refresh_token();

	// If an error occured, don't save the new tokens.
	// Logging is handled by the ConvertKit_API_V4 class.
	if ( is_wp_error( $result ) ) {
		return;
	}

	$settings->save(
		array(
			'access_token'  => $result['access_token'],
			'refresh_token' => $result['refresh_token'],
			'token_expires' => ( $result['created_at'] + $result['expires_in'] ),
		)
	);

}

// Register action to run above function; this action is created by WordPress' wp_schedule_event() function
// in update_credentials() in the ConvertKit_Settings class.
add_action( 'convertkit_mm_refresh_token', 'convertkit_mm_refresh_token' );