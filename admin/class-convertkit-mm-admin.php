<?php
/**
 * Admin class.
 *
 * @package ConvertKit_MM
 * @author ConvertKit
 */

/**
 * Registers the admin settings screen and saves settings.
 *
 * @package ConvertKit_MM
 * @author ConvertKit
 */
class ConvertKit_MM_Admin {

	/**
	 * Holds the Settings Page Slug
	 *
	 * @since   1.2.0
	 *
	 * @var     string
	 */
	const SETTINGS_PAGE_SLUG = 'convertkit-mm';

	/**
	 * Holds the ConvertKit Settings class.
	 *
	 * @since   1.2.2
	 *
	 * @var     null|ConvertKit_MM_Settings
	 */
	public $settings;

	/**
	 * Holds the API instance.
	 *
	 * @since   1.3.0
	 *
	 * @var     ConvertKit_MM_API
	 */
	private $api;

	/**
	 * Holds the ConvertKit Account Name.
	 *
	 * @since   1.3.0
	 *
	 * @var     bool|WP_Error|array
	 */
	private $account = false;

	/**
	 * Holds the Kit Custom Fields.
	 *
	 * @since   1.2.8
	 *
	 * @var     null|ConvertKit_MM_Resource_Custom_Fields
	 */
	public $custom_fields;

	/**
	 * Holds the ConvertKit Tags.
	 *
	 * @since   1.2.0
	 *
	 * @var     null|ConvertKit_MM_Resource_Tags
	 */
	public $tags;

	/**
	 * Initialize the class
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		// Register settings screen.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links_convertkit-membermouse/convertkit-membermouse.php', array( $this, 'settings_link' ) );

		// Initialize settings class.
		$this->settings = new ConvertKit_MM_Settings();

		// Run OAuth related actions.
		$this->maybe_get_and_store_access_token();
		$this->check_credentials();
		$this->maybe_disconnect();

	}

	/**
	 * Requests an access token via OAuth, if an authorization code and verifier are included in the request.
	 *
	 * @since   1.3.0
	 */
	private function maybe_get_and_store_access_token() {

		// Bail if we're not on the settings screen.
		if ( ! array_key_exists( 'page', $_REQUEST ) ) {  // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		if ( sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) !== 'convertkit-mm' ) {  // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		// Bail if no authorization code is included in the request.
		if ( ! array_key_exists( 'code', $_REQUEST ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		// Sanitize token.
		$authorization_code = sanitize_text_field( wp_unslash( $_REQUEST['code'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

		// Exchange the authorization code and verifier for an access token.
		$api    = new ConvertKit_MM_API( CONVERTKIT_MM_OAUTH_CLIENT_ID, CONVERTKIT_MM_OAUTH_CLIENT_REDIRECT_URI );
		$result = $api->get_access_token( $authorization_code );

		// Redirect with an error if we could not fetch the access token.
		if ( is_wp_error( $result ) ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'              => 'convertkit-mm',
						'error_description' => $result->get_error_message(),
					),
					'options-general.php'
				)
			);
			exit();
		}

		// Store Access Token, Refresh Token and expiry.
		$this->settings->save(
			array(
				'access_token'  => $result['access_token'],
				'refresh_token' => $result['refresh_token'],
				'token_expires' => ( $result['created_at'] + $result['expires_in'] ),
			)
		);

		// Redirect to settings screen, which will now show the Plugin's settings, because the Plugin
		// is now authenticated.
		wp_safe_redirect(
			add_query_arg(
				array(
					'page' => 'convertkit-mm',
				),
				'options-general.php'
			)
		);
		exit();

	}

	/**
	 * Test the access token, if it exists.
	 * If the access token has been revoked or is invalid, remove it from the settings now.
	 *
	 * @since   1.3.0
	 */
	private function check_credentials() {

		// Bail if we're not on the settings screen.
		if ( ! array_key_exists( 'page', $_REQUEST ) ) {  // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		if ( sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) !== 'convertkit-mm' ) {  // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		// Bail if no access and refresh token exist.
		if ( ! $this->settings->has_access_and_refresh_token() ) {
			return;
		}

		// Initialize the API.
		$this->api = new ConvertKit_MM_API(
			CONVERTKIT_MM_OAUTH_CLIENT_ID,
			CONVERTKIT_MM_OAUTH_CLIENT_REDIRECT_URI,
			$this->settings->get_access_token(),
			$this->settings->get_refresh_token(),
			$this->settings->debug_enabled(),
			'settings'
		);

		// Get Account Details, which we'll use in account_name_callback(), but also lets us test
		// whether the API credentials are valid.
		$this->account = $this->api->get_account();

		// If the request succeeded, no need to perform further actions.
		if ( ! is_wp_error( $this->account ) ) {
			return;
		}

		// Depending on the error code, maybe persist a notice in the WordPress Administration until the user
		// fixes the problem.
		switch ( $this->account->get_error_data( $this->account->get_error_code() ) ) {
			case 401:
				// Access token either expired or was revoked in ConvertKit.
				// Remove from settings.
				$this->settings->delete_credentials();

				// Redirect to General screen, which will now show the ConvertKit_Settings_OAuth screen, because
				// the Plugin has no access token.
				wp_safe_redirect(
					add_query_arg(
						array(
							'page' => 'convertkit-mm',
						),
						'options-general.php'
					)
				);
				exit();
		}

	}

	/**
	 * Deletes the OAuth Access Token, Refresh Token and Expiry from the Plugin's settings, if the user
	 * clicked the Disconnect button.
	 *
	 * @since   1.3.0
	 */
	private function maybe_disconnect() {

		// Bail if we're not on the settings screen.
		if ( ! array_key_exists( 'page', $_REQUEST ) ) {  // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		if ( sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) !== 'convertkit-mm' ) {  // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		// Bail if nonce verification fails.
		if ( ! isset( $_REQUEST['_convertkit_mm_settings_oauth_disconnect'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_key( $_REQUEST['_convertkit_mm_settings_oauth_disconnect'] ), 'convertkit-mm-oauth-disconnect' ) ) {
			return;
		}

		// Delete Access Token.
		$this->settings->delete_credentials();

		// Delete cached resources.
		$tags = new ConvertKit_MM_Resource_Tags();
		$tags->delete();

		// Redirect to General screen, which will now show the OAuth connect screen, because
		// the Plugin has no access token.
		wp_safe_redirect(
			add_query_arg(
				array(
					'page' => 'convertkit-mm',
				),
				'options-general.php'
			)
		);
		exit();

	}

	/**
	 * Enqueue CSS for the Settings Screens at Settings > ConvertKit
	 *
	 * @since   1.3.0
	 *
	 * @param   string $hook   Hook.
	 */
	public function enqueue_styles( $hook ) {

		// Bail if we are not on the Settings screen.
		if ( $hook !== 'settings_page_' . self::SETTINGS_PAGE_SLUG ) {
			return;
		}

		// Always enqueue Settings CSS, as this is used for the UI across all settings sections.
		wp_enqueue_style( 'convertkit-admin-settings', CONVERTKIT_MM_URL . 'resources/backend/css/settings.css', array(), CONVERTKIT_MM_VERSION );

	}

	/**
	 * Helper method to determine if we're viewing the current settings screen.
	 *
	 * @since   1.2.8
	 *
	 * @return  bool
	 */
	public function on_settings_screen() {

		// phpcs:disable WordPress.Security.NonceVerification

		// Bail if we're not on the settings screen.
		if ( ! array_key_exists( 'page', $_REQUEST ) ) {
			return false;
		}
		if ( sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) !== 'convertkit-mm' ) {
			return false;
		}
		// phpcs:enable

		return true;

	}

	/**
	 * Register settings sections and fields on the settings screen.
	 *
	 * @since       1.0.0
	 */
	public function register_settings() {

		// Register settings store.
		register_setting(
			CONVERTKIT_MM_NAME . '-options',
			CONVERTKIT_MM_NAME . '-options',
			array( $this, 'validate_options' )
		);

		// If no Access Token exists, register a settings section that shows a button
		// to start the OAuth authentication flow.
		if ( ! $this->settings->has_access_and_refresh_token() ) {
			add_settings_section(
				CONVERTKIT_MM_NAME . '-oauth',
				__( 'Connect to Kit', 'convertkit-mm' ),
				array( $this, 'display_section_introduction' ),
				CONVERTKIT_MM_NAME,
				array(
					'description' => esc_html__( 'For the Kit for MemberMouse Plugin to function, please connect your Kit account using the button below.', 'convertkit-mm' ),
				)
			);
			return;
		}

		// Initialize resource classes.
		$this->maybe_initialize_and_refresh_resources();

		// Register "General" settings section and fields.
		add_settings_section(
			CONVERTKIT_MM_NAME . '-display-options',
			__( 'General', 'convertkit-mm' ),
			array( $this, 'display_section_introduction' ),
			CONVERTKIT_MM_NAME
		);
		add_settings_field(
			'account_name',
			__( 'Account Name', 'convertkit-mm' ),
			array( $this, 'account_name_callback' ),
			CONVERTKIT_MM_NAME,
			CONVERTKIT_MM_NAME . '-display-options'
		);
		add_settings_field(
			'debug',
			__( 'Debug', 'convertkit-mm' ),
			array( $this, 'checkbox_field_callback' ),
			CONVERTKIT_MM_NAME,
			CONVERTKIT_MM_NAME . '-display-options',
			array(
				'name'        => 'debug',
				'value'       => '1',
				'checked'     => true,
				'label'       => __( 'Log requests to file and output browser console messages.', 'convertkit-mm' ),
				'label_for'   => 'debug',
				'description' => array(
					__( 'You can ignore this unless you\'re working with our support team to resolve an issue. Uncheck this option to improve performance.', 'convertkit-mm' ),
				),
			)
		);

		// Bail if no tags, as there are no further configuration settings without having ConvertKit Tags.
		if ( ! $this->tags->exist() ) {
			return;
		}

		// Register "Custom Fields" settings section and fields.
		$this->register_custom_fields();

		// Register "Tagging: Membership Levels" settings section and fields.
		$this->register_settings_membership_levels();

		// Register "Tagging: Products" settings section and fields.
		$this->register_settings_products();

		// Register "Tagging: Bundles" settings section and fields.
		$this->register_settings_bundles();

	}

	/**
	 * Initialize resource classes and perform a and refresh of resources,
	 * if initialization has not yet taken place.
	 *
	 * @since   1.2.8
	 */
	public function maybe_initialize_and_refresh_resources() {

		// Initialize classes.
		$this->tags          = new ConvertKit_MM_Resource_Tags( $this->api );
		$this->custom_fields = new ConvertKit_MM_Resource_Custom_Fields( $this->api );

		// Don't refresh resources if we're not on the settings screen, as
		// it's a resource intense process that can take several seconds.
		// We don't want to block other parts of the admin UI.
		if ( ! $this->on_settings_screen() ) {
			return;
		}

		// Refresh resources now.
		$this->tags->refresh();
		$this->custom_fields->refresh();

	}

	/**
	 * Registers the settings section and fields for Membership Levels.
	 *
	 * @since   1.2.8
	 */
	private function register_custom_fields() {

		add_settings_section(
			CONVERTKIT_MM_NAME . '-custom-fields',
			__( 'Custom Fields', 'convertkit-mm' ),
			array( $this, 'display_section_introduction' ),
			CONVERTKIT_MM_NAME,
			array(
				'before_section' => '<div class="section">',
				'after_section'  => '</div>',
				'description'    => __( 'The Kit custom fields to store MemberMouse data.', 'convertkit-mm' ),
			)
		);

		add_settings_field(
			'custom_field_last_name',
			__( 'Send Last Name', 'convertkit-mm' ),
			array( $this, 'custom_field_callback' ),
			CONVERTKIT_MM_NAME,
			CONVERTKIT_MM_NAME . '-custom-fields',
			array(
				'key'     => 'last_name',
				'name'    => 'custom_field_last_name',
				'value'   => $this->settings->get_by_key( 'custom_field_last_name' ),
				'options' => $this->custom_fields->get(),
			)
		);

	}

	/**
	 * Registers the settings section and fields for Membership Levels.
	 *
	 * @since   1.3.0
	 */
	private function register_settings_membership_levels() {

		// Get membership levels.
		$levels = $this->get_mm_membership_levels();

		// Define description for the settings section, depending on whether any bundles exist.
		if ( count( $levels ) ) {
			$description = esc_html__( 'For each MemberMouse membership level, assign a Kit Tag that you wish to be assigned to members of that level.', 'convertkit-mm' );
		} else {
			$description = esc_html__( 'No membership levels exist in MemberMouse. Add a membership level first, and then reload this settings screen to assign tags to members by membership level.', 'convertkit-mm' );
		}

		// Register settings section.
		add_settings_section(
			CONVERTKIT_MM_NAME . '-ck-mapping-membership-levels',
			__( 'Tagging: Membership Levels', 'convertkit-mm' ),
			array( $this, 'display_section_introduction' ),
			CONVERTKIT_MM_NAME,
			array(
				'before_section' => '<div class="section">',
				'after_section'  => '</div>',
				'description'    => $description,
			)
		);

		// If no levels exist, don't display any settings fields.
		if ( ! $levels ) {
			return;
		}

		// Output settings fields for each level.
		foreach ( $levels as $key => $name ) {
			add_settings_field(
				'convertkit-mapping-' . $key,
				$name,
				array( $this, 'mapping_fields_callback' ),
				CONVERTKIT_MM_NAME,
				CONVERTKIT_MM_NAME . '-ck-mapping-membership-levels',
				array(
					'key'          => $key,
					'type'         => __( 'Level', 'convertkit-mm' ),

					'name'         => 'convertkit-mapping-' . $key,
					'value'        => $this->settings->get_membership_level_mapping( $key ),

					'name_cancel'  => 'convertkit-mapping-' . $key . '-cancel',
					'value_cancel' => $this->settings->get_membership_level_cancellation_mapping( $key ),

					'options'      => $this->tags->get(),
				)
			);
		}

	}

	/**
	 * Registers the settings section and fields for Products.
	 *
	 * @since   1.3.0
	 */
	private function register_settings_products() {

		// Get products.
		$products = $this->get_mm_products();

		// Define description for the settings section, depending on whether any products exist.
		if ( count( $products ) ) {
			$description = esc_html__( 'For each MemberMouse product, assign a Kit Tag that you wish to be assigned to members of that product.', 'convertkit-mm' );
		} else {
			$description = esc_html__( 'No products exist in MemberMouse. Add a product first, and then reload this settings screen to assign tags to members of products.', 'convertkit-mm' );
		}

		add_settings_section(
			CONVERTKIT_MM_NAME . '-ck-mapping-products',
			__( 'Tagging: Products', 'convertkit-mm' ),
			array( $this, 'display_section_introduction' ),
			CONVERTKIT_MM_NAME,
			array(
				'before_section' => '<div class="section">',
				'after_section'  => '</div>',
				'description'    => $description,
			)
		);

		// If no products exist, don't display any settings fields.
		if ( ! $products ) {
			return;
		}

		// Output settings fields for each product.
		foreach ( $products as $key => $name ) {
			add_settings_field(
				'convertkit-mapping-product-' . $key,
				$name,
				array( $this, 'mapping_fields_callback' ),
				CONVERTKIT_MM_NAME,
				CONVERTKIT_MM_NAME . '-ck-mapping-products',
				array(
					'key'     => $key,
					'type'    => __( 'Product', 'convertkit-mm' ),

					'name'    => 'convertkit-mapping-product-' . $key,
					'value'   => $this->settings->get_product_mapping( $key ),

					'options' => $this->tags->get(),
				)
			);
		}

	}

	/**
	 * Registers the settings section and fields for Bundles.
	 *
	 * @since   1.3.0
	 */
	private function register_settings_bundles() {

		// Get bundles.
		$bundles = $this->get_mm_bundles();

		// Define description for the settings section, depending on whether any bundles exist.
		if ( count( $bundles ) ) {
			$description = esc_html__( 'For each MemberMouse bundle, assign a Kit Tag that you wish to be assigned to members of that bundle.', 'convertkit-mm' );
		} else {
			$description = esc_html__( 'No bundles exist in MemberMouse. Add a bundle first, and then reload this settings screen to assign tags to members of bundles.', 'convertkit-mm' );
		}

		// Register settings section.
		add_settings_section(
			CONVERTKIT_MM_NAME . '-ck-mapping-bundles',
			__( 'Tagging: Bundles', 'convertkit-mm' ),
			array( $this, 'display_section_introduction' ),
			CONVERTKIT_MM_NAME,
			array(
				'before_section' => '<div class="section">',
				'after_section'  => '</div>',
				'description'    => $description,
			)
		);

		// If no bundles exist, don't display any settings fields.
		if ( ! $bundles ) {
			return;
		}

		// Output settings fields for each bundle.
		foreach ( $bundles as $key => $name ) {
			add_settings_field(
				'convertkit-mapping-bundle-' . $key,
				$name,
				array( $this, 'mapping_fields_callback' ),
				CONVERTKIT_MM_NAME,
				CONVERTKIT_MM_NAME . '-ck-mapping-bundles',
				array(
					'key'          => $key,
					'type'         => __( 'Bundle', 'convertkit-mm' ),

					'name'         => 'convertkit-mapping-bundle-' . $key,
					'value'        => $this->settings->get_bundle_mapping( $key ),

					'name_cancel'  => 'convertkit-mapping-bundle-' . $key . '-cancel',
					'value_cancel' => $this->settings->get_bundle_cancellation_mapping( $key ),

					'options'      => $this->tags->get(),
				)
			);
		}

	}

	/**
	 * Adds a settings page link to a menu
	 *
	 * @since       1.0.0
	 * @return      void
	 */
	public function add_settings_page() {

		add_options_page(
			apply_filters( CONVERTKIT_MM_NAME . '_settings_page_title', esc_html__( 'Kit MemberMouse Settings', 'convertkit-mm' ) ),
			apply_filters( CONVERTKIT_MM_NAME . '_settings_menu_title', esc_html__( 'Kit MemberMouse', 'convertkit-mm' ) ),
			'manage_options',
			CONVERTKIT_MM_NAME,
			array( $this, 'display_settings_page' )
		);

	}

	/**
	 * Creates the options page
	 *
	 * @since       1.0.0
	 * @return      void
	 */
	public function display_settings_page() {

		?>
		<header>
			<h1><?php esc_html_e( 'Kit for MemberMouse', 'convertkit-mm' ); ?></h1>

			<?php
			// Output Help link.
			$documentation_url = 'https://help.kit.com/en/articles/2502605-membermouse-integration';
			printf(
				'<a href="%s" class="convertkit-docs" target="_blank">%s</a>',
				esc_attr( $documentation_url ),
				esc_html__( 'Help', 'convertkit-mm' )
			);
			?>
		</header>

		<div class="wrap">
			<form method="post" action="options.php" enctype="multipart/form-data">
				<div class="metabox-holder">
					<div class="postbox">
						<?php
						do_settings_sections( CONVERTKIT_MM_NAME );
						settings_fields( 'convertkit-mm-options' );

						// If no access token, show connect button.
						if ( ! $this->settings->has_access_and_refresh_token() ) {
							// Determine the OAuth URL to begin the authorization process.
							$api       = new ConvertKit_MM_API( CONVERTKIT_MM_OAUTH_CLIENT_ID, CONVERTKIT_MM_OAUTH_CLIENT_REDIRECT_URI );
							$oauth_url = $api->get_oauth_url( admin_url( 'options-general.php?page=convertkit-mm' ), get_site_url() );
							?>
							<p>
								<a href="<?php echo esc_url( $oauth_url ); ?>" class="button button-primary"><?php esc_html_e( 'Connect', 'convertkit-mm' ); ?></a>
							</p>
							<?php
						} else {
							submit_button( __( 'Save Settings', 'convertkit-mm' ) );
						}
						?>
						</div>
				</div>
			</form>

			<p class="description">
				<?php
				// Output Help link.
				printf(
					'%s <a href="%s" target="_blank">%s</a>',
					esc_html__( 'If you need help setting up the plugin please refer to the', 'convertkit-mm' ),
					esc_attr( $documentation_url ),
					esc_html__( 'plugin documentation', 'convertkit-mm' )
				);
				?>
			</p>
		</div>
		<?php

	}

	/**
	 * Outputs a description at the start of a settings section, before its fields.
	 *
	 * @since       1.3.0
	 *
	 * @param   array $args   Section arguments.
	 */
	public function display_section_introduction( $args ) {

		// If no description provided, don't output a blank paragraph tag.
		if ( ! array_key_exists( 'description', $args ) ) {
			return;
		}
		if ( empty( $args['description'] ) ) {
			return;
		}

		echo '<p>' . esc_html( $args['description'] ) . '</p>';

	}

	/**
	 * Outputs the Account Name
	 *
	 * @since   1.3.0
	 */
	public function account_name_callback() {

		// Output Account Name.
		$html = sprintf(
			'<p>%s</p>',
			isset( $this->account['account']['name'] ) ? esc_attr( $this->account['account']['name'] ) : esc_html__( '(Not specified)', 'convertkit-mm' )
		);

		// Display an option to disconnect.
		$html .= sprintf(
			'<p><a href="%1$s" class="button button-secondary">%2$s</a></p>',
			esc_url(
				add_query_arg(
					array(
						'page' => 'convertkit-mm',
						'_convertkit_mm_settings_oauth_disconnect' => wp_create_nonce( 'convertkit-mm-oauth-disconnect' ),
					),
					'options-general.php'
				)
			),
			esc_html__( 'Disconnect', 'convertkit-mm' )
		);

		// Output has already been run through escaping functions above.
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/**
	 * Renders a text input field.
	 *
	 * @since   1.3.0
	 *
	 * @param   array $args   Setting field arguments.
	 */
	public function text_field_callback( $args ) {

		// Output field.
		$html = sprintf(
			'<input type="text" class="%s" id="%s" name="%s[%s]" value="%s" />',
			( is_array( $args['css_classes'] ) ? implode( ' ', $args['css_classes'] ) : 'regular-text' ),
			$args['name'],
			$this->settings::SETTINGS_NAME,
			$args['name'],
			$this->settings->get_by_key( $args['name'] )
		);

		// Output field with description appended to it.
		echo $html . $this->get_description( $args['description'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Renders a checkbox field.
	 *
	 * @since   1.3.0
	 *
	 * @param   array $args   Setting field arguments.
	 */
	public function checkbox_field_callback( $args ) {

		$html = '';

		if ( $args['label'] ) {
			$html .= sprintf(
				'<label for="%s">',
				$args['name']
			);
		}

		$html .= sprintf(
			'<input type="checkbox" id="%s" name="%s[%s]" class="%s" value="%s" %s />',
			$args['name'],
			$this->settings::SETTINGS_NAME,
			$args['name'],
			( array_key_exists( 'css_classes', $args ) && is_array( $args['css_classes'] ) ? implode( ' ', $args['css_classes'] ) : '' ),
			$args['value'],
			( $args['checked'] ? ' checked' : '' )
		);

		if ( $args['label'] ) {
			$html .= sprintf(
				'%s</label>',
				$args['label']
			);
		}

		// Output field with description appended to it.
		echo $html . $this->get_description( $args['description'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Renders the fields for the tag mapping section in the settings screen
	 * (membership levels, products and bundles).
	 *
	 * @since   1.3.0
	 *
	 * @param   array $args   Setting field arguments.
	 */
	public function mapping_fields_callback( $args ) {

		$html = $this->get_select_field(
			$args['name'],
			$args['value'],
			$args['options'],
			__( 'Apply tag on add', 'convertkit-mm' )
		);

		// If a cancel option is not specified, return the single field now.
		if ( ! array_key_exists( 'name_cancel', $args ) ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}

		$html .= $this->get_select_field(
			$args['name_cancel'],
			$args['value_cancel'],
			$args['options'],
			__( 'Apply / remove tag on cancel', 'convertkit-mm' ),
			true
		);

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Renders a custom field setting in the settings screen.
	 *
	 * @since   1.2.8
	 *
	 * @param   array $args   Setting field arguments.
	 */
	public function custom_field_callback( $args ) {

		// Re-key options to be compatible with get_select_field().
		$options = array();
		foreach ( $args['options'] as $custom_field_id => $custom_field ) {
			$options[ $custom_field['key'] ] = array(
				'id'   => $custom_field['key'],
				'name' => $custom_field['label'],
			);
		}

		$html = $this->get_select_field(
			$args['name'],
			$args['value'],
			$options
		);

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Returns a select dropdown field.
	 *
	 * @since   1.3.0
	 *
	 * @param   string      $name                   Name.
	 * @param   string      $value                  Value.
	 * @param   array       $options                Options / Choices.
	 * @param   bool|string $label                  Label.
	 * @param   bool        $show_remove_options    Show 'Remove Tag' options.
	 * @return  string                              HTML Select Field
	 */
	private function get_select_field( $name, $value = '', $options = array(), $label = false, $show_remove_options = false ) {

		$html = '';

		if ( $label ) {
			$html .= sprintf(
				'<div><label for="%s">%s</label>',
				$name,
				$label
			);
		}

		// Build opening <select> tag.
		$html .= sprintf(
			'<select id="%s" name="%s[%s]" size="1">',
			$name,
			$this->settings::SETTINGS_NAME,
			$name
		);

		// Add 'None' option.
		$html .= sprintf(
			'<option value=""%s>%s</option>',
			selected( $value, '', false ),
			esc_attr__( '(None)', 'convertkit-mm' )
		);

		// Tags: Assign.
		$html .= sprintf(
			'<optgroup label="%s">',
			__( 'Assign Tag', 'convertkit-mm' )
		);
		foreach ( $options as $option ) {
			$html .= sprintf(
				'<option value="%s"%s>%s</option>',
				$option['id'],
				selected( $value, $option['id'], false ),
				$option['name']
			);
		}
		$html .= '</optgroup>';

		// Tags: Remove.
		if ( $show_remove_options ) {
			$html .= sprintf(
				'<optgroup label="%s">',
				__( 'Remove Tag', 'convertkit-mm' )
			);
			foreach ( $options as $option ) {
				$html .= sprintf(
					'<option value="%s-remove"%s>%s (%s)</option>',
					$option['id'],
					selected( $value, $option['id'] . '-remove', false ),
					$option['name'],
					__( 'Remove', 'convertkit-mm' )
				);
			}
			$html .= '</optgroup>';
		}

		// Close <select>.
		$html .= '</select>';

		if ( $label ) {
			$html .= '</div>';
		}

		return $html;

	}

	/**
	 * Returns the given text wrapped in a paragraph with the description class,
	 * if a description is specified.
	 *
	 * @since   1.3.0
	 *
	 * @param   string|array $description    Description.
	 * @return  string                       HTML Description
	 */
	private function get_description( $description = '' ) {

		// Return blank string if no description specified.
		if ( empty( $description ) ) {
			return '';
		}

		// Return description in paragraph if a string.
		if ( ! is_array( $description ) ) {
			return '<p class="description">' . $description . '</p>';
		}

		// Return description lines in a paragraph, using breaklines for each description entry in the array.
		return '<p class="description">' . implode( '<br />', $description ) . '</p>';

	}

	/**
	 * Validates saved options
	 *
	 * @since       1.0.0
	 *
	 * @param       array $input          Submitted plugin options.
	 * @return      array                 Validated plugin options
	 */
	public function validate_options( $input ) {

		// If no Access Token, Refresh Token or Token Expiry keys were specified in the settings
		// prior to save, don't overwrite them with the blank setting from get_defaults().
		// This ensures we only blank these values if we explicitly do so via $settings,
		// as they won't be included in the Settings screen for security.
		if ( ! array_key_exists( 'disconnect', $_REQUEST ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			if ( ! array_key_exists( 'access_token', $input ) ) {
				$input['access_token'] = $this->settings->get_access_token();
			}
			if ( ! array_key_exists( 'refresh_token', $input ) ) {
				$input['refresh_token'] = $this->settings->get_refresh_token();
			}
			if ( ! array_key_exists( 'token_expires', $input ) ) {
				$input['token_expires'] = $this->settings->get_token_expiry();
			}
		}

		return $input;

	}

	/**
	 * Adds a link to the plugin settings page
	 *
	 * @since       1.0.0
	 *
	 * @param       array $links    Settings links.
	 * @return      array           Settings links
	 */
	public function settings_link( $links ) {

		$settings_link = sprintf( '<a href="%s">%s</a>', admin_url( 'options-general.php?page=' . CONVERTKIT_MM_NAME ), esc_html__( 'Settings', 'convertkit-mm' ) );
		array_unshift( $links, $settings_link );
		return $links;

	}

	/**
	 * Get all MemberMouse membership levels
	 *
	 * @since 1.0.0
	 * @return array
	 */
	private function get_mm_membership_levels() {

		global $wpdb;

		$levels = array();
		if ( ! defined( 'MM_TABLE_MEMBERSHIP_LEVELS' ) ) {
			return $levels;
		}

		$result = $wpdb->get_results( 'SELECT id, name, status FROM ' . MM_TABLE_MEMBERSHIP_LEVELS, OBJECT ); // phpcs:ignore WordPress.DB

		foreach ( $result as $_level ) {
			$levels[ $_level->id ] = $_level->name;
		}

		return $levels;

	}

	/**
	 * Get all MemberMouse products
	 *
	 * @since   1.2.0
	 *
	 * @return  array
	 */
	private function get_mm_products() {

		global $wpdb;

		$products = array();
		if ( ! defined( 'MM_TABLE_PRODUCTS' ) ) {
			return $products;
		}

		$result = $wpdb->get_results( 'SELECT id, name FROM ' . MM_TABLE_PRODUCTS, OBJECT ); // phpcs:ignore WordPress.DB

		foreach ( $result as $product ) {
			$products[ $product->id ] = $product->name;
		}

		return $products;

	}

	/**
	 * Get all MemberMouse bundles
	 *
	 * @since   1.2.0
	 *
	 * @return  array
	 */
	private function get_mm_bundles() {

		global $wpdb;

		$bundles = array();
		if ( ! defined( 'MM_TABLE_BUNDLES' ) ) {
			return $bundles;
		}

		$result = $wpdb->get_results( 'SELECT id, name FROM ' . MM_TABLE_BUNDLES, OBJECT ); // phpcs:ignore WordPress.DB

		foreach ( $result as $bundle ) {
			$bundles[ $bundle->id ] = $bundle->name;
		}

		return $bundles;

	}

}
