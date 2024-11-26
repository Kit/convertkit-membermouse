<?php
namespace Helper\Acceptance;

/**
 * Helper methods and actions related to third party Plugins,
 * which are then available using $I->{yourFunctionName}.
 *
 * @since   1.2.0
 */
class ThirdPartyPlugin extends \Codeception\Module
{
	/**
	 * Helper method to activate a third party Plugin, checking
	 * it activated and no errors were output.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I     AcceptanceTester.
	 * @param   string           $name  Plugin Slug.
	 */
	public function activateThirdPartyPlugin($I, $name)
	{
		// Login as the Administrator.
		$I->loginAsAdmin();

		// Go to the Plugins screen in the WordPress Administration interface.
		$I->amOnPluginsPage();

		// Activate the Plugin.
		$I->activatePlugin($name);

		// Go to the Plugins screen again; this prevents any Plugin that loads a wizard-style screen from
		// causing seePluginActivated() to fail.
		$I->amOnPluginsPage();

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);
	}

	/**
	 * Helper method to activate a third party Plugin, checking
	 * it activated and no errors were output.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I      Acceptance Tester.
	 * @param   string           $name   Plugin Slug.
	 */
	public function deactivateThirdPartyPlugin($I, $name)
	{
		// Login as the Administrator.
		$I->amOnPage('wp-login.php');
		$I->waitForElement('#user_login', 10);
		$I->fillField('#user_login', $_ENV['TEST_SITE_ADMIN_USERNAME']);
		$I->waitForElement('#user_pass', 10);
		$I->fillField('#user_pass', $_ENV['TEST_SITE_ADMIN_PASSWORD']);

		// Wait and complete the password field again, because sometimes it doesn't
		// get filled the first time.
		$I->wait(1);
		$I->fillField('#user_pass', $_ENV['TEST_SITE_ADMIN_PASSWORD']);

		$I->click('#wp-submit');

		// Wait for admin interface to load.
		$I->waitForElementVisible('body.wp-admin');

		// Go to the Plugins screen in the WordPress Administration interface.
		$I->amOnPluginsPage();

		// Deactivate the Plugin.
		$I->deactivatePlugin($name);

		// Wait for notice to display.
		$I->waitForElementVisible('div.updated');

		// Check that the Plugin deactivated successfully.
		$I->seePluginDeactivated($name);
	}
}
