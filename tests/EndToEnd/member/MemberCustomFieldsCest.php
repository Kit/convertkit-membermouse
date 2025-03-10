<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests that subscribers are added to Kit and custom field
 * data assigned based on the membership level.
 *
 * @since   1.2.8
 */
class MemberCustomFieldsCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 *
	 * @since   1.2.8
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function _before(EndToEndTester $I)
	{
		// Activate Plugins.
		$I->activateConvertKitPlugin($I);
		$I->activateThirdPartyPlugin($I, 'membermouse-platform');
		$I->memberMouseSetupPlugin($I);
	}

	/**
	 * Test that the custom field data is stored against the subscriber
	 * in Kit when a member is added to a Membership Level in MemberMouse.
	 *
	 * @since   1.2.8
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testMemberCustomFieldsWhenMembershipLevelAdded(EndToEndTester $I)
	{
		// Setup Plugin to tag users purchasing the bundle to the
		// Kit Tag ID, and store the Last Name in the Kit
		// Last Name Custom Field.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-1'   => $_ENV['CONVERTKIT_API_TAG_ID'],
				'custom_field_last_name' => 'last_name',
			]
		);

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Create member.
		$I->memberMouseCreateMember($I, $emailAddress);

		// Check subscriber exists.
		$subscriber = $I->apiCheckSubscriberExists($I, $emailAddress);

		// Check that the subscriber has been assigned to the tag.
		$I->apiCheckSubscriberHasTag($I, $subscriber['id'], $_ENV['CONVERTKIT_API_TAG_ID']);

		// Check that the subscriber has the custom field data.
		$I->apiCustomFieldDataIsValid(
			$I,
			$subscriber,
			[
				'last_name' => 'Last',
			]
		);
	}

	/**
	 * Test that the custom field data is stored against the subscriber
	 * in Kit when a member's Membership Level is changed in MemberMouse.
	 *
	 * @since   1.2.8
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testMemberCustomFieldsWhenMembershipLevelChanged(EndToEndTester $I)
	{
		// Create an additional membership level.
		$levelID = $I->memberMouseCreateMembershipLevel($I, 'Premium');

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Create member.
		$I->memberMouseCreateMember($I, $emailAddress);

		// Setup Plugin to tag users purchasing the bundle to the
		// Kit Tag ID, and store the Last Name in the Kit
		// Last Name Custom Field.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-' . $levelID => $_ENV['CONVERTKIT_API_TAG_ID'],
				'custom_field_last_name'         => 'last_name',
			]
		);

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
		$I->apiCheckSubscriberHasTag($I, $subscriber['id'], $_ENV['CONVERTKIT_API_TAG_ID']);

		// Check that the subscriber has the custom field data.
		$I->apiCustomFieldDataIsValid(
			$I,
			$subscriber,
			[
				'last_name' => 'Last',
			]
		);
	}

	/**
	 * Deactivate and reset Plugin(s) after each test, if the test passes.
	 * We don't use _after, as this would provide a screenshot of the Plugin
	 * deactivation and not the true test error.
	 *
	 * @since   1.2.8
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function _passed(EndToEndTester $I)
	{
		$I->deactivateConvertKitPlugin($I);
		$I->resetConvertKitPlugin($I);
	}
}
