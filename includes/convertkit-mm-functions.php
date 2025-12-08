<?php
/**
 * General plugin functions.
 *
 * @package    ConvertKit_MM
 * @author     ConvertKit
 */

/**
 * Debug log.
 *
 * @since   1.0.2
 *
 * @param   string $log        Log filename.
 * @param   string $message    Message to put in the log.
 */
function convertkit_mm_log( $log, $message ) {

	// Initialize settings class.
	$settings = new ConvertKit_MM_Settings();

	// Bail if debugging isn't enabled.
	if ( ! $settings->debug_enabled() ) {
		return;
	}

	// Write to log.
	$log     = fopen( CONVERTKIT_MM_PATH . '/log-' . $log . '.txt', 'a+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$message = '[' . gmdate( 'd-m-Y H:i:s' ) . '] ' . $message . PHP_EOL;
	fwrite( $log, $message ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	fclose( $log ); // phpcs:ignore WordPress.WP.AlternativeFunctions

}

/**
 * Saves the new access token, refresh token and its expiry, and schedules
 * a WordPress Cron event to refresh the token on expiry.
 *
 * @since   1.3.7
 *
 * @param   array  $result      New Access Token, Refresh Token and Expiry.
 * @param   string $client_id   OAuth Client ID used for the Access and Refresh Tokens.
 */
function convertkit_mm_maybe_update_credentials( $result, $client_id ) {

	// Don't save these credentials if they're not for this Client ID.
	// They're for another Kit Plugin that uses OAuth.
	if ( $client_id !== CONVERTKIT_MM_OAUTH_CLIENT_ID ) {
		return;
	}

	$settings = new ConvertKit_MM_Settings();
	$settings->update_credentials( $result );

}

/**
 * Deletes the stored access token, refresh token and its expiry from the Plugin settings,
 * and clears any existing scheduled WordPress Cron event to refresh the token on expiry,
 * when either:
 * - The access token is invalid
 * - The access token expired, and refreshing failed
 *
 * @since   1.3.7
 *
 * @param   WP_Error $result      Error result.
 * @param   string   $client_id   OAuth Client ID used for the Access and Refresh Tokens.
 */
function convertkit_mm_maybe_delete_credentials( $result, $client_id ) {

	// Don't save these credentials if they're not for this Client ID.
	// They're for another Kit Plugin that uses OAuth.
	if ( $client_id !== CONVERTKIT_MM_OAUTH_CLIENT_ID ) {
		return;
	}

	// If the error isn't a 401, don't delete credentials.
	// This could be e.g. a temporary network error, rate limit or similar.
	if ( $result->get_error_data( 'convertkit_api_error' ) !== 401 ) {
		return;
	}

	// Persist an error notice in the WordPress Administration until the user fixes the problem.
	$admin_notices = new ConvertKit_MM_Admin_Notices();
	$admin_notices->add( 'authorization_failed' );

	$settings = new ConvertKit_MM_Settings();
	$settings->delete_credentials();

}

// Update Access Token when refreshed by the API class.
add_action( 'convertkit_api_get_access_token', 'convertkit_mm_maybe_update_credentials', 10, 2 );
add_action( 'convertkit_api_refresh_token', 'convertkit_mm_maybe_update_credentials', 10, 2 );

// Delete credentials if the API class uses a invalid access token.
// This prevents the Plugin making repetitive API requests that will 401.
add_action( 'convertkit_api_access_token_invalid', 'convertkit_mm_maybe_delete_credentials', 10, 2 );
