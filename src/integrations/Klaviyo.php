<?php

/**
 * Email Subscriptions plugin for Craft CMS 3.x
 *
 * Allows subscribing and unsubscribing from 3rd party email lists.
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2018 Kurious Agency
 */

namespace kuriousagency\emailsubscriptions\integrations;

use kuriousagency\emailsubscriptions\EmailSubscriptions;

use Craft;
use craft\base\Component;
use craft\helpers\App;
use craft\helpers\DateTimeHelper;

use DateTime;

/**
 * @author    Kurious Agency
 * @package   EmailSubscriptions
 * @since     0.0.1
 */
class Klaviyo extends Component
{
	// Public Methods
	// =========================================================================
	/*
  * @return mixed
  */
	/**
	 * @return array
	 */
	public function getLists($offset = 0)
	{
		$results = [];

		$uri = 'lists';
		$lists = $this->request('GET', $uri)['body'];

		if (!isset($lists['data'])) {
			return $results;
		}

		foreach ($lists['data'] as $list) {
			$results[] = [
				'id' => $list['id'],
				'name' => $list['attributes']['name'],
			];
		}

		return $results;
	}

	/**
	 * @return array
	 */
	public function getListsByEmail($email)
	{
		$lists = [];
		$profileId = $this->getProfileIdByEmail($email);

		if (!$profileId) {
			return $lists;
		}

		$uri = 'profiles/' . $profileId . '/lists';
		$response = $this->request('GET', $uri)['body'];

		if (isset($response['data'])) {
			foreach ($response['data'] as $listData) {
				if ($listData['type'] == 'list') {
					$lists[] = [
						'id' => $listData['id'],
					];
				}
			}
		}

		return $lists;
	}

	public function subscribe($listId, $email)
	{
		$params = [
			"data" => [
				"type" => "profile-subscription-bulk-create-job",
				"attributes" => [
					"custom_source" => "Website Newsletter Sign up",
					"profiles" => [
						"data" => [
							[
								"type" => "profile",
								"attributes" => [
									"email" => $email,
									"subscriptions" => [
										"email" => [
											"marketing" => [
												"consent" => "SUBSCRIBED",
												"consented_at" => DateTimeHelper::toIso8601(new DateTime),
											]
										]
									]
								]
							]
						]
					]
				],
				"relationships" => [
					"list" => [
						"data" => [
							"type" => "list",
							"id" => $listId
						]
					]
				]
			]
		];

		// Craft::dd($params);

		$uri = 'profile-subscription-bulk-create-jobs';
		$response = $this->request('POST', $uri, ['json' => $params]);

		if ($response['success'] && $response['statusCode'] === 202) {
			return ['status' => 'success'];
		} elseif ($response['success'] && $response['statusCode'] !== 202) {
			return ['status' => 'error', 'message' => $response['statusCode'] . ' ' . $response['reason']];
		} else {
			return ['status' => 'error', 'message' => $response['reason']];
		}
	}

	public function unsubscribe($listId, $email)
	{
		$profileId = $this->getProfileIdByEmail($email);

		if (!$profileId) {
			return false;
		}

		$params = [
			'data' => [
				'type' => 'profile',
				'id' => $profileId,
			],
		];

		$uri = 'lists/' . $listId . '/relationships/profiles';
		$response = $this->request('DELETE', $uri, ['json' => $params]);


		if ($response['success'] && $response['statusCode'] === 204) {
			return ['status' => 'success'];
		} elseif ($response['success'] && $response['statusCode'] !== 204) {
			return ['status' => 'error', 'message' => $response['statusCode'] . ' ' . $response['reason']];
		} else {
			return ['status' => 'error', 'message' => $response['reason']];
		}
	}

	public function getProfileIdByEmail($email)
	{
		$params = [
			'filter' => 'equals(email,"' . $email . '")',
		];

		$uri = 'profiles';
		$response = $this->request('GET', $uri, ['query' => $params])['body'];

		$profileId = isset($response['data']['id']) ? $response['data']['id'] : null;

		return $profileId;
	}

	private function request(string $method = 'GET', string $uri = '', array $params = [])
	{
		$settings = EmailSubscriptions::$plugin->getSettings();

		$apiKey = App::parseEnv($settings->apiKey);

		$client = new \GuzzleHttp\Client([
			// 'base_uri' => 'https://a.klaviyo.com/api/v2/',
			'base_uri' => 'https://a.klaviyo.com/api/',
			'http_errors' => false,
			'timeout' => 10,
		]);

		$params = array_merge_recursive([
			'headers' => [
				'Authorization' => 'Klaviyo-API-Key ' . $apiKey,
				'Revision' => '2024-02-15',
				'Accept' => 'application/json',
			]
		], $params);

		try {

			$response = $client->request($method, $uri, $params);

			return [
				'success' => true,
				'statusCode' => $response->getStatuscode(),
				'reason' => $response->getReasonPhrase(),
				'body' => json_decode($response->getBody(), true)
			];
		} catch (\Exception $exception) {

			return [
				'success' => false,
				'reason' => $exception->getMessage()
			];
		}
	}
}
