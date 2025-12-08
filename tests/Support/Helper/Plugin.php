<?php
namespace Tests\Support\Helper;

/**
 * Helper methods and actions related to the ConvertKit Plugin,
 * which are then available using $I->{yourFunctionName}.
 *
 * @since   1.2.0
 */
class Plugin extends \Codeception\Module
{
	/**
	 * Helper method to activate the ConvertKit Plugin, checking
	 * it activated and no errors were output.
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I             Tester.
	 */
	public function activateConvertKitPlugin($I)
	{
		$I->activateThirdPartyPlugin($I, 'convertkit-membermouse');
	}

	/**
	 * Helper method to deactivate the ConvertKit Plugin, checking
	 * it activated and no errors were output.
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I             Tester.
	 */
	public function deactivateConvertKitPlugin($I)
	{
		$I->deactivateThirdPartyPlugin($I, 'convertkit-membermouse');
	}

	/**
	 * Helper method to programmatically setup the Plugin's settings.
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I             Tester.
	 * @param   bool|array     $options {
	 *         Optional. An array of settings.
	 *
	 *     @type string $api_key            API Key (if specified, used instead of CONVERTKIT_API_KEY).
	 *     @type string $access_token       Access Token (if specified, used instead of CONVERTKIT_OAUTH_ACCESS_TOKEN).
	 *     @type string $refresh_token      Refresh Token (if specified, used instead of CONVERTKIT_OAUTH_REFRESH_TOKEN).
	 *     @type string $debug              Enable debugging (default: on).
	 * }
	 */
	public function setupConvertKitPlugin($I, $options = false)
	{
		// Define default options.
		$defaults = [
			// API Key retained for testing Legacy Forms and Landing Pages.
			'api-key'       => $_ENV['CONVERTKIT_API_KEY'],
			'access_token'  => $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
			'refresh_token' => $_ENV['CONVERTKIT_OAUTH_REFRESH_TOKEN'],
			'debug'         => 'on',
		];

		// If supplied options are an array, merge them with the defaults.
		if (is_array($options)) {
			$options = array_merge($defaults, $options);
		} else {
			$options = $defaults;
		}

		// Define settings in options table.
		$I->haveOptionInDatabase('convertkit-mm-options', $options);
	}

	/**
	 * Helper method to reset the ConvertKit Plugin settings, as if it's a clean installation.
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I             Tester.
	 */
	public function resetConvertKitPlugin($I)
	{
		// Plugin Settings.
		$I->dontHaveOptionInDatabase('convertkit-mm-options');

		// Resources.
		$I->dontHaveOptionInDatabase('convertkit-mm-custom-fields');
		$I->dontHaveOptionInDatabase('convertkit-mm-custom-fields_last_queried');
		$I->dontHaveOptionInDatabase('convertkit-mm-tags');
		$I->dontHaveOptionInDatabase('convertkit-mm-tags_last_queried');

		// Persistent notices.
		$I->dontHaveOptionInDatabase('convertkit-mm-admin-notices');
	}
}
