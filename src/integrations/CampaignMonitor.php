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
class CampaignMonitor extends Component
{

	private $_apiKey;

	private $_clientId;

	// Public Methods
	// =========================================================================


	public function init(): void
	{
		$settings = EmailSubscriptions::$plugin->getSettings();
		$this->_apiKey = Craft::parseEnv($settings->apiKey);
		$this->_clientId = $settings->accountId;
	}


    /*
  * @return mixed
  */
 /**
  * @return array<mixed, array<'id'|'name', mixed>>
  */
 public function getLists(): array
	{

		$results = [];
		$response = $this->request('GET', 'clients/'.$this->_clientId.'/lists');

		if ($response['success'] && $response['statusCode'] === 200) {

			foreach ($response['body'] as $list)
			{
				$results[] = [
					'id' => $list['ListID'],
					'name' => $list['Name'],
				];
			}
		}

		return $results;
	}

	/**
  * @return array<mixed, array<'id'|'name', mixed>>
  */
 public function getListsByEmail($email): array
	{
		$results = [];

		foreach ($this->request('GET', 'clients/'.$this->_clientId.'/listsforemail', ['email'=>$email])['body'] as $list)
		{
			if($list['SubscriberState'] == 'Active') {
				$results[] = [
					'id' => $list['ListID'],
					'name' => $list['ListName'],
				];
			}
		}

		return $results;
	}

	public function subscribe(string $listId, $email)
	{

		$subscribers[] = [
			'EmailAddress' => $email,
			'ConsentToTrack' => 'Yes'
		];

		$params = [
			'Subscribers'=> $subscribers,
			'Resubscribe' => true
		];

		$response = $this->request('POST', "subscribers/".$listId."/import", $params);

		if ($response['success'] && ($response['statusCode'] == 200 || $response['statusCode'] == 201)) {
			return ['status' => 'success'];
		} elseif ($response['success'] && $response['statusCode'] !== 200) {
			return ['status' => 'error', 'message' => $response['statusCode'].' '.$response['reason']];
		} else{
			return ['status' => 'error', 'message' => $response['reason']];
		}
	}

	public function unsubscribe($listId, $email)
	{
		$params = [
			'EmailAddress' => $email,
		];

		$response = $this->request('POST', sprintf('subscribers/%s/unsubscribe', $listId), $params);

		if($response['success'] && $response['statusCode'] === 200) {
			return ['status' => 'success'];
		} elseif ($response['success'] && $response['statusCode'] !== 200) {
			if(array_key_exists('Code', $response['body']) && $response['body']['Code'] == 203) {
				return ['status' => 'success'];
			}

			return ['status' => 'error', 'message' => $response['statusCode'].' '.$response['reason']];
		} else{
			return ['status' => 'error', 'message' => $response['reason']];
		}
	}

	private function request(string $type = 'GET', string $uri = '', $params = null)
    {

        $client = new \GuzzleHttp\Client([
          'base_uri' => 'https://api.createsend.com/api/v3.2/',
          'http_errors' => false,
          'timeout' => 10,
          'auth' => [$this->_apiKey,'']
		]);

		$uri .= '.json';

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

// https://kuriousagency.createsend.com/subscribers/listDetail.aspx?listID=16AFBFACFDBCCBFC
