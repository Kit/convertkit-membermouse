<?php
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
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function _before(AcceptanceTester $I)
	{
		// Activate Plugins.
		$I->activateConvertKitPlugin($I);
		$I->activateThirdPartyPlugin($I, 'membermouse-platform');
		$I->memberMouseSetupPlugin($I);
	}

	/**
	 * Test that the member is tagged with the configured "apply tag on add"
	 * setting when added to a Membership Level.
	 *
	 * @since   1.2.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testMemberNameAndEmailUpdatedWhenChangedInMemberMouse(AcceptanceTester $I)
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
		$subscriberID = $I->apiCheckSubscriberExists($I, $emailAddress);

		// Change the member's first name and email address.
		$newFirstName    = 'New First Name';
		$newEmailAddress = 'new-' . $emailAddress;
		$I->memberMouseUpdateMember($I, $emailAddress, $newEmailAddress, $newFirstName);

		// Check the subscriber's email address was updated in ConvertKit.
		$subscriberIDAfterNewEmailAddress = $I->apiCheckSubscriberExists($I, $newEmailAddress, $newFirstName);

		// Confirm the subscriber ID is the same.
		$I->assertEquals($subscriberID, $subscriberIDAfterNewEmailAddress);
	}

	/**
	 * Deactivate and reset Plugin(s) after each test, if the test passes.
	 * We don't use _after, as this would provide a screenshot of the Plugin
	 * deactivation and not the true test error.
	 *
	 * @since   1.2.2
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function _passed(AcceptanceTester $I)
	{
		$I->deactivateThirdPartyPlugin($I, 'membermouse-platform');
		$I->deactivateConvertKitPlugin($I);
		$I->resetConvertKitPlugin($I);
	}
}
