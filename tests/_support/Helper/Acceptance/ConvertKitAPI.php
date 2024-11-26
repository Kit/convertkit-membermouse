<?php
namespace Helper\Acceptance;

/**
 * Helper methods and actions related to the ConvertKit API,
 * which are then available using $I->{yourFunctionName}.
 *
 * @since   1.2.0
 */
class ConvertKitAPI extends \Codeception\Module
{
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
	 * Check the given email address exists as a subscriber.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I              AcceptanceTester.
	 * @param   string           $emailAddress   Email Address.
	 * @param   bool|string      $firstName      First Name.
	 */
	public function apiCheckSubscriberExists($I, $emailAddress, $firstName = false)
	{
		// Run request.
		$results = $this->apiRequest(
			'subscribers',
			'GET',
			[
				'email_address'       => $emailAddress,
				'include_total_count' => true,
			]
		);

		// Check at least one subscriber was returned and it matches the email address.
		$I->assertGreaterThan(0, $results['pagination']['total_count']);
		$I->assertEquals($emailAddress, $results['subscribers'][0]['email_address']);

		// If a name is supplied, confirm it matches.
		if ($firstName) {
			$I->assertEquals($firstName, $results['subscribers'][0]['first_name']);
		}

		return $results['subscribers'][0];
	}

	/**
	 * Check the given subscriber ID has been assigned to the given tag ID.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   int              $subscriberID  Subscriber ID.
	 * @param   int              $tagID         Tag ID.
	 */
	public function apiCheckSubscriberHasTag($I, $subscriberID, $tagID)
	{
		// Run request.
		$results = $this->apiRequest(
			'subscribers/' . $subscriberID . '/tags',
			'GET'
		);

		// Confirm the tag has been assigned to the subscriber.
		$I->assertEquals($tagID, $results['tags'][0]['id']);
	}

	/**
	 * Check the given subscriber ID has no tags assigned.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   int              $subscriberID  Subscriber ID.
	 */
	public function apiCheckSubscriberHasNoTags($I, $subscriberID)
	{
		// Run request.
		$results = $this->apiRequest(
			'subscribers/' . $subscriberID . '/tags',
			'GET'
		);

		// Confirm no tags have been assigned to the subscriber.
		$I->assertCount(0, $results['tags']);
	}

	/**
	 * Check the given subscriber ID has been assigned to the given number
	 * of tags.
	 *
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   int              $subscriberID  Subscriber ID.
	 * @param   int              $numberOfTags  Number of tags.
	 */
	public function apiCheckSubscriberTagCount($I, $subscriberID, $numberOfTags)
	{
		// Run request.
		$results = $this->apiRequest(
			'subscribers/' . $subscriberID . '/tags',
			'GET'
		);

		// Confirm the correct number of tags have been assigned to the subscriber.
		$I->assertEquals($numberOfTags, count($results['tags']));
	}

	/**
	 * Removes the given tag ID from the given subscriber ID.
	 *
	 * @since   1.2.1
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   int              $subscriberID  Subscriber ID.
	 * @param   int              $tagID         Tag ID.
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
	 * @since   1.2.0
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   string           $emailAddress   Email Address.
	 */
	public function apiCheckSubscriberDoesNotExist($I, $emailAddress)
	{
		// Run request.
		$results = $this->apiRequest(
			'subscribers',
			'GET',
			[
				'email_address'       => $emailAddress,
				'include_total_count' => true,
			]
		);

		// Check no subscribers are returned by this request.
		$I->assertEquals(0, $results['pagination']['total_count']);
	}

	/**
	 * Check the subscriber array's custom field data is valid.
	 *
	 * @since   1.2.8
	 *
	 * @param   AcceptanceTester $I              AcceptanceTester.
	 * @param   array            $subscriber     Subscriber from API.
	 * @param   array            $customFields   Custom Field key/value pairs to check.
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
	 * @param   AcceptanceTester $I                 AcceptanceTester.
	 * @param   array            $subscriber        Subscriber from API.
	 * @param   array            $customFieldKeys   Custom Field Keys to check they are empty.
	 */
	public function apiCustomFieldDataIsEmpty($I, $subscriber, $customFieldKeys)
	{
		foreach ($customFieldKeys as $key) {
			$I->assertEquals($subscriber['fields'][ $key ], '');
		}
	}

	/**
	 * Sends a request to the ConvertKit API, typically used to read an endpoint to confirm
	 * that data in an Acceptance Test was added/edited/deleted successfully.
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
							'timeout'       => 5,
						],
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
							'timeout'       => 5,
						],
						'body'    => (string) json_encode($params), // phpcs:ignore WordPress.WP.AlternativeFunctions
					]
				);
				break;
		}

		// Return JSON decoded response.
		return json_decode($result->getBody()->getContents(), true);
	}
}
