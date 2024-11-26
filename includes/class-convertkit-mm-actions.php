<?php
/**
 * Actions class.
 *
 * @package ConvertKit_MM
 * @author ConvertKit
 */

/**
 * Actions class.
 *
 * @package ConvertKit_MM
 * @author ConvertKit
 */
class ConvertKit_MM_Actions {

	/**
	 * Holds the ConvertKit Settings class.
	 *
	 * @since   1.2.2
	 *
	 * @var     null|ConvertKit_MM_Settings
	 */
	public $settings;

	/**
	 * Constructor. Registers hooks when specific MemberMouse actions are performed,
	 * such as adding a member, purchasing a product and being assigned to a bundle.
	 *
	 * @since   1.2.0
	 */
	public function __construct() {

		// Initialize settings class.
		$this->settings = new ConvertKit_MM_Settings();

		// Bail if no access and refresh token exist.
		if ( ! $this->settings->has_access_and_refresh_token() ) {
			return;
		}

		// Tag on Membership Level.
		add_action( 'mm_member_add', array( $this, 'add_member' ) );
		add_action( 'mm_member_membership_change', array( $this, 'add_member' ) );
		add_action( 'mm_member_status_change', array( $this, 'status_change_member' ) );
		add_action( 'mm_member_delete', array( $this, 'delete_member' ) );

		// Tag on Product.
		add_action( 'mm_product_purchase', array( $this, 'purchase_product' ) );

		// Tag on Bundle.
		add_action( 'mm_bundles_add', array( $this, 'add_bundle' ) );
		add_action( 'mm_bundles_status_change', array( $this, 'status_change_bundle' ) );

		// Membership Account changed.
		add_action( 'mm_member_account_update', array( $this, 'update_member' ) );

	}

	/**
	 * A member was added to MemberMouse
	 *
	 * Member Data is sent to this hook including the new `membership_level`
	 * If membership level is > 0 then the user is being added to level with that ID.
	 * For info what is contained in the member_data see link.
	 *
	 * @see https://membermouse.uservoice.com/knowledgebase/articles/319072-membermouse-wordpress-hooks#member-data
	 *
	 * @since   1.0.0
	 *
	 * @param   array $member_data    Member data.
	 */
	public function add_member( $member_data ) {

		// Bail if no membership level data exists.
		if ( ! isset( $member_data['membership_level'] ) ) {
			return;
		}

		// Fetch data from member array.
		$user_email = $member_data['email'];
		$first_name = rawurlencode( $member_data['first_name'] );
		$tag_id     = $this->settings->get_membership_level_mapping( $member_data['membership_level'] );

		// Bail if no tag mapping exists, as this means we don't need to tag the subscriber.
		if ( empty( $tag_id ) ) {
			return;
		}

		// Subscribe and tag.
		$this->add_tag_to_user( $user_email, $first_name, $tag_id );
		convertkit_mm_log( 'tag', 'Add tag ' . $tag_id . ' to user ' . $user_email . ' (' . $first_name . ')' );

	}

	/**
	 * Called when a member's account is updated in MemberMouse.
	 *
	 * @since   1.2.2
	 *
	 * @param   array $member_data    Member data.
	 */
	public function update_member( $member_data ) {

		// Bail if no change of first name or email address occured.
		if ( ! array_key_exists( 'last_email', $member_data ) && ! array_key_exists( 'last_first_name', $member_data ) ) {
			return;
		}

		// Update the subscriber with their new first name and/or email address.
		$this->update_subscriber( $member_data['email'], $member_data['first_name'], $member_data['last_email'] );

	}

	/**
	 * Called when a member's status is changed in MemberMouse.
	 *
	 * @since   1.0.0
	 *
	 * @param   array $member_data    Member data.
	 */
	public function status_change_member( $member_data ) {

		// Bail if no membership level data exists.
		if ( ! isset( $member_data['membership_level'] ) ) {
			return;
		}

		// Bail if the status isn't set to cancelled.
		if ( ! isset( $member_data['status_name'] ) ) {
			return;
		}
		if ( $member_data['status_name'] !== 'Canceled' ) {
			return;
		}

		// Fetch data from member array.
		$user_email = $member_data['email'];
		$first_name = rawurlencode( $member_data['first_name'] );
		$tag_id     = $this->settings->get_membership_level_cancellation_mapping( $member_data['membership_level'] );

		// Bail if no tag mapping exists, as this means we don't need to tag the subscriber.
		if ( empty( $tag_id ) ) {
			return;
		}

		// If the Tag contains `-remove`, we need to remove the Tag from the subscriber.
		if ( strpos( $tag_id, '-remove' ) !== false ) {
			$this->remove_tag_from_user( $user_email, $tag_id );
			convertkit_mm_log( 'tag', 'Remove tag ' . $tag_id . ' from user ' . $user_email . ' (' . $first_name . ')' );
			return;
		}

		// Subscribe and tag.
		$this->add_tag_to_user( $user_email, $first_name, $tag_id );
		convertkit_mm_log( 'tag', 'Add tag ' . $tag_id . ' to user ' . $user_email . ' (' . $first_name . ')' );

	}

	/**
	 * Called when a member is deleted in MemberMouse.
	 *
	 * @since   1.0.0
	 *
	 * @param   array $member_data    Member data.
	 */
	public function delete_member( $member_data ) {

		// Bail if no membership level data exists.
		if ( ! isset( $member_data['membership_level'] ) ) {
			return;
		}

		// Fetch data from member array.
		$user_email = $member_data['email'];
		$first_name = rawurlencode( $member_data['first_name'] );
		$tag_id     = $this->settings->get_membership_level_cancellation_mapping( $member_data['membership_level'] );

		// Bail if no tag mapping exists, as this means we don't need to tag the subscriber.
		if ( empty( $tag_id ) ) {
			return;
		}

		// If the Tag contains `-remove`, we need to remove the Tag from the subscriber.
		if ( strpos( $tag_id, '-remove' ) !== false ) {
			$this->remove_tag_from_user( $user_email, $tag_id );
			convertkit_mm_log( 'tag', 'Remove tag ' . $tag_id . ' from user ' . $user_email . ' (' . $first_name . ')' );
			return;
		}

		// Subscribe and tag.
		$this->add_tag_to_user( $user_email, $first_name, $tag_id );
		convertkit_mm_log( 'tag', 'Add tag ' . $tag_id . ' to user ' . $user_email . ' (' . $user_name . ')' );

	}

	/**
	 * Assign a tag to the subscriber when purchasing a MemberMouse Product
	 * that is configured to tag a subscriber.
	 *
	 * @since   1.2.0
	 *
	 * @param   array $purchase_data  Checkout purchase data.
	 */
	public function purchase_product( $purchase_data ) {

		// Fetch data from purchase array.
		$user_email = $purchase_data['email'];
		$first_name = rawurlencode( $purchase_data['first_name'] );
		$tag_id     = $this->settings->get_product_mapping( $purchase_data['product_id'] );

		// If no tag assigned to this Product, bail.
		if ( empty( $tag_id ) ) {
			return;
		}

		// Assign tag to subscriber in ConvertKit.
		$this->add_tag_to_user( $user_email, $first_name, $tag_id );
		convertkit_mm_log( 'tag', 'Add product tag ' . $tag_id . ' to user ' . $user_email . ' (' . $first_name . ')' );

	}

	/**
	 * Assign a tag to the subscriber when purchasing a MemberMouse Product
	 * that is assigned to a bundle, and the bundle is configured to tag a subscriber.
	 *
	 * @since   1.2.0
	 *
	 * @param   array $purchase_data  Checkout purchase data.
	 */
	public function add_bundle( $purchase_data ) {

		// Fetch data from purchase array.
		$user_email = $purchase_data['email'];
		$first_name = rawurlencode( $purchase_data['first_name'] );
		$tag_id     = $this->settings->get_bundle_mapping( $purchase_data['bundle_id'] );

		// If no tag assigned to this Bundle, bail.
		if ( empty( $tag_id ) ) {
			return;
		}

		// Assign tag to subscriber in ConvertKit.
		$this->add_tag_to_user( $user_email, $first_name, $tag_id );
		convertkit_mm_log( 'tag', 'Add bundle tag ' . $tag_id . ' to user ' . $user_email . ' (' . $first_name . ')' );

	}

	/**
	 * Assign a tag to the subscriber when a member's bundle status is changed in MemberMouse.
	 *
	 * @since   1.2.0
	 *
	 * @param   array $member_data    Member data.
	 */
	public function status_change_bundle( $member_data ) {

		// Determine the status change.
		switch ( $member_data['bundle_status_name'] ) {
			case 'Active':
				$tag_id = $this->settings->get_bundle_mapping( $member_data['bundle_id'] );
				break;
			case 'Canceled':
				$tag_id = $this->settings->get_bundle_cancellation_mapping( $member_data['bundle_id'] );
				break;

			default:
				// Unsupported status at this time.
				return;
		}

		// Fetch data from member array.
		$user_email = $member_data['email'];
		$first_name = rawurlencode( $member_data['first_name'] );

		// If no tag assigned to this Bundle, bail.
		if ( empty( $tag_id ) ) {
			return;
		}

		// If the Tag contains `-remove`, we need to remove the Tag from the subscriber.
		if ( strpos( $tag_id, '-remove' ) !== false ) {
			$this->remove_tag_from_user( $user_email, $tag_id );
			convertkit_mm_log( 'tag', 'Remove bundle tag ' . $tag_id . ' from user ' . $user_email . ' (' . $first_name . ')' );
			return;
		}

		// Assign tag to subscriber in ConvertKit.
		$this->add_tag_to_user( $user_email, $first_name, $tag_id );
		convertkit_mm_log( 'tag', 'Add bundle tag ' . $tag_id . ' to user ' . $user_email . ' (' . $first_name . ')' );

	}

	/**
	 * Initializes the API, subscribing the given email address and assigning the
	 * subscriber to the ConvertKit Tag ID.
	 *
	 * @since   1.2.0
	 *
	 * @param   string $email          Email Address.
	 * @param   string $first_name     First Name.
	 * @param   int    $tag_id         Tag ID.
	 */
	private function add_tag_to_user( $email, $first_name, $tag_id ) {

		// Initialize the API.
		$api = new ConvertKit_MM_API(
			CONVERTKIT_MM_OAUTH_CLIENT_ID,
			CONVERTKIT_MM_OAUTH_CLIENT_REDIRECT_URI,
			$this->settings->get_access_token(),
			$this->settings->get_refresh_token(),
			$this->settings->debug_enabled(),
			'settings'
		);

		// Subscribe email.
		// Subscribe the email address.
		$subscriber = $api->create_subscriber( $email, $first_name );
		if ( is_wp_error( $subscriber ) ) {
			return;
		}

		// Tag subscriber.
		$api->tag_subscriber( absint( $tag_id ), $subscriber['subscriber']['id'] );

	}

	/**
	 * Initializes the API, removing the Tag ID from the subscriber on Kit.
	 *
	 * @since   1.2.8
	 *
	 * @param   string $email          Email Address.
	 * @param   int    $tag_id         Tag ID.
	 */
	private function remove_tag_from_user( $email, $tag_id ) {

		// Initialize the API.
		$api = new ConvertKit_MM_API(
			CONVERTKIT_MM_OAUTH_CLIENT_ID,
			CONVERTKIT_MM_OAUTH_CLIENT_REDIRECT_URI,
			$this->settings->get_access_token(),
			$this->settings->get_refresh_token(),
			$this->settings->debug_enabled(),
			'settings'
		);

		// Remove tag from subscriber.
		$api->remove_tag_from_subscriber_by_email( absint( $tag_id ), $email );

	}

	/**
	 * Updates the subscriber in ConvertKit with their new email address.
	 *
	 * @since   1.2.2
	 *
	 * @param   string $email          Email Address.
	 * @param   string $first_name     First Name.
	 * @param   string $last_email     Old (last) email address.
	 */
	private function update_subscriber( $email, $first_name, $last_email ) {

		// Initialize the API.
		$api = new ConvertKit_MM_API(
			CONVERTKIT_MM_OAUTH_CLIENT_ID,
			CONVERTKIT_MM_OAUTH_CLIENT_REDIRECT_URI,
			$this->settings->get_access_token(),
			$this->settings->get_refresh_token(),
			$this->settings->debug_enabled(),
			'settings'
		);

		// Get subscriber ID using the last email address.
		$subscriber_id = $api->get_subscriber_id( $last_email );

		// If no subscriber could be found, bail.
		if ( ! $subscriber_id ) {
			return;
		}

		// Update subscriber.
		$api->update_subscriber( $subscriber_id, $first_name, $email );

	}

}
