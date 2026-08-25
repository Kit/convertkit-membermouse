<?php

namespace Tests\EndToEnd;

use Tests\Support\EndToEndTester;

/**
 * Tests that the Plugin's log file is created and written to.
 *
 * @since   1.4.6
 */
class LogCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 *
	 * @since   1.4.6
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
	 * Test that the Plugin's log file is created and written to.
	 *
	 * @since   1.4.6
	 *
	 * @param   EndToEndTester $I  Tester.
	 */
	public function testLogFileIsCreatedAndWrittenTo(EndToEndTester $I)
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

		// Confirm no log directory or files exist in the Plugin's directory.
		$I->dontSeePluginFileFound('convertkit-membermouse/log-api.txt');
		$I->dontSeePluginFileFound('convertkit-membermouse/log-tag.txt');
		$I->dontSeePluginFileFound('convertkit-membermouse/log/.htaccess');
		$I->dontSeePluginFileFound('convertkit-membermouse/log/index.html');
		$I->dontSeePluginFileFound('convertkit-membermouse/log');

		// Confirm the log directory was created in the uploads directory, with the
		// .htaccess and index.html files that prevent listing and access on Apache.
		$I->seeUploadedFileFound('kit-logs/.htaccess');
		$I->seeUploadedFileFound('kit-logs/index.html');
	}

	/**
	 * Deactivate and reset Plugin(s) after each test, if the test passes.
	 * We don't use _after, as this would provide a screenshot of the Plugin
	 * deactivation and not the true test error.
	 *
	 * @since   1.4.6
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
