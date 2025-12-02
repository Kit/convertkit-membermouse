<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests that subscribers are added to ConvertKit and tagged
 * based on the membership level.
 *
 * @since   1.2.0
 */
class MemberTagCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function _before(EndToEndTester $I)
	{
		// Activate Plugins.
		$I->activateConvertKitPlugin($I);
		$I->activateThirdPartyPlugin($I, 'membermouse-platform');
	}

	/**
	 * Test that the member is tagged with the configured "apply tag on add"
	 * setting when added to a Membership Level.
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testMemberTaggedWhenMembershipLevelAdded(EndToEndTester $I)
	{
		// Setup Plugin to tag users added to the Free Membership level to the
		// ConvertKit Tag ID.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-1' => $_ENV['CONVERTKIT_API_TAG_ID'],
			]
		);

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Create member.
		$I->memberMouseCreateMember($I, $emailAddress);

		// Check subscriber exists.
		$subscriber = $I->apiCheckSubscriberExists($I, $emailAddress);

		// Check that the subscriber has been assigned to the tag.
		$I->apiCheckSubscriberHasTag(
			$I,
			subscriberID: $subscriber['id'],
			tagID: $_ENV['CONVERTKIT_API_TAG_ID']
		);
	}

	/**
	 * Test that the member is tagged with the configured "apply tag"
	 * setting when the Membership Level is changed.
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testMemberTaggedWhenMembershipLevelChanged(EndToEndTester $I)
	{
		// Create an additional membership level.
		$levelID = $I->memberMouseCreateMembershipLevel($I, 'Premium');

		// Setup Plugin to tag users added to the Free Membership level to the
		// ConvertKit Tag ID.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-' . $levelID => $_ENV['CONVERTKIT_API_TAG_ID'],
			]
		);

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Create member.
		$I->memberMouseCreateMember($I, $emailAddress);

		// Change the user's membership level.
		$I->amOnAdminPage('admin.php?page=manage_members');
		$I->click($emailAddress);
		$I->click('Access Rights');
		$I->selectOption('#mm-new-membership-selection', 'Premium');
		$I->click('Change Membership');

		// Accept popups.
		$I->memberMouseAcceptPopups($I, 2);

		// Check subscriber exists.
		$subscriber = $I->apiCheckSubscriberExists($I, $emailAddress);

		// Check that the subscriber has been assigned to the tag.
		$I->apiCheckSubscriberHasTag(
			$I,
			subscriberID: $subscriber['id'],
			tagID: $_ENV['CONVERTKIT_API_TAG_ID']
		);
	}

	/**
	 * Test that the member is tagged with the configured "apply tag on cancelled"
	 * setting when cancelled.
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testMemberTaggedWhenMembershipLevelCancelled(EndToEndTester $I)
	{
		// Setup Plugin to tag users added to the Free Membership level to the
		// ConvertKit Tag ID, and assign them a different tag when their membership
		// is cancelled.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-1'        => $_ENV['CONVERTKIT_API_TAG_ID'],
				'convertkit-mapping-1-cancel' => $_ENV['CONVERTKIT_API_TAG_CANCEL_ID'],
			]
		);

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Create member.
		$I->memberMouseCreateMember($I, $emailAddress);

		// Check subscriber exists.
		$subscriber = $I->apiCheckSubscriberExists($I, $emailAddress);

		// Check that the subscriber has been assigned to the tag.
		$I->apiCheckSubscriberHasTag(
			$I,
			subscriberID: $subscriber['id'],
			tagID: $_ENV['CONVERTKIT_API_TAG_ID']
		);

		// Cancel the user's membership level.
		$I->amOnAdminPage('admin.php?page=manage_members');
		$I->click($emailAddress);
		$I->click('Access Rights');
		$I->click('Cancel Membership');

		// Accept popups.
		$I->memberMouseAcceptPopups($I, 2);

		// Check that the subscriber has been assigned to the cancelled tag.
		$I->apiCheckSubscriberHasTag(
			$I,
			subscriberID: $subscriber['id'],
			tagID: $_ENV['CONVERTKIT_API_TAG_CANCEL_ID']
		);
	}

	/**
	 * Test that the member has a tag removed when their membership level is cancelled.
	 *
	 * @since   1.2.8
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testMemberTagRemovedWhenMembershipLevelCancelled(EndToEndTester $I)
	{
		// Setup Plugin to tag users added to the Free Membership level to the
		// ConvertKit Tag ID, and remove the tag when their membership
		// is cancelled.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-1'        => $_ENV['CONVERTKIT_API_TAG_ID'],
				'convertkit-mapping-1-cancel' => $_ENV['CONVERTKIT_API_TAG_ID'] . '-remove',
			]
		);

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Create member.
		$I->memberMouseCreateMember($I, $emailAddress);

		// Check subscriber exists.
		$subscriber = $I->apiCheckSubscriberExists($I, $emailAddress);

		// Check that the subscriber has been assigned to the tag.
		$I->apiCheckSubscriberHasTag(
			$I,
			subscriberID: $subscriber['id'],
			tagID: $_ENV['CONVERTKIT_API_TAG_ID']
		);

		// Cancel the user's membership level.
		$I->amOnAdminPage('admin.php?page=manage_members');
		$I->click($emailAddress);
		$I->click('Access Rights');
		$I->click('Cancel Membership');

		// Accept popups.
		$I->memberMouseAcceptPopups($I, 2);

		// Check that the subscriber is no longer assigned to the tag.
		$I->apiCheckSubscriberHasNoTags($I, $subscriber['id']);
	}

	/**
	 * Test that the member is tagged with the configured "apply tag on cancelled"
	 * setting when deleted.
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testMemberTaggedWhenDeleted(EndToEndTester $I)
	{
		// Setup Plugin to tag users added to the Free Membership level to the
		// ConvertKit Tag ID, and assign them a different tag when their membership
		// is cancelled through account deletion.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-1'        => $_ENV['CONVERTKIT_API_TAG_ID'],
				'convertkit-mapping-1-cancel' => $_ENV['CONVERTKIT_API_TAG_CANCEL_ID'],
			]
		);

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Create member.
		$I->memberMouseCreateMember($I, $emailAddress);

		// Check subscriber exists.
		$subscriber = $I->apiCheckSubscriberExists($I, $emailAddress);

		// Check that the subscriber has been assigned to the tag.
		$I->apiCheckSubscriberHasTag(
			$I,
			subscriberID: $subscriber['id'],
			tagID: $_ENV['CONVERTKIT_API_TAG_ID']
		);

		// Cancel the user's membership level.
		$I->amOnAdminPage('admin.php?page=manage_members');
		$I->click($emailAddress);
		$I->click('Delete Member');

		// Accept popups.
		$I->memberMouseAcceptPopups($I, 2);

		// Check that the subscriber has been assigned to the cancelled tag.
		$I->apiCheckSubscriberHasTag(
			$I,
			subscriberID: $subscriber['id'],
			tagID: $_ENV['CONVERTKIT_API_TAG_CANCEL_ID']
		);
	}

	/**
	 * Test that the member has a tag removed when their membership is deleted.
	 *
	 * @since   1.2.8
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testMemberTagRemovedWhenDeleted(EndToEndTester $I)
	{
		// Setup Plugin to tag users added to the Free Membership level to the
		// ConvertKit Tag ID, and remove the tag when their membership
		// is deleted.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-1'        => $_ENV['CONVERTKIT_API_TAG_ID'],
				'convertkit-mapping-1-cancel' => $_ENV['CONVERTKIT_API_TAG_ID'] . '-remove',
			]
		);

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Create member.
		$I->memberMouseCreateMember($I, $emailAddress);

		// Check subscriber exists.
		$subscriber = $I->apiCheckSubscriberExists($I, $emailAddress);

		// Check that the subscriber has been assigned to the tag.
		$I->apiCheckSubscriberHasTag(
			$I,
			subscriberID: $subscriber['id'],
			tagID: $_ENV['CONVERTKIT_API_TAG_ID']
		);

		// Cancel the user's membership level.
		$I->amOnAdminPage('admin.php?page=manage_members');
		$I->click($emailAddress);
		$I->click('Delete Member');

		// Accept popups.
		$I->memberMouseAcceptPopups(
			$I,
			numberOfPopups: 2
		);

		// Check that the subscriber is no longer assigned to the tag.
		$I->apiCheckSubscriberHasNoTags($I, $subscriber['id']);
	}

	/**
	 * Test that the member is tagged with the configured "apply tag" when:
	 * - a first level is added, applying a tag,
	 * - a first level is cancelled, applying a cancel tag,
	 * - a second level is added, applying a tag.
	 *
	 * @since   1.2.1
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testMemberTaggedWhenMembershipLevelAddedCancelledAndReAdded(EndToEndTester $I)
	{
		// Create an additional membership level.
		$levelID = $I->memberMouseCreateMembershipLevel($I, 'Premium');

		// Setup Plugin to tag users added to the Free Membership level to the
		// ConvertKit Tag ID, and assign them a different tag when their membership
		// is cancelled.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-1'           => $_ENV['CONVERTKIT_API_TAG_ID'],
				'convertkit-mapping-1-cancel'    => $_ENV['CONVERTKIT_API_TAG_CANCEL_ID'],
				'convertkit-mapping-' . $levelID => $_ENV['CONVERTKIT_API_TAG_ID'],
			]
		);

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Create member.
		$I->memberMouseCreateMember($I, $emailAddress);

		// Check subscriber exists.
		$subscriber = $I->apiCheckSubscriberExists($I, $emailAddress);

		// Check that the subscriber has been assigned to the tag.
		$I->apiCheckSubscriberHasTag(
			$I,
			subscriberID: $subscriber['id'],
			tagID: $_ENV['CONVERTKIT_API_TAG_ID']
		);

		// Cancel the user's membership level.
		$I->amOnAdminPage('admin.php?page=manage_members');
		$I->click($emailAddress);
		$I->click('Access Rights');
		$I->click('Cancel Membership');

		// Accept popups.
		$I->memberMouseAcceptPopups(
			$I,
			numberOfPopups: 2
		);

		// Check that the subscriber has been assigned to the cancelled tag.
		$I->apiCheckSubscriberHasTag(
			$I,
			subscriberID: $subscriber['id'],
			tagID: $_ENV['CONVERTKIT_API_TAG_CANCEL_ID']
		);

		// Remove tags added to subscriber.
		$I->apiSubscriberRemoveTag(
			$I,
			subscriberID: $subscriber['id'],
			tagID: $_ENV['CONVERTKIT_API_TAG_ID']
		);
		$I->apiSubscriberRemoveTag(
			$I,
			subscriberID: $subscriber['id'],
			tagID: $_ENV['CONVERTKIT_API_TAG_CANCEL_ID']
		);
		// Confirm tag removal worked.
		$I->apiCheckSubscriberHasNoTags($I, $subscriber['id']);

		// Assign second, new membership level.
		$I->selectOption('#mm-new-membership-selection', 'Premium');
		$I->click('Change Membership');

		// Accept popups.
		$I->memberMouseAcceptPopups(
			$I,
			numberOfPopups: 2
		);

		// Check that the subscriber has been assigned to the tag for the second membership level.
		$I->apiCheckSubscriberHasTag(
			$I,
			subscriberID: $subscriber['id'],
			tagID: $_ENV['CONVERTKIT_API_TAG_ID']
		);
	}

	/**
	 * Test that the member is not subscribed or tagged when the configured "apply tag on add"
	 * setting is set to 'None', and the member is added to a Membership Level.
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testMemberNotTaggedWhenMembershipLevelAdded(EndToEndTester $I)
	{
		// Setup Plugin to not tag users added to the Free Membership level to the
		// ConvertKit Tag ID.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-1' => '',
			]
		);

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Create member.
		$I->memberMouseCreateMember($I, $emailAddress);

		// Check subscriber does not exist.
		$I->apiCheckSubscriberDoesNotExist($I, $emailAddress);
	}

	/**
	 * Test that the member is not tagged when the configured "apply tag on add"
	 * setting is set to 'None' and the Membership Level is changed.
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testMemberNotTaggedWhenMembershipLevelChanged(EndToEndTester $I)
	{
		// Create an additional membership level.
		$levelID = $I->memberMouseCreateMembershipLevel($I, 'Premium');

		// Setup Plugin to tag users added to the Free Membership level to the
		// ConvertKit Tag ID, but not tag when migrated to the Premium Membership
		// level.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-1'           => $_ENV['CONVERTKIT_API_TAG_ID'],
				'convertkit-mapping-' . $levelID => '',
			]
		);

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Create member.
		$I->memberMouseCreateMember($I, $emailAddress);

		// Check subscriber exists.
		$subscriber = $I->apiCheckSubscriberExists($I, $emailAddress);

		// Check that the subscriber has been assigned to the tag.
		$I->apiCheckSubscriberHasTag(
			$I,
			subscriberID: $subscriber['id'],
			tagID: $_ENV['CONVERTKIT_API_TAG_ID']
		);

		// Change the user's membership level.
		$I->amOnAdminPage('admin.php?page=manage_members');
		$I->click($emailAddress);
		$I->click('Access Rights');
		$I->selectOption('#mm-new-membership-selection', 'Premium');
		$I->click('Change Membership');

		// Accept popups.
		$I->memberMouseAcceptPopups(
			$I,
			numberOfPopups: 2
		);

		// Check subscriber exists.
		$subscriber = $I->apiCheckSubscriberExists($I, $emailAddress);

		// Check that the subscriber is still assigned to the first tag and has no additional tags.
		$I->apiCheckSubscriberHasTag(
			$I,
			subscriberID: $subscriber['id'],
			tagID: $_ENV['CONVERTKIT_API_TAG_ID']
		);
		$I->apiCheckSubscriberTagCount(
			$I,
			subscriberID: $subscriber['id'],
			numberOfTags: 1
		);
	}

	/**
	 * Test that the member is not tagged when the configured "apply tag on cancel"
	 * setting is set to 'None' and the Membership Level is removed.
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testMemberNotTaggedWhenMembershipLevelCancelled(EndToEndTester $I)
	{
		// Setup Plugin to tag users added to the Free Membership level to the
		// ConvertKit Tag ID, but not assign a different tag when their membership
		// is cancelled.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-1'        => $_ENV['CONVERTKIT_API_TAG_ID'],
				'convertkit-mapping-1-cancel' => '',
			]
		);

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Create member.
		$I->memberMouseCreateMember($I, $emailAddress);

		// Check subscriber exists.
		$subscriber = $I->apiCheckSubscriberExists($I, $emailAddress);

		// Check that the subscriber has been assigned to the tag.
		$I->apiCheckSubscriberHasTag(
			$I,
			subscriberID: $subscriber['id'],
			tagID: $_ENV['CONVERTKIT_API_TAG_ID']
		);

		// Cancel the user's membership level.
		$I->amOnAdminPage('admin.php?page=manage_members');
		$I->click($emailAddress);
		$I->click('Access Rights');
		$I->click('Cancel Membership');

		// Accept popups.
		$I->memberMouseAcceptPopups($I, 2);

		// Check that the subscriber is still assigned to the first tag and has no additional tags.
		$I->apiCheckSubscriberHasTag(
			$I,
			subscriberID: $subscriber['id'],
			tagID: $_ENV['CONVERTKIT_API_TAG_ID']
		);
		$I->apiCheckSubscriberTagCount(
			$I,
			subscriberID: $subscriber['id'],
			numberOfTags: 1
		);
	}

	/**
	 * Test that the member is not tagged when the configured "apply tag on cancel"
	 * setting is set to 'None' and the member is deleted.
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testMemberNotTaggedWhenDeleted(EndToEndTester $I)
	{
		// Setup Plugin to tag users added to the Free Membership level to the
		// ConvertKit Tag ID, but not assign them a different tag when their membership
		// is cancelled through account deletion.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-1'        => $_ENV['CONVERTKIT_API_TAG_ID'],
				'convertkit-mapping-1-cancel' => '',

			]
		);

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Create member.
		$I->memberMouseCreateMember($I, $emailAddress);

		// Check subscriber exists.
		$subscriber = $I->apiCheckSubscriberExists($I, $emailAddress);

		// Check that the subscriber has been assigned to the tag.
		$I->apiCheckSubscriberHasTag(
			$I,
			subscriberID: $subscriber['id'],
			tagID: $_ENV['CONVERTKIT_API_TAG_ID']
		);

		// Cancel the user's membership level.
		$I->amOnAdminPage('admin.php?page=manage_members');
		$I->click($emailAddress);
		$I->click('Delete Member');

		// Accept popups.
		$I->memberMouseAcceptPopups($I, 2);

		// Check that the subscriber is still assigned to the first tag and has no additional tags.
		$I->apiCheckSubscriberHasTag(
			$I,
			subscriberID: $subscriber['id'],
			tagID: $_ENV['CONVERTKIT_API_TAG_ID']
		);
		$I->apiCheckSubscriberTagCount(
			$I,
			subscriberID: $subscriber['id'],
			numberOfTags: 1
		);
	}

	/**
	 * Deactivate and reset Plugin(s) after each test, if the test passes.
	 * We don't use _after, as this would provide a screenshot of the Plugin
	 * deactivation and not the true test error.
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function _passed(EndToEndTester $I)
	{
		$I->deactivateThirdPartyPlugin($I, 'membermouse-platform');
		$I->deactivateConvertKitPlugin($I);
		$I->resetConvertKitPlugin($I);
	}
}
