<?php
/**
 * ConvertKit Resource class.
 *
 * @package CKWC
 * @author ConvertKit
 */

/**
 * Abstract class defining variables and functions for a ConvertKit API Resource
 * (forms, sequences, tags).
 *
 * @since   1.3.0
 */
class ConvertKit_MM_Resource extends ConvertKit_Resource_V4 {

	/**
	 * Constructor.
	 *
	 * @since   1.3.0
	 */
	public function __construct() {

		// Initialize the API if the Access Token has been defined in the Plugin Settings.
		$settings = new ConvertKit_MM_Settings();
		if ( $settings->has_access_and_refresh_token() ) {
			$this->api = new ConvertKit_API_V4(
				CONVERTKIT_MM_OAUTH_CLIENT_ID,
				CONVERTKIT_MM_OAUTH_CLIENT_REDIRECT_URI,
				$settings->get_access_token(),
				$settings->get_refresh_token(),
				$settings->debug_enabled()
			);
		}

		// Get last query time and existing resources.
		$this->last_queried = get_option( $this->settings_name . '_last_queried' );
		$this->resources    = get_option( $this->settings_name );

	}

	/**
	 * Fetches resources (custom fields, forms, sequences or tags) from the API, storing them in the options table
	 * with a last queried timestamp.
	 *
	 * If the refresh results in a 401, removes the access and refresh tokens from the settings.
	 *
	 * @since   1.3.7
	 *
	 * @return  WP_Error|array
	 */
	public function refresh() {

		// Call parent refresh method.
		$result = parent::refresh();

		// If an error occured, maybe delete credentials from the Plugin's settings
		// if the error is a 401 unauthorized.
		if ( is_wp_error( $result ) ) {
			convertkit_mm_maybe_delete_credentials( $result );
		}

		return $result;

	}

}
