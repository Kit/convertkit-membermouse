<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests that subscribers email address and first name are updated in ConvertKit
 * when changed in MemberMouse.
 *
 * @since   1.2.2
 */
class MemberSubscribeCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 *
	 * @since   1.2.2
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
	 * Test that the member's name, email address and custom field data are updated in Kit when their
	 * information is updated in MemberMouse.
	 *
	 * @since   1.2.2
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testMemberNameAndEmailUpdatedWhenChangedInMemberMouse(EndToEndTester $I)
	{
		// Setup Plugin to tag users added to the Free Membership level to the
		// ConvertKit Tag ID.
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

		// Change the member's first name and email address.
		$newFirstName    = 'New First Name';
		$newLastName     = 'New Last Name';
		$newEmailAddress = 'new-' . $emailAddress;
		$I->memberMouseUpdateMember(
			$I,
			emailAddress: $emailAddress,
			newEmailAddress: $newEmailAddress,
			newFirstName: $newFirstName,
			newLastName: $newLastName
		);

		// Check the subscriber's email address was updated in ConvertKit.
		$subscriberAfterNewEmailAddress = $I->apiCheckSubscriberExists(
			$I,
			emailAddress: $newEmailAddress,
			firstName: $newFirstName
		);

		// Confirm the subscriber ID is the same.
		$I->assertEquals($subscriber['id'], $subscriberAfterNewEmailAddress['id']);

		// Check that the subscriber has the custom field data.
		$I->apiCustomFieldDataIsValid(
			$I,
			subscriber: $subscriberAfterNewEmailAddress,
			customFields: [
				'last_name' => $newLastName,
			]
		);
	}

	/**
	 * Deactivate and reset Plugin(s) after each test, if the test passes.
	 * We don't use _after, as this would provide a screenshot of the Plugin
	 * deactivation and not the true test error.
	 *
	 * @since   1.2.2
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
