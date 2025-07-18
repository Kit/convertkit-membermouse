<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests that subscribers are added to ConvertKit and tagged
 * based on the product purchased.
 *
 * @since   1.2.0
 */
class ProductTagCest
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
		$I->memberMouseSetupPlugin($I);
	}

	/**
	 * Test that the member is tagged with the configured "apply tag on add"
	 * setting when purchasing the given product.
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testMemberTaggedWhenProductPurchased(EndToEndTester $I)
	{
		// Create a product.
		$productID = $I->memberMouseCreateProduct(
			$I,
			name: 'Product',
			key: $_ENV['MEMBERMOUSE_PRODUCT_REFERENCE_KEY']
		);

		// Setup Plugin to tag users purchasing the product to the
		// ConvertKit Tag ID.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-product-1' => $_ENV['CONVERTKIT_API_TAG_ID'],
			]
		);

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Logout.
		$I->memberMouseLogOut($I);

		// Complete checkout.
		$I->memberMouseCheckoutProduct(
			$I,
			key: $_ENV['MEMBERMOUSE_PRODUCT_REFERENCE_KEY'],
			emailAddress: $emailAddress
		);

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
	 * Test that the member is not tagged when the configured "apply tag on add"
	 * setting is "none" for the given purchased product.
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testMemberNotTaggedWhenProductPurchased(EndToEndTester $I)
	{
		// Create a product.
		$productID = $I->memberMouseCreateProduct(
			$I,
			name: 'Product',
			key: $_ENV['MEMBERMOUSE_PRODUCT_REFERENCE_KEY']
		);

		// Setup Plugin to not tag users purchasing the product to the
		// ConvertKit Tag ID.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-product-1' => '',
			]
		);

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Logout.
		$I->memberMouseLogOut($I);

		// Complete checkout.
		$I->memberMouseCheckoutProduct(
			$I,
			key: $_ENV['MEMBERMOUSE_PRODUCT_REFERENCE_KEY'],
			emailAddress: $emailAddress
		);

		// Check subscriber does not exist.
		$I->apiCheckSubscriberDoesNotExist($I, $emailAddress);

		// Logout.
		$I->memberMouseLogOut($I);
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
