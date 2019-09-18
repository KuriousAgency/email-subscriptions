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
class ActiveCampaign extends Component
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
			$results[] = [
				'id' => $list['id'],
				'name' => $list['name'],
			];
		}
		return $results;
	}

	public function getListsByEmail($email)
	{
		
		$contactId = 0;
		$results = [];

		$lists = $this->getLists();

		$contactId = $this->_getContactIdByEmail($email);

		if($contactId) {
			foreach ($this->request('GET', 'contacts/'.$contactId.'/contactLists')['body']['contactLists'] as $list)
			{
				if($list['status'] == 1) {
					$results[] = [
						'id' => $list['list'],
						'name' => $lists[$list['list']]['name'],
					];
				}
			}
		}

		return $results;
	}

	public function subscribe($listId, $email)
	{

		// find existing contact or create a new one
		$params['contact'] = [
			'email' => $email,
		];
		$contactId = $this->request('POST', 'contact/sync', $params)['body']['contact']['id'];

		$params = [];
		$params['contactList'] = [
			'list'=>$listId,
			'contact'=>$contactId,
			'status' => 1
		];

		$response = $this->request('POST', 'contactLists', $params);

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
		$contactId = $this->_getContactIdByEmail($email);
		
		$params['contactList'] = [
			'list'=>$listId,
			'contact'=>$contactId,
			'status' => 2
		];

		$response = $this->request('POST', 'contactLists', $params);

		if ($response['success'] and $response['statusCode'] === 200) {
			return ['status' => 'success'];
		} elseif ($response['success'] and $response['statusCode'] !== 200) {
			return ['status' => 'error', 'message' => $response['statusCode'].' '.$response['reason']];
		}else{
			return ['status' => 'error', 'message' => $response['reason']];
		}
	}

	private function request($type = 'GET', $uri = '', $params = null)
    {
		$settings = EmailSubscriptions::$plugin->getSettings();

        $client = new \GuzzleHttp\Client([
          'base_uri' => 'https://'.$settings->accountId.'.api-us1.com/api/3/',
          'http_errors' => false,
		  'timeout' => 60,
		  'headers' => ['Api-Token' => $settings->apiKey]
        ]);

        try {

			if($type == 'GET'){
				$response = $client->request($type, $uri, [
					'query' => $params
				]);
			}else{
				$response = $client->request($type, $uri, [
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
	
	private function _getContactIdByEmail($email)
	{
		$contactId = 0;
		
		$params = [
			'email' => $email
		];
		$response = $this->request('GET', 'contacts',$params);

		// Craft::dd($response);
		if($contact = $response['body']['contacts']) {
			$contactId = $contact[0]['id'];
		}

		return $contactId;

	}
}
