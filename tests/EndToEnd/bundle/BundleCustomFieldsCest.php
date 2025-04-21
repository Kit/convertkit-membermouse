<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests that subscribers are added to Kit and custom field
 * data assigned based on the assigned bundle.
 *
 * @since   1.2.8
 */
class BundleCustomFieldsCest
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
	 * in Kit when a bundle is assigned to the member in MemberMouse.
	 *
	 * @since   1.2.8
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testMemberCustomFieldsWhenBundleAdded(EndToEndTester $I)
	{
		// Create a product.
		$productID = $I->memberMouseCreateProduct($I, 'Product', $_ENV['MEMBERMOUSE_PRODUCT_REFERENCE_KEY']);

		// Create bundle.
		$bundleID = $I->memberMouseCreateBundle($I, 'Bundle', [ $productID ]);

		// Setup Plugin to tag users purchasing the bundle to the
		// Kit Tag ID, and store the Last Name in the Kit
		// Last Name Custom Field.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-bundle-' . $bundleID => $_ENV['CONVERTKIT_API_TAG_ID'],
				'custom_field_last_name'                 => 'last_name',
			]
		);

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Logout.
		$I->memberMouseLogOut($I);

		// Complete checkout.
		$I->memberMouseCheckoutProduct($I, $_ENV['MEMBERMOUSE_PRODUCT_REFERENCE_KEY'], $emailAddress);

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
	 * in Kit when a bundle is re-activated for the member in MemberMouse.
	 *
	 * @since   1.2.8
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testMemberCustomFieldsWhenBundleReactivated(EndToEndTester $I)
	{
		// Create a product.
		$productID = $I->memberMouseCreateProduct($I, 'Product', $_ENV['MEMBERMOUSE_PRODUCT_REFERENCE_KEY']);

		// Create bundle.
		$bundleID = $I->memberMouseCreateBundle($I, 'Bundle', [ $productID ]);

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Create member.
		$I->memberMouseCreateMember($I, $emailAddress);

		// Check that the subscriber does not exist, as no tagging took place.
		$I->apiCheckSubscriberDoesNotExist($I, $emailAddress);

		// Assign bundle.
		$I->memberMouseAssignBundleToMember($I, $emailAddress, 'Bundle');

		// Check that the subscriber does not exist, as no tagging took place.
		$I->apiCheckSubscriberDoesNotExist($I, $emailAddress);

		// Cancel the user's bundle.
		$I->memberMouseCancelMemberBundle($I, $emailAddress, 'Bundle');

		// Check that the subscriber does not exist, as no tagging took place.
		$I->apiCheckSubscriberDoesNotExist($I, $emailAddress);

		// Setup Plugin to tag users purchasing the bundle to the
		// Kit Tag ID, and store the Last Name in the Kit
		// Last Name Custom Field.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-bundle-' . $bundleID => $_ENV['CONVERTKIT_API_TAG_ID'],
				'custom_field_last_name'                 => 'last_name',
			]
		);

		// Re-activate the user's bundle.
		$I->memberMouseResumeMemberBundle($I, $emailAddress, 'Bundle');

		// Check subscriber exists.
		$subscriber = $I->apiCheckSubscriberExists($I, $emailAddress);

		// Check that the subscriber has been assigned to the bundle's tag.
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
