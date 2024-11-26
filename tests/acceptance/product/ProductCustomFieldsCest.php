<?php
/**
 * Tests that subscribers are added to Kit and custom field
 * data assigned when purchasing a MemberMouse product.
 *
 * @since   1.2.8
 */
class ProductCustomFieldsCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 *
	 * @since   1.2.8
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
	 * Test that the custom field data is stored against the subscriber
	 * in Kit when purchasing a product in MemberMouse.
	 *
	 * @since   1.2.8
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testMemberCustomFieldsWhenProductPurchased(AcceptanceTester $I)
	{
		// Create a product.
		$productID = $I->memberMouseCreateProduct($I, 'Product', $_ENV['MEMBERMOUSE_PRODUCT_REFERENCE_KEY']);

		// Setup Plugin to tag users purchasing the bundle to the
		// Kit Tag ID, and store the Last Name in the Kit
		// Last Name Custom Field.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-product-1' => $_ENV['CONVERTKIT_API_TAG_ID'],
				'custom_field_last_name' 	   => 'last_name',
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
		$I->apiCustomFieldDataIsValid($I, $subscriber, [
			'last_name' => 'Last',
		]);
	}

	/**
	 * Deactivate and reset Plugin(s) after each test, if the test passes.
	 * We don't use _after, as this would provide a screenshot of the Plugin
	 * deactivation and not the true test error.
	 *
	 * @since   1.2.8
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function _passed(AcceptanceTester $I)
	{
		$I->deactivateConvertKitPlugin($I);
		$I->resetConvertKitPlugin($I);
	}
}
