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
	public function getLists()
	{
		return $this->request('GET', 'lists')['body']['lists'];
	}

	public function getListsByEmail($email)
	{
		return $this->request('GET', 'lists', ['email'=>$email])['body']['lists'];
	}

	public function subscribe($listId, $email)
	{
		$params = [
			'email_address' => $email,
			'status' => 'subscribed',
		];

		$hash = md5($email);

		$response = $this->request('PUT', "lists/$listId/members/$hash", $params);

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
		$params = [
			'email_address' => $email,
			'status' => 'unsubscribed',
		];

		$hash = md5($email);

		$response = $this->request('PUT', "lists/$listId/members/$hash", $params);

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
