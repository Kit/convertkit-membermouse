<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests Plugin activation and deactivation.
 *
 * @since   1.2.0
 */
class ActivateDeactivatePluginCest
{
	/**
	 * Test that activating the Plugin and the MemberMouse Plugins works
	 * with no errors.
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testPluginActivationDeactivation(EndToEndTester $I)
	{
		$I->activateConvertKitPlugin($I);
		$I->activateThirdPartyPlugin($I, 'membermouse-platform');

		// Go to the Plugin's Settings > General Screen.
		$I->amOnAdminPage('options-general.php?page=convertkit-mm');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		$I->deactivateConvertKitPlugin($I);
		$I->deactivateThirdPartyPlugin($I, 'membermouse-platform');
	}

	/**
	 * Test that activating the Plugin, without activating the MemberMouse Plugin, works
	 * with no errors.
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testPluginActivationDeactivationWithoutMemberMouse(EndToEndTester $I)
	{
		$I->activateConvertKitPlugin($I);

		// Go to the Plugin's Settings > General Screen.
		$I->amOnAdminPage('options-general.php?page=convertkit-mm');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		$I->deactivateConvertKitPlugin($I);
	}
}
