<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests that subscribers are added to ConvertKit and tagged
 * based on the bundle assigned to a purchased product.
 *
 * @since   1.2.0
 */
class BundleTagCest
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
	 * setting when purchasing a product assigned to the given bundle
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testMemberTaggedWhenBundleAdded(EndToEndTester $I)
	{
		// Create a product.
		$productID = $I->memberMouseCreateProduct(
			$I,
			name: 'Product',
			key: $_ENV['MEMBERMOUSE_PRODUCT_REFERENCE_KEY']
		);

		// Create bundle.
		$bundleID = $I->memberMouseCreateBundle(
			$I,
			name: 'Bundle',
			productIDs: [ $productID ]
		);

		// Setup Plugin to tag users purchasing the bundle to the
		// ConvertKit Tag ID.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-bundle-' . $bundleID => $_ENV['CONVERTKIT_API_TAG_ID'],
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
	 * Test that the member is tagged with the configured "apply tag on add"
	 * setting when a previously cancelled bundle is re-activated on their account
	 * in MemberMouse
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testMemberTaggedWhenBundleReactivated(EndToEndTester $I)
	{
		// Create a product.
		$productID = $I->memberMouseCreateProduct(
			$I,
			name: 'Product',
			key: $_ENV['MEMBERMOUSE_PRODUCT_REFERENCE_KEY']
		);

		// Create bundle.
		$bundleID = $I->memberMouseCreateBundle(
			$I,
			name: 'Bundle',
			productIDs: [ $productID ]
		);

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Create member.
		$I->memberMouseCreateMember($I, $emailAddress);

		// Check that the subscriber does not exist, as no tagging took place.
		$I->apiCheckSubscriberDoesNotExist($I, $emailAddress);

		// Assign bundle.
		$I->memberMouseAssignBundleToMember(
			$I,
			emailAddress: $emailAddress,
			bundleName: 'Bundle'
		);

		// Check that the subscriber does not exist, as no tagging took place.
		$I->apiCheckSubscriberDoesNotExist($I, $emailAddress);

		// Cancel the user's bundle.
		$I->memberMouseCancelMemberBundle(
			$I,
			emailAddress: $emailAddress,
			bundleName: 'Bundle'
		);

		// Check that the subscriber does not exist, as no tagging took place.
		$I->apiCheckSubscriberDoesNotExist($I, $emailAddress);

		// Setup Plugin to tag users assigned / reactivated to the bundle.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-bundle-' . $bundleID => $_ENV['CONVERTKIT_API_TAG_ID'],
			]
		);

		// Re-activate the user's bundle.
		$I->memberMouseResumeMemberBundle(
			$I,
			emailAddress: $emailAddress,
			bundleName: 'Bundle'
		);

		// Check subscriber exists.
		$subscriber = $I->apiCheckSubscriberExists($I, $emailAddress);

		// Check that the subscriber has been assigned to the bundle's tag.
		$I->apiCheckSubscriberHasTag(
			$I,
			subscriberID: $subscriber['id'],
			tagID: $_ENV['CONVERTKIT_API_TAG_ID']
		);
	}

	/**
	 * Test that the member is tagged with the configured "apply tag on cancelled"
	 * setting when the given bundle for the member is cancelled.
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testMemberTaggedWhenBundleCancelled(EndToEndTester $I)
	{
		// Create a product.
		$productID = $I->memberMouseCreateProduct(
			$I,
			name: 'Product',
			key: $_ENV['MEMBERMOUSE_PRODUCT_REFERENCE_KEY']
		);

		// Create bundle.
		$bundleID = $I->memberMouseCreateBundle(
			$I,
			name: 'Bundle',
			productIDs: [ $productID ]
		);

		// Setup Plugin to tag users when the bundle is cancelled.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-bundle-' . $bundleID . '-cancel' => $_ENV['CONVERTKIT_API_TAG_CANCEL_ID'],
			]
		);

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Create member.
		$I->memberMouseCreateMember($I, $emailAddress);

		// Check that the subscriber does not exist, as no tagging took place.
		$I->apiCheckSubscriberDoesNotExist($I, $emailAddress);

		// Assign bundle.
		$I->memberMouseAssignBundleToMember(
			$I,
			emailAddress: $emailAddress,
			bundleName: 'Bundle'
		);

		// Check that the subscriber does not exist, as no tagging took place.
		$I->apiCheckSubscriberDoesNotExist($I, $emailAddress);

		// Cancel the user's bundle.
		$I->memberMouseCancelMemberBundle(
			$I,
			emailAddress: $emailAddress,
			bundleName: 'Bundle'
		);

		// Check subscriber exists.
		$subscriber = $I->apiCheckSubscriberExists($I, $emailAddress);

		// Check that the subscriber has been assigned to the cancelled tag.
		$I->apiCheckSubscriberHasTag(
			$I,
			subscriberID: $subscriber['id'],
			tagID: $_ENV['CONVERTKIT_API_TAG_CANCEL_ID']
		);
	}

	/**
	 * Test that the member has a tag removed when their bundle is cancelled.
	 *
	 * @since   1.2.8
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testMemberTagRemovedWhenBundleCancelled(EndToEndTester $I)
	{
		// Create a product.
		$productID = $I->memberMouseCreateProduct(
			$I,
			name: 'Product',
			key: $_ENV['MEMBERMOUSE_PRODUCT_REFERENCE_KEY']
		);

		// Create bundle.
		$bundleID = $I->memberMouseCreateBundle(
			$I,
			name: 'Bundle',
			productIDs: [ $productID ]
		);

		// Setup Plugin to tag users purchasing the bundle to the
		// ConvertKit Tag ID.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-bundle-' . $bundleID => $_ENV['CONVERTKIT_API_TAG_ID'],
				'convertkit-mapping-bundle-' . $bundleID . '-cancel' => $_ENV['CONVERTKIT_API_TAG_ID'] . '-remove',
			]
		);

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Create member.
		$I->memberMouseCreateMember($I, $emailAddress);

		// Assign bundle.
		$I->memberMouseAssignBundleToMember(
			$I,
			emailAddress: $emailAddress,
			bundleName: 'Bundle'
		);

		// Check subscriber exists.
		$subscriber = $I->apiCheckSubscriberExists($I, $emailAddress);

		// Check that the subscriber has been assigned to the tag.
		$I->apiCheckSubscriberHasTag($I, $subscriber['id'], $_ENV['CONVERTKIT_API_TAG_ID']);

		// Cancel the user's bundle.
		$I->memberMouseCancelMemberBundle(
			$I,
			emailAddress: $emailAddress,
			bundleName: 'Bundle'
		);

		// Check that the subscriber is no longer assigned to the tag.
		$I->apiCheckSubscriberHasNoTags($I, $subscriber['id']);
	}

	/**
	 * Test that the member is not tagged when the configured "apply tag on add"
	 * setting is "none" for the given bundle.
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testMemberNotTaggedWhenBundleAdded(EndToEndTester $I)
	{
		// Create a product.
		$productID = $I->memberMouseCreateProduct(
			$I,
			name: 'Product',
			key: $_ENV['MEMBERMOUSE_PRODUCT_REFERENCE_KEY']
		);

		// Create bundle.
		$bundleID = $I->memberMouseCreateBundle(
			$I,
			name: 'Bundle',
			productIDs: [ $productID ]
		);

		// Setup Plugin to not tag users purchasing the bundle to the
		// ConvertKit Tag ID.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-bundle-' . $bundleID => '',
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
	}

	/**
	 * Test that the member is not tagged when a previously cancelled bundle is
	 * re-activated on their account in MemberMouse and no tagging is configured.
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testMemberNotTaggedWhenBundleReactivated(EndToEndTester $I)
	{
		// Create a product.
		$productID = $I->memberMouseCreateProduct(
			$I,
			name: 'Product',
			key: $_ENV['MEMBERMOUSE_PRODUCT_REFERENCE_KEY']
		);

		// Create bundle.
		$bundleID = $I->memberMouseCreateBundle(
			$I,
			name: 'Bundle',
			productIDs: [ $productID ]
		);

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Create member.
		$I->memberMouseCreateMember($I, $emailAddress);

		// Check that the subscriber does not exist, as no tagging took place.
		$I->apiCheckSubscriberDoesNotExist($I, $emailAddress);

		// Assign bundle.
		$I->memberMouseAssignBundleToMember(
			$I,
			emailAddress: $emailAddress,
			bundleName: 'Bundle'
		);

		// Check that the subscriber does not exist, as no tagging took place.
		$I->apiCheckSubscriberDoesNotExist($I, $emailAddress);

		// Cancel the user's bundle.
		$I->memberMouseCancelMemberBundle(
			$I,
			emailAddress: $emailAddress,
			bundleName: 'Bundle'
		);

		// Check that the subscriber does not exist, as no tagging took place.
		$I->apiCheckSubscriberDoesNotExist($I, $emailAddress);

		// Re-activate the user's bundle.
		$I->memberMouseResumeMemberBundle(
			$I,
			emailAddress: $emailAddress,
			bundleName: 'Bundle'
		);

		// Check that the subscriber does not exist, as no tagging took place.
		$I->apiCheckSubscriberDoesNotExist($I, $emailAddress);
	}

	/**
	 * Test that the member is not tagged when the configured "apply tag on cancelled"
	 * setting is set to 'None' and the given bundle for the member is cancelled.
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testMemberNotTaggedWhenBundleCancelled(EndToEndTester $I)
	{
		// Create a product.
		$productID = $I->memberMouseCreateProduct(
			$I,
			name: 'Product',
			key: $_ENV['MEMBERMOUSE_PRODUCT_REFERENCE_KEY']
		);

		// Create bundle.
		$bundleID = $I->memberMouseCreateBundle(
			$I,
			name: 'Bundle',
			productIDs: [ $productID ]
		);

		// Setup Plugin to tag users purchasing the bundle to the
		// ConvertKit Tag ID.
		$I->setupConvertKitPlugin(
			$I,
			[
				'convertkit-mapping-bundle-' . $bundleID => $_ENV['CONVERTKIT_API_TAG_ID'],
				'convertkit-mapping-bundle-' . $bundleID . '-cancel' => '',
			]
		);

		// Generate email address for test.
		$emailAddress = $I->generateEmailAddress();

		// Create member.
		$I->memberMouseCreateMember($I, $emailAddress);

		// Assign bundle.
		$I->memberMouseAssignBundleToMember(
			$I,
			emailAddress: $emailAddress,
			bundleName: 'Bundle'
		);

		// Check subscriber exists.
		$subscriber = $I->apiCheckSubscriberExists($I, $emailAddress);

		// Check that the subscriber was tagged.
		$I->apiCheckSubscriberHasTag(
			$I,
			subscriberID: $subscriber['id'],
			tagID: $_ENV['CONVERTKIT_API_TAG_ID']
		);

		// Cancel the user's bundle.
		$I->memberMouseCancelMemberBundle(
			$I,
			emailAddress: $emailAddress,
			bundleName: 'Bundle'
		);

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
