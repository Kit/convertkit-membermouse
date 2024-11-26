<?php
namespace Helper\Acceptance;

/**
 * Helper methods and actions related to the MemberMouse Plugin,
 * which are then available using $I->{yourFunctionName}.
 *
 * @since   1.2.0
 */
class MemberMouse extends \Codeception\Module
{
	/**
	 * Helper method to create a membership level.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I     AcceptanceTester.
	 * @param   string           $name  Membership Level Name.
	 * @return  int                     Membership Level ID.
	 */
	public function memberMouseCreateMembershipLevel($I, $name)
	{
		return $I->haveInDatabase(
			'wp_mm_membership_levels',
			[
				'reference_key'      => 'm9BvU2',
				'is_free'            => 1,
				'is_default'         => 0,
				'name'               => $name,
				'description'        => $name,
				'wp_role'            => 'mm-ignore-role',
				'default_product_id' => 0,
				'status'             => 1,
			]
		);
	}

	/**
	 * Helper method to create a product.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I     AcceptanceTester.
	 * @param   string           $name  Product Name.
	 * @param   string           $key   Product Reference Key.
	 * @return  int                     Product ID.
	 */
	public function memberMouseCreateProduct($I, $name, $key)
	{
		return $I->haveInDatabase(
			'wp_mm_products',
			[
				'reference_key' => $key,
				'status'        => 1,
				'name'          => $name,
				'price'         => 1,
			]
		);
	}

	/**
	 * Helper method to create a bundle.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I            AcceptanceTester.
	 * @param   string           $name         Bundle Name.
	 * @param   array            $productIDs   Product IDs to assign to the bundle.
	 * @return  int                            Bundle ID.
	 */
	public function memberMouseCreateBundle($I, $name, $productIDs)
	{
		$bundleID = $I->haveInDatabase(
			'wp_mm_bundles',
			[
				'name'    => $name,
				'is_free' => 0,
				'status'  => 1,
			]
		);

		foreach ( $productIDs as $productID ) {
			$I->haveInDatabase(
				'wp_mm_bundle_products',
				[
					'bundle_id'  => $bundleID,
					'product_id' => $productID,
				]
			);
		}

		return $bundleID;
	}

	/**
	 * Helper method to create a member in MemberMouse.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I                     AcceptanceTester.
	 * @param   string           $emailAddress          Email Address.
	 * @param   string           $membershipLevelName   Membership Level Name to assign to member.
	 */
	public function memberMouseCreateMember($I, $emailAddress, $membershipLevelName = 'Free Membership')
	{
		// Navigate to MemberMouse > Manage Members.
		$I->amOnAdminPage('admin.php?page=manage_members');

		// Create Member.
		$I->click('Create Member');
		$I->waitForElementVisible('#mm-new-member-form-container');
		$I->selectOption('#mm-new-membership-selector', $membershipLevelName);
		$I->fillField('#mm-new-first-name', 'First');
		$I->fillField('#mm-new-last-name', 'Last');
		$I->fillField('#mm-new-email', $emailAddress);
		$I->fillField('#mm-new-password', '12345678');

		$I->click('Create Member', '.mm-dialog-button-container');
		$I->waitForElementNotVisible('#mm-new-member-form-container');

		// Accept popup once user created.
		// We have to wait as there's no specific event MemberMouse fires to tell
		// us it completed adding the member.
		$I->memberMouseAcceptPopups($I, 1, 15);
	}

	/**
	 * Helper method to update a member's email address in MemberMouse.
	 *
	 * @since   1.2.2
	 *
	 * @param   AcceptanceTester $I                     AcceptanceTester.
	 * @param   string           $emailAddress          Email Address.
	 * @param   string           $newEmailAddress       New Email Address.
	 * @param   bool|string      $newFirstName          New First Name.
	 * @param   bool|string      $newLastName           New Last Name.
	 */
	public function memberMouseUpdateMember($I, $emailAddress, $newEmailAddress, $newFirstName = false, $newLastName = false)
	{
		// Click account with current email address.
		$I->click($emailAddress);

		// Change email address and first name.
		$I->fillField('#mm-email', $newEmailAddress);
		if ($newFirstName) {
			$I->fillField('#mm-first-name', $newFirstName);
		}
		if ($newLastName) {
			$I->fillField('#mm-last-name', $newLastName);
		}

		$I->click('Update Member');

		// Accept popup once user updated.
		// We have to wait as there's no specific event MemberMouse fires to tell
		// us it completed updating the member.
		$I->memberMouseAcceptPopups($I, 1);
	}

	/**
	 * Helper method to assign a bundle to the given email address.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   string           $emailAddress  Email Address.
	 * @param   string           $bundleName    Bundle name to cancel.
	 */
	public function memberMouseAssignBundleToMember($I, $emailAddress, $bundleName)
	{
		// Cancel the user's bundle.
		$I->amOnAdminPage('admin.php?page=manage_members');
		$I->click($emailAddress);
		$I->click('Access Rights');

		// This isn't a typo; MemberMouse's bundle <select> dropdown has this name.
		$I->selectOption('bundle-seletor', $bundleName);
		$I->click('Apply Bundle');

		// Wait for modal.
		$I->waitForElementVisible('#mm-payment-options-dialog');

		// Comp product for free.
		$I->click('Comp ' . $bundleName);

		// Accept popups.
		$I->memberMouseAcceptPopups($I, 2);
	}

	/**
	 * Helper method to cancel a bundle for the given email address.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   string           $emailAddress  Email Address.
	 * @param   string           $bundleName    Bundle name to cancel.
	 */
	public function memberMouseCancelMemberBundle($I, $emailAddress, $bundleName)
	{
		// Cancel the user's bundle.
		$I->amOnAdminPage('admin.php?page=manage_members');
		$I->click($emailAddress);
		$I->click('Access Rights');
		$I->click('a[title="Cancel ' . $bundleName . '"]');

		// Accept popups.
		$I->memberMouseAcceptPopups($I, 2, 7);
	}

	/**
	 * Helper method to re-activate a previously cancelled bundle for the given email address.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   string           $emailAddress  Email Address.
	 * @param   string           $bundleName    Bundle name to activate.
	 */
	public function memberMouseResumeMemberBundle($I, $emailAddress, $bundleName)
	{
		// Activate the user's bundle.
		$I->amOnAdminPage('admin.php?page=manage_members');
		$I->click($emailAddress);
		$I->click('Access Rights');
		$I->click('a[title="Activate ' . $bundleName . '"]');

		// Wait for modal.
		$I->waitForElementVisible('#mm-payment-options-dialog');
		$I->click('Comp ' . $bundleName);

		// Accept popups.
		$I->memberMouseAcceptPopups($I, 2);
	}

	/**
	 * Helper method to setup the MemberMouse Plugin for tests
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I            AcceptanceTester.
	 */
	public function memberMouseSetupPlugin($I)
	{
		// Define test payment service.
		$I->dontHaveInDatabase('wp_mm_payment_services', [ 'token' => 'TEST' ]);
		$I->haveInDatabase(
			'wp_mm_payment_services',
			[
				'id'     => 14,
				'token'  => 'TEST',
				'name'   => serialize(
					[
						'mode'                           => 'always-override',
						'testProcessorOverrideKey'       => '',
						'productionProcessorOverrideKey' => '',
					]
				),
				'active' => 1,
			]
		);

		// Don't use MemberMouse login page, as it breaks some tests when cleaning up / deactivating Plugins.
		$I->haveOptionInDatabase('mm-option-use-mm-login-page', '0');
	}

	/**
	 * Helper method to log out from WordPress when MemberMouse is enabled.
	 * We don't use logOut() as MemberMouse hijacks the logout process with a redirect,
	 * resulting in the logOut() assertion `loggedout=true` failing.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I            AcceptanceTester.
	 */
	public function memberMouseLogOut($I)
	{
		$I->amOnPage('wp-login.php?action=logout');
		$I->click("//a[contains(@href,'action=logout')]");
	}

	/**
	 * Helper method to complete the checkout process for the given MemberMouse
	 * Product by its reference key.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I                     AcceptanceTester.
	 * @param   string           $productReferenceKey   Product reference key.
	 * @param   string           $emailAddress          Email Address.
	 */
	public function memberMouseCheckoutProduct($I, $productReferenceKey, $emailAddress)
	{
		// Navigate to purchase screen for the product.
		$I->amOnPage('checkout/?rid=' . $productReferenceKey);

		// Complete checkout.
		$I->fillField('mm_field_first_name', 'First');
		$I->fillField('mm_field_last_name', 'Last');
		$I->fillField('mm_field_email', $emailAddress);
		$I->fillField('mm_field_password', '12345678');
		$I->fillField('mm_field_phone', '12345678');
		$I->fillField('mm_field_cc_number', '4242424242424242');
		$I->fillField('mm_field_cc_cvv', '123');
		$I->selectOption('mm_field_cc_exp_year', '2038');
		$I->fillField('mm_field_billing_address', '123 Main Street');
		$I->fillField('mm_field_billing_city', 'Nashville');
		$I->selectOption('#mm_field_billing_state_dd', 'Tennessee');
		$I->fillField('mm_field_billing_zip', '37208');

		// Submit.
		$I->waitForElementNotVisible('.lock-area');
		$I->click('Submit Order');

		// Wait for confirmation.
		$I->waitForText('Thank you for your order');
	}

	/**
	 * Helper method to wait and acecpt browser popups when
	 * managing members in MemberMouse.
	 *
	 * @since   1.2.8
	 *
	 * @param   AcceptanceTester $I                     AcceptanceTester.
	 * @param   int              $numberOfPopups        Number of popups to accept.
	 * @param   int              $waitInSeconds         Number of seconds to wait for each popup.
	 */
	public function memberMouseAcceptPopups($I, $numberOfPopups, $waitInSeconds = 5)
	{
		// We have to wait as there's no specific event MemberMouse fires to tell
		// us it completed changing the membership level.
		for ( $i = 1; $i <= $numberOfPopups; $i++) {
			$I->wait($waitInSeconds);
			$I->acceptPopup();
		}

		$I->wait($waitInSeconds);
	}
}
