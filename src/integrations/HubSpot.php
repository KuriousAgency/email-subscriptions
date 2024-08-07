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
 /**
  * @return mixed[]
  */
 public function getLists($offset=0): array
	{
		$results = [];
		$data = $this->request('GET', 'lists', ['offset'=>$offset,'count'=>100])['body'];

		if( (is_array($data)) && (array_key_exists('lists',$data)) ) {
			foreach ($data['lists'] as $list)
			{
				if (!$list['dynamic']) {
					$results[] = [
						'id' => $list['listId'],
						'name' => $list['name'],
					];
				}
			}

			if ($data['has-more'] && $data['offset'] < 21) {
				$results = array_merge($results, $this->getLists($data['offset']));
			}
		}	
		
		return $results;
	}

	public function getListsByEmail(string $email)
	{
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

		// create contact first, 409 response if contact exists
		$response = $this->request('POST', "contact", [
			'properties' => [
				["property" => "email", "value" => $email],
			]
		]);
		

		$params = [
			'emails' => [$email],
		];

		$response = $this->request('POST', sprintf('lists/%s/add', $listId), $params);

		if ($response['success'] && $response['statusCode'] === 200) {
			return ['status' => 'success'];
		} elseif ($response['success'] && $response['statusCode'] !== 200) {
			return ['status' => 'error', 'message' => $response['statusCode'].' '.$response['reason']];
		}else{
			return ['status' => 'error', 'message' => $response['reason']];
		}
	}

	public function unsubscribe($listId, string $email)
	{
		$contact = $this->request('GET', 'contact/email/'.$email.'/profile')['body'];
		if (isset($contact['status']) && $contact['status'] == 'error') {
			return ['status' => 'success'];
		}
		
		$params = [
			'vids' => [$contact['vid']],
		];

		$response = $this->request('POST', sprintf('lists/%s/remove/', $listId), $params);

		if ($response['success'] && $response['statusCode'] === 200) {
			return ['status' => 'success'];
		} elseif ($response['success'] && $response['statusCode'] !== 200) {
			return ['status' => 'error', 'message' => $response['statusCode'].' '.$response['reason']];
		}else{
			return ['status' => 'error', 'message' => $response['reason']];
		}
	}

	private function request(string $type = 'GET', string $uri = '', array $params = [])
    {
        $settings = EmailSubscriptions::$plugin->getSettings();

		$apiKey = Craft::parseEnv($settings->apiKey);

        $params['hapikey'] = $apiKey;

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
					'query' => ['hapikey' => $apiKey],
					'json' => $params
				]);
			}

          return [
            'success' => true,
            'statusCode' => $response->getStatuscode(),
            'reason' => $response->getReasonPhrase(),
            'body' => json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR)
          ];

        } catch (\Exception $exception) {

          return [
            'success' => false,
            'reason' => $exception->getMessage()
          ];

        }
    }
}
