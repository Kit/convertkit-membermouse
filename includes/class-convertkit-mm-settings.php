<?php
/**
 * ConvertKit Plugin Settings class.
 *
 * @package ConvertKit_MM
 * @author ConvertKit
 */

/**
 * Class to read ConvertKit Plugin Settings.
 *
 * @since   1.2.2
 */
class ConvertKit_MM_Settings {

	/**
	 * Holds the Settings Key that stores site wide ConvertKit settings
	 *
	 * @since   1.2.2
	 *
	 * @var     string
	 */
	const SETTINGS_NAME = 'convertkit-mm-options';

	/**
	 * Holds the Settings
	 *
	 * @since   1.2.2
	 *
	 * @var     array
	 */
	private $settings = array();

	/**
	 * Constructor. Reads settings from options table, falling back to defaults
	 * if no settings exist.
	 *
	 * @since   1.2.2
	 */
	public function __construct() {

		// Get Settings.
		$settings = get_option( self::SETTINGS_NAME );

		// If no Settings exist, falback to default settings.
		if ( ! $settings ) {
			$this->settings = $this->get_defaults();
		} else {
			$this->settings = array_merge( $this->get_defaults(), $settings );
		}

		// Update Access Token when refreshed by the API class.
		add_action( 'convertkit_api_get_access_token', array( $this, 'update_credentials' ), 10, 2 );
		add_action( 'convertkit_api_refresh_token', array( $this, 'update_credentials' ), 10, 2 );

	}

	/**
	 * Returns Plugin settings.
	 *
	 * @since   1.2.2
	 *
	 * @return  array
	 */
	public function get() {

		return $this->settings;

	}

	/**
	 * Returns the setting for the given key.
	 *
	 * @since   1.2.2
	 *
	 * @param   string $key    Settings key.
	 * @return  string
	 */
	public function get_by_key( $key ) {

		if ( ! array_key_exists( $key, $this->settings ) ) {
			return '';
		}

		return $this->settings[ $key ];

	}

	/**
	 * Returns the API Key Plugin setting.
	 *
	 * @since   1.2.2
	 *
	 * @return  string
	 */
	public function get_api_key() {

		// Return API Key from settings.
		return $this->settings['api-key'];

	}

	/**
	 * Returns whether the API Key has been set in the Plugin settings.
	 *
	 * @since   1.2.2
	 *
	 * @return  bool
	 */
	public function has_api_key() {

		return ( ! empty( $this->get_api_key() ) ? true : false );

	}

	/**
	 * Returns the Access Token Plugin setting.
	 *
	 * @since   1.3.0
	 *
	 * @return  string
	 */
	public function get_access_token() {

		// Return Access Token from settings.
		return $this->settings['access_token'];

	}

	/**
	 * Returns whether the Access Token has been set in the Plugin settings.
	 *
	 * @since   1.3.0
	 *
	 * @return  bool
	 */
	public function has_access_token() {

		return ( ! empty( $this->get_access_token() ) ? true : false );

	}

	/**
	 * Returns the Refresh Token Plugin setting.
	 *
	 * @since   1.3.0
	 *
	 * @return  string
	 */
	public function get_refresh_token() {

		// Return Refresh Token from settings.
		return $this->settings['refresh_token'];

	}

	/**
	 * Returns whether the Refresh Token has been set in the Plugin settings.
	 *
	 * @since   1.3.0
	 *
	 * @return  bool
	 */
	public function has_refresh_token() {

		return ( ! empty( $this->get_refresh_token() ) );

	}

	/**
	 * Returns whether to use Access and Refresh Tokens for API requests,
	 * based on whether an Access Token and Refresh Token have been saved
	 * in the Plugin settings.
	 *
	 * @since   1.3.0
	 *
	 * @return  bool
	 */
	public function has_access_and_refresh_token() {

		return $this->has_access_token() && $this->has_refresh_token();

	}

	/**
	 * Returns the Access Token expiry timestamp.
	 *
	 * @since   1.3.0
	 *
	 * @return  int
	 */
	public function get_token_expiry() {

		// Return Token Expiry from settings.
		return $this->settings['token_expires'];

	}

	/**
	 * Returns whether debugging is enabled in the Plugin settings.
	 *
	 * @since   1.2.2
	 *
	 * @return  bool
	 */
	public function debug_enabled() {

		return ( $this->settings['debug'] === 'on' ? true : false );

	}

	/**
	 * Returns the mapping setting for the given MemberMouse Membership Level ID.
	 *
	 * @since   1.2.2
	 *
	 * @param   int $id     Membership Level ID.
	 * @return  string          Setting
	 */
	public function get_membership_level_mapping( $id ) {

		return $this->get_mapping( $id, 'level' );

	}

	/**
	 * Returns the mapping setting for the given MemberMouse Membership Level ID
	 * when the Level is removed from the User.
	 *
	 * @since   1.2.2
	 *
	 * @param   int $id     Membership Level ID.
	 * @return  string          Setting
	 */
	public function get_membership_level_cancellation_mapping( $id ) {

		return $this->get_mapping( $id, 'level', true );

	}

	/**
	 * Returns the mapping setting for the given MemberMouse Product ID.
	 *
	 * @since   1.2.2
	 *
	 * @param   int $id     Membership Level ID.
	 * @return  string          Setting
	 */
	public function get_product_mapping( $id ) {

		return $this->get_mapping( $id, 'product' );

	}

	/**
	 * Returns the mapping setting for the given MemberMouse Bundle ID.
	 *
	 * @since   1.2.2
	 *
	 * @param   int $id     Membership Level ID.
	 * @return  string          Setting
	 */
	public function get_bundle_mapping( $id ) {

		return $this->get_mapping( $id, 'bundle' );

	}

	/**
	 * Returns the mapping setting for the given MemberMouse Bundle ID
	 * when the Bundle is removed from the User.
	 *
	 * @since   1.2.2
	 *
	 * @param   int $id     Bundle ID.
	 * @return  string          Setting
	 */
	public function get_bundle_cancellation_mapping( $id ) {

		return $this->get_mapping( $id, 'bundle', true );

	}

	/**
	 * Saves the new access token, refresh token and its expiry, and schedules
	 * a WordPress Cron event to refresh the token on expiry.
	 *
	 * @since   1.3.0
	 *
	 * @param   array  $result      New Access Token, Refresh Token and Expiry.
	 * @param   string $client_id   OAuth Client ID used for the Access and Refresh Tokens.
	 */
	public function update_credentials( $result, $client_id ) {

		// Don't save these credentials if they're not for this Client ID.
		// They're for another ConvertKit Plugin that uses OAuth.
		if ( $client_id !== CONVERTKIT_MM_OAUTH_CLIENT_ID ) {
			return;
		}

		$this->save(
			array(
				'access_token'  => $result['access_token'],
				'refresh_token' => $result['refresh_token'],
				'token_expires' => ( $result['created_at'] + $result['expires_in'] ),
			)
		);

		// Clear any existing scheduled WordPress Cron event.
		wp_clear_scheduled_hook( 'convertkit_mm_refresh_token' );

		// Schedule a WordPress Cron event to refresh the token on expiry.
		wp_schedule_single_event( ( $result['created_at'] + $result['expires_in'] ), 'convertkit_mm_refresh_token' );

	}

	/**
	 * Deletes any existing access token, refresh token and its expiry from the Plugin settings.
	 *
	 * @since   1.3.0
	 */
	public function delete_credentials() {

		$this->save(
			array(
				'access_token'  => '',
				'refresh_token' => '',
				'token_expires' => '',
			)
		);

	}

	/**
	 * Returns the mapping setting for the given MemberMouse resource ID, type
	 * and whether the mapping is for the 'cancellation'.
	 *
	 * @since   1.2.2
	 *
	 * @param   int    $id                         Level, Product or Bundle ID.
	 * @param   string $type                       Mapping type (level,bundle,product).
	 * @param   bool   $is_cancellation_mapping    If the mapping setting is for the 'cancel' action.
	 * @return  string                              Setting
	 */
	private function get_mapping( $id, $type = 'level', $is_cancellation_mapping = false ) {

		// Build key we're looking for in the array of settings.
		// Membership levels are stored as `convertkit-mapping-ID`, so don't append the type in this instance.
		$key = 'convertkit-mapping-' . ( $type !== 'level' ? $type . '-' : '' ) . $id;

		// If requesting a 'cancel' setting, append this to the key.
		if ( $is_cancellation_mapping ) {
			$key .= '-cancel';
		}

		return $this->get_by_key( $key );

	}

	/**
	 * The default settings, used when the ConvertKit Plugin Settings haven't been saved
	 * e.g. on a new installation.
	 *
	 * @since   1.2.2
	 *
	 * @return  array
	 */
	public function get_defaults() {

		$defaults = array(
			// OAuth.
			'access_token'           => '', // string.
			'refresh_token'          => '', // string.
			'token_expires'          => '', // integer.

			// API Key. Retained if needed for backward compat.
			// Hyphen is deliberate; this is how the setting has always been stored.
			'api-key'                => '', // string.

			// Settings.
			'debug'                  => '', // blank|on.

			// Custom Fields.
			'custom_field_last_name' => '',
		);

		/**
		 * The default settings, used when the ConvertKit Plugin Settings haven't been saved
		 * e.g. on a new installation.
		 *
		 * @since   1.2.2
		 *
		 * @param   array   $defaults   Default Settings.
		 */
		$defaults = apply_filters( 'convertkit_settings_get_defaults', $defaults );

		return $defaults;

	}

	/**
	 * Saves the given array of settings to the WordPress options table.
	 *
	 * @since   1.2.2
	 *
	 * @param   array $settings   Settings.
	 */
	public function save( $settings ) {

		update_option( self::SETTINGS_NAME, array_merge( $this->get(), $settings ) );

		// Reload settings in class, to reflect changes.
		$this->settings = get_option( self::SETTINGS_NAME );

	}

}
