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
class MailChimp extends Component
{
    // Public Methods
 // =========================================================================
 /*
  * @return mixed
  */
 /**
  * @return array<mixed, array<'id'|'name', mixed>>
  */
 public function getLists(): array
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

	/**
  * @return array<mixed, array<'id'|'name', mixed>>
  */
 public function getListsByEmail($email): array
	{
		$results = [];
		foreach ($this->request('GET', 'lists', ['email'=>$email])['body']['lists'] as $list)
		{
			$results[] = [
				'id' => $list['id'],
				'name' => $list['name'],
			];
		}

		return $results;
	}

	public function subscribe($listId, $email)
	{
		$params = [
			'email_address' => $email,
			'status' => 'subscribed',
		];

		$hash = md5($email);

		$response = $this->request('PUT', sprintf('lists/%s/members/%s', $listId, $hash), $params);

		if ($response['success'] && $response['statusCode'] === 200) {
			return ['status' => 'success'];
		} elseif ($response['success'] && $response['statusCode'] !== 200) {
			return ['status' => 'error', 'message' => $response['statusCode'].' '.$response['reason']];
		}else{
			return ['status' => 'error', 'message' => $response['reason']];
		}
	}

	public function unsubscribe($listId, $email)
	{
		$params = [
			'email_address' => $email,
			'status' => 'unsubscribed',
		];

		$hash = md5($email);

		$response = $this->request('PUT', sprintf('lists/%s/members/%s', $listId, $hash), $params);

		if ($response['success'] && $response['statusCode'] === 200) {
			return ['status' => 'success'];
		} elseif ($response['success'] && $response['statusCode'] !== 200) {
			return ['status' => 'error', 'message' => $response['statusCode'].' '.$response['reason']];
		}else{
			return ['status' => 'error', 'message' => $response['reason']];
		}
	}

	private function request(string $type = 'GET', string $uri = '', $params = null): array
    {
        $settings = EmailSubscriptions::$plugin->getSettings();

        // Get datacenter from end of api key
        $explode = explode('-', $settings->apiKey);
        $dc = end($explode);

        $client = new \GuzzleHttp\Client([
          'base_uri' => 'https://'.$dc.'.api.mailchimp.com/3.0/',
          'http_errors' => false,
          'timeout' => 10,
          'auth' => ['plugin', $settings->apiKey]
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
