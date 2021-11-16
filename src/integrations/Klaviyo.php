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
	public function getLists($offset=0)
	{
		$results = [];

		foreach ($this->request('GET', 'lists')['body'] as $list)
		{
			$results[] = [
				'id' => $list['list_id'],
				'name' => $list['list_name'],
			];
		}
		return $results;
	}

	public function getListsByEmail($email)
	{
		$lists = [];
		$params = [
			'emails' => [$email],
		];

		foreach($this->getLists() as $list) {

			$listId = $list['id'];

			$response = $this->request('GET', "list/$listId/subscribe", $params)['body'];

			if($response) {
				$lists[] = [
					'id'=>$listId,
				];
			}
		}

		return $lists;
	}

	public function subscribe($listId, $email)
	{
		// check if email already in list
		$params = [
			'emails' => [$email],
		];
		$response = $this->request('GET', "list/$listId/subscribe", $params);
		
		// if email not already in list subscribe
		if(!$response['body']) {

			$params = [
				'profiles' => [
					'email' => $email,
					'$consent' => 'email',
				]
			];

			$response = $this->request('POST', "list/$listId/subscribe", $params);

		}

		if ($response['success'] and $response['statusCode'] === 200) {
			return ['status' => 'success'];
		} elseif ($response['success'] and $response['statusCode'] !== 200) {
			return ['status' => 'error', 'message' => $response['statusCode'].' '.$response['reason']];
		} else{
			return ['status' => 'error', 'message' => $response['reason']];
		}
	}

	public function unsubscribe($listId, $email)
	{
		$params = [
			'emails' => [$email],
		];

		$response = $this->request('DELETE', "list/$listId/subscribe", $params);

		if ($response['success'] and $response['statusCode'] === 200) {
			return ['status' => 'success'];
		} elseif ($response['success'] and $response['statusCode'] !== 200) {
			return ['status' => 'error', 'message' => $response['statusCode'].' '.$response['reason']];
		}else{
			return ['status' => 'error', 'message' => $response['reason']];
		}
	}

	private function request($type = 'GET', $uri = '', $params = [])
    {
        $settings = EmailSubscriptions::$plugin->getSettings();

		$apiKey = Craft::parseEnv($settings->apiKey);

        $params['api_key'] = $apiKey;

        $client = new \GuzzleHttp\Client([
          'base_uri' => 'https://a.klaviyo.com/api/v2/',
          'http_errors' => false,
          'timeout' => 10,
        ]);

        try {

			if($type == 'GET'){
				$response = $client->request($type, $uri, [
					'query' => ['api_key' => $apiKey],
					'json' => $params
				]);
			}else{
				$response = $client->request($type, $uri, [
					'query' => ['api_key' => $apiKey],
					'json' => $params
				]);
			}

          return [
            'success' => true,
            'statusCode' => $response->getStatuscode(),
            'reason' => $response->getReasonPhrase(),
            'body' => json_decode($response->getBody(), true)
          ];

        } catch (\Exception $e) {

          return [
            'success' => false,
            'reason' => $e->getMessage()
          ];

        }
    }
}
