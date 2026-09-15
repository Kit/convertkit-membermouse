<?php
namespace Tests\Support\Helper;

/**
 * Helper methods and actions related to the ConvertKit API,
 * which are then available using $I->{yourFunctionName}.
 *
 * @since   1.2.0
 */
class KitAPI extends \Codeception\Module
{
	/**
	 * Installs the Kit API recorder mu-plugin, and clears any previously recorded
	 * requests, before each test runs.
	 *
	 * @since   1.4.7
	 *
	 * @param   \Codeception\TestInterface $test   Test.
	 */
	public function _before(\Codeception\TestInterface $test) // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
	{
		$this->getModule('lucatume\WPBrowser\Module\WPFilesystem')->haveMuPlugin(
			'kit-api-recorder.php',
			(string) file_get_contents(__DIR__ . '/../mu-plugins/kit-api-recorder.php') // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		);

		$this->getModule('lucatume\WPBrowser\Module\WPDb')->haveOptionInDatabase('kit_api_log', []);
	}

	/**
	 * Returns an encoded `state` parameter compatible with OAuth.
	 *
	 * @since   1.3.0
	 *
	 * @param   string $returnTo   Return URL.
	 * @param   string $clientID   OAuth Client ID.
	 * @return  string
	 */
	public function apiEncodeState($returnTo, $clientID)
	{
		$str = json_encode( // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
			array(
				'return_to' => $returnTo,
				'client_id' => $clientID,
			)
		);

		// Encode to Base64 string.
		$str = base64_encode( $str ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions

		// Convert Base64 to Base64URL by replacing “+” with “-” and “/” with “_”.
		$str = strtr( $str, '+/', '-_' );

		// Remove padding character from the end of line.
		$str = rtrim( $str, '=' );

		return $str;
	}

	/**
	 * Returns the Kit API requests the Plugin made during this test, optionally
	 * filtered by method, path and email address.
	 *
	 * @since   1.4.7
	 *
	 * @param   EndToEndTester $I              Tester.
	 * @param   bool|string    $method         HTTP method (GET,POST,PUT,DELETE).
	 * @param   bool|string    $path           Request path, excluding the API version e.g. `subscribers`.
	 * @param   bool|string    $emailAddress   Email address in the request body.
	 * @return  array
	 */
	public function grabKitAPIRequests($I, $method = false, $path = false, $emailAddress = false)
	{
		$log = $I->grabOptionFromDatabase('kit_api_log');

		if ( ! is_array($log)) {
			return [];
		}

		return array_values(
			array_filter(
				$log,
				function ($request) use ($method, $path, $emailAddress) {
					if ($method && $request['method'] !== $method) {
						return false;
					}
					if ($path && $request['path'] !== $path) {
						return false;
					}
					if ($emailAddress && ( ! array_key_exists('email_address', $request['body']) || $request['body']['email_address'] !== $emailAddress )) {
						return false;
					}

					return true;
				}
			)
		);
	}

	/**
	 * Returns the first Kit API request the Plugin made during this test that matches
	 * the given method, path and email address, waiting for it to be made.
	 *
	 * @since   1.4.7
	 *
	 * @param   EndToEndTester $I              Tester.
	 * @param   string         $method         HTTP method (GET,POST,PUT,DELETE).
	 * @param   string         $path           Request path, excluding the API version e.g. `subscribers`.
	 * @param   bool|string    $emailAddress   Email address in the request body.
	 * @return  bool|array
	 */
	public function grabKitAPIRequest($I, $method, $path, $emailAddress = false)
	{
		// The request is made by WordPress when the member is created or updated, which may
		// not have completed when this is called.
		return $this->retryUntil(
			function () use ($I, $method, $path, $emailAddress) {
				$requests = $this->grabKitAPIRequests($I, $method, $path, $emailAddress);

				return count($requests) ? $requests[0] : false;
			},
			10,
			1
		);
	}

	/**
	 * Returns the Kit API request the Plugin made that created or updated the subscriber
	 * with the given email address, waiting for it to be made.
	 *
	 * The Plugin creates a subscriber with a POST request. When a member's email address is
	 * changed in MemberMouse, the Plugin instead updates the existing subscriber with a PUT
	 * request, so both are checked.
	 *
	 * @since   1.4.7
	 *
	 * @param   EndToEndTester $I              Tester.
	 * @param   string         $emailAddress   Email Address.
	 * @return  bool|array
	 */
	public function grabKitAPISubscriberRequest($I, $emailAddress)
	{
		return $this->retryUntil(
			function () use ($I, $emailAddress) {
				// Check if the Plugin created the subscriber.
				$requests = $this->grabKitAPIRequests($I, 'POST', 'subscribers', $emailAddress);
				if (count($requests)) {
					return $requests[0];
				}

				// Check if the Plugin updated an existing subscriber with this email address.
				$requests = array_filter(
					$this->grabKitAPIRequests($I, 'PUT', false, $emailAddress),
					function ($request) {
						return strpos($request['path'], 'subscribers/') === 0;
					}
				);

				// Use the most recent update request, as a subscriber may be updated more than once.
				return count($requests) ? end($requests) : false;
			},
			10,
			1
		);
	}

	/**
	 * Check the given email address exists as a subscriber.
	 *
	 * The Plugin's request to create or update the subscriber is used to determine the subscriber ID,
	 * as querying the API by email address is subject to eventual consistency. Querying by subscriber
	 * ID returns strongly consistent results.
	 *
	 * @see     https://developers.kit.com/api-reference/eventual-consistency
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I              Tester.
	 * @param   string         $emailAddress   Email Address.
	 * @param   bool|string    $firstName      First Name.
	 * @return  array                           Subscriber.
	 */
	public function apiCheckSubscriberExists($I, $emailAddress, $firstName = false)
	{
		// Get the request the Plugin made to create or update the subscriber.
		$request = $this->grabKitAPISubscriberRequest($I, $emailAddress);

		// Check the Plugin created or updated the subscriber.
		$I->assertNotFalse(
			$request,
			sprintf('The Plugin did not send a request to create or update the subscriber %s.', $emailAddress)
		);
		$I->assertLessThan(
			300,
			$request['code'],
			sprintf('The API returned a %s response when the Plugin created or updated the subscriber %s.', $request['code'], $emailAddress)
		);

		// Fetch the subscriber by their ID, which returns strongly consistent results.
		$results = $this->apiRequest('subscribers/' . $request['response']['subscriber']['id'], 'GET');

		// Check the subscriber matches the email address.
		$I->assertEquals($emailAddress, $results['subscriber']['email_address']);

		// If a name is supplied, confirm it matches.
		if ($firstName) {
			$I->assertEquals($firstName, $results['subscriber']['first_name']);
		}

		return $results['subscriber'];
	}

	/**
	 * Check the given subscriber ID has been assigned to the given tag ID.
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I             Tester.
	 * @param   int            $subscriberID  Subscriber ID.
	 * @param   int            $tagID         Tag ID.
	 */
	public function apiCheckSubscriberHasTag($I, $subscriberID, $tagID)
	{
		// Wait for the tag to be assigned to the subscriber, as list endpoints are eventually consistent.
		$tag = $this->retryUntil(
			function () use ($subscriberID, $tagID) {
				$results = $this->apiRequest(
					'subscribers/' . $subscriberID . '/tags',
					'GET'
				);

				// Return the tag only if it's assigned to the subscriber, so
				// retryUntil() will keep trying otherwise.
				foreach ($results['tags'] as $tag) {
					if ( (int) $tag['id'] === (int) $tagID) {
						return $tag;
					}
				}

				return false;
			}
		);

		// Assert the subscriber has the tag.
		$I->assertNotFalse(
			$tag,
			sprintf('Subscriber %s was not assigned Tag %s in time.', $subscriberID, $tagID)
		);
	}

	/**
	 * Check the given subscriber ID has no tags assigned.
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I             Tester.
	 * @param   int            $subscriberID  Subscriber ID.
	 */
	public function apiCheckSubscriberHasNoTags($I, $subscriberID)
	{
		// Wait for the subscriber to have no tags, as list endpoints are eventually consistent.
		$result = $this->retryUntil(
			function () use ($subscriberID) {
				$results = $this->apiRequest(
					'subscribers/' . $subscriberID . '/tags',
					'GET'
				);

				// Return the tags only if none are assigned, so retryUntil() will keep trying otherwise.
				// The result is wrapped in an array, as an empty array is falsy.
				return count($results['tags']) === 0 ? array( 'tags' => $results['tags'] ) : false;
			}
		);

		// Assert the subscriber has no tags.
		$I->assertNotFalse(
			$result,
			sprintf('Subscriber %s still has tags assigned.', $subscriberID)
		);
	}

	/**
	 * Check the given subscriber ID has been assigned to the given number
	 * of tags.
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I             Tester.
	 * @param   int            $subscriberID  Subscriber ID.
	 * @param   int            $numberOfTags  Number of tags.
	 */
	public function apiCheckSubscriberTagCount($I, $subscriberID, $numberOfTags)
	{
		// Wait for the tags to be assigned to the subscriber, as list endpoints are eventually consistent.
		$result = $this->retryUntil(
			function () use ($subscriberID, $numberOfTags) {
				$results = $this->apiRequest(
					'subscribers/' . $subscriberID . '/tags',
					'GET'
				);

				// Return the tags only if the expected number are assigned, so retryUntil() will keep
				// trying otherwise. The result is wrapped in an array, as an empty array is falsy.
				return count($results['tags']) === (int) $numberOfTags ? array( 'tags' => $results['tags'] ) : false;
			}
		);

		// Assert the subscriber has the expected number of tags.
		$I->assertNotFalse(
			$result,
			sprintf('Subscriber %s was not assigned %s tag(s) in time.', $subscriberID, $numberOfTags)
		);
	}

	/**
	 * Removes the given tag ID from the given subscriber ID.
	 *
	 * @since   1.2.1
	 *
	 * @param   EndToEndTester $I             Tester.
	 * @param   int            $subscriberID  Subscriber ID.
	 * @param   int            $tagID         Tag ID.
	 */
	public function apiSubscriberRemoveTag($I, $subscriberID, $tagID)
	{
		$this->apiRequest(
			'tags/' . $tagID . '/subscribers/' . $subscriberID,
			'DELETE'
		);
	}

	/**
	 * Check the given email address does not exists as a subscriber.
	 *
	 * The Plugin's requests are inspected, instead of querying the API by email address, as
	 * querying by email address is subject to eventual consistency and would therefore return
	 * no subscriber even when the Plugin created one.
	 *
	 * @see     https://developers.kit.com/api-reference/eventual-consistency
	 *
	 * @since   1.2.0
	 *
	 * @param   EndToEndTester $I             Tester.
	 * @param   string         $emailAddress   Email Address.
	 */
	public function apiCheckSubscriberDoesNotExist($I, $emailAddress)
	{
		// Get any requests the Plugin made to create the subscriber.
		$requests = $this->grabKitAPIRequests($I, 'POST', 'subscribers', $emailAddress);

		// Check the Plugin did not create the subscriber.
		$I->assertCount(
			0,
			$requests,
			sprintf('The Plugin sent a request to create the subscriber %s.', $emailAddress)
		);
	}

	/**
	 * Check the subscriber array's custom field data is valid.
	 *
	 * @since   1.2.8
	 *
	 * @param   EndToEndTester $I             Tester.
	 * @param   array          $subscriber     Subscriber from API.
	 * @param   array          $customFields   Custom Field key/value pairs to check.
	 */
	public function apiCustomFieldDataIsValid($I, $subscriber, $customFields)
	{
		foreach ($customFields as $key => $value) {
			$I->assertEquals($subscriber['fields'][ $key ], $value);
		}
	}

	/**
	 * Check the subscriber array's custom field data is empty.
	 *
	 * @since   1.2.8
	 *
	 * @param   EndToEndTester $I             Tester.
	 * @param   array          $subscriber        Subscriber from API.
	 * @param   array          $customFieldKeys   Custom Field Keys to check they are empty.
	 */
	public function apiCustomFieldDataIsEmpty($I, $subscriber, $customFieldKeys)
	{
		foreach ($customFieldKeys as $key) {
			$I->assertEquals($subscriber['fields'][ $key ], '');
		}
	}

	/**
	 * Sends a request to the ConvertKit API, typically used to read an endpoint to confirm
	 * that data in an End to End Test was added/edited/deleted successfully.
	 *
	 * @since   1.2.0
	 *
	 * @param   string $endpoint   Endpoint.
	 * @param   string $method     Method (GET|POST|PUT).
	 * @param   array  $params     Endpoint Parameters.
	 */
	public function apiRequest($endpoint, $method = 'GET', $params = array())
	{
		// Send request.
		$client = new \GuzzleHttp\Client();
		switch ($method) {
			case 'GET':
				$result = $client->request(
					$method,
					'https://api.kit.com/v4/' . $endpoint . '?' . http_build_query($params),
					[
						'headers' => [
							'Authorization' => 'Bearer ' . $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
						],
						'timeout' => 5,
					]
				);
				break;

			default:
				$result = $client->request(
					$method,
					'https://api.kit.com/v4/' . $endpoint,
					[
						'headers' => [
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json; charset=utf-8',
							'Authorization' => 'Bearer ' . $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
						],
						'timeout' => 5,
						'body'    => (string) json_encode($params), // phpcs:ignore WordPress.WP.AlternativeFunctions
					]
				);
				break;
		}

		// Return JSON decoded response.
		return json_decode($result->getBody()->getContents(), true);
	}

	/**
	 * Repeatedly invokes the given callback until it returns a truthy value, or
	 * the maximum number of attempts is reached.
	 *
	 * Use this to wrap API checks that can be flaky due to ingestion lag at
	 * Kit's end (e.g. a tag assigned to a subscriber isn't always immediately
	 * returned by the `subscribers/{id}/tags` endpoint).
	 *
	 * @since   1.4.3
	 *
	 * @param   callable $callback   Callback to invoke. Should return the value
	 *                                to use, or false/null to indicate the
	 *                                check has not yet succeeded.
	 * @param   int      $attempts   Maximum number of attempts.
	 * @param   int      $delay      Seconds to wait between attempts.
	 * @return  mixed                The truthy value returned by $callback, or
	 *                                false if all attempts are exhausted.
	 */
	private function retryUntil(callable $callback, $attempts = 4, $delay = 3)
	{
		for ($i = 0; $i < $attempts; $i++) {
			$result = $callback();
			if ($result) {
				return $result;
			}

			// Don't sleep after the final attempt.
			if ($i < $attempts - 1) {
				sleep($delay);
			}
		}

		return false;
	}
}
