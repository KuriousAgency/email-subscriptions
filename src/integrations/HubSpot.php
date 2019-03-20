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
class HubSpot extends Component
{
    // Public Methods
    // =========================================================================

    /*
     * @return mixed
     */
	public function getLists()
	{
		$results = [];
		foreach ($this->request('GET', 'lists')['body']['lists'] as $list)
		{
			if (!$list['dynamic']) {
				$results[] = [
					'id' => $list['listId'],
					'name' => $list['name'],
				];
			}
		}
		return $results;
	}

	public function getListsByEmail($email)
	{
		$results = [];
		//$email = 'testingapis@hubspot.com';
		
		$contact = $this->request('GET', 'contact/email/'.$email.'/profile')['body'];
		if (isset($contact['status']) && $contact['status'] == 'error') {
			return [];
		}
		//Craft::dd($contact);
		$ids = [];
		foreach ($contact['list-memberships'] as $list)
		{
			$ids[] = $list['static-list-id'];
		}
		
		$lists = $this->getLists();

		foreach($lists as $key => $list)
		{
			if (!in_array($list['id'], $ids)) {
				unset($lists[$key]);
			}
		}

		return $lists;
	}

	public function subscribe($listId, $email)
	{
		//$email = 'testingapis@hubspot.com';
		$params = [
			'emails' => [$email],
		];

		$response = $this->request('POST', "lists/$listId/add", $params);

		if ($response['success'] and $response['statusCode'] === 200) {
			return ['status' => 'success'];
		} elseif ($response['success'] and $response['statusCode'] !== 200) {
			return ['status' => 'error', 'message' => $response['statusCode'].' '.$response['reason']];
		}else{
			return ['status' => 'error', 'message' => $response['reason']];
		}
	}

	public function unsubscribe($listId, $email)
	{
		$contact = $this->request('GET', 'contact/email/'.$email.'/profile')['body'];
		if (isset($contact['status']) && $contact['status'] == 'error') {
			return ['status' => 'success'];
		}
		
		$params = [
			'vids' => [$contact['vid']],
		];

		$response = $this->request('POST', "lists/$listId/remove/", $params);

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

        $params['hapikey'] = $settings->apiKey;

        $client = new \GuzzleHttp\Client([
          'base_uri' => 'https://api.hubapi.com/contacts/v1/',
          'http_errors' => false,
          'timeout' => 10,
        ]);

        try {

			if($type == 'GET'){
				$response = $client->request($type, $uri, [
					'query' => $params
				]);
			}else{
				$response = $client->request($type, $uri, [
					'query' => ['hapikey' => $settings->apiKey],
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
