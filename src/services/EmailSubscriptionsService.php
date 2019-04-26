<?php
/**
 * Email Subscriptions plugin for Craft CMS 3.x
 *
 * Allows subscribing and unsubscribing from 3rd party email lists.
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2018 Kurious Agency
 */

namespace kuriousagency\emailsubscriptions\services;

use kuriousagency\emailsubscriptions\EmailSubscriptions;

use kuriousagency\emailsubscriptions\integrations\MailChimp as mailchimp;

use Craft;
use craft\base\Component;

/**
 * @author    Kurious Agency
 * @package   EmailSubscriptions
 * @since     0.0.1
 */
class EmailSubscriptionsService extends Component
{
    // Public Methods
	// =========================================================================
	public $service = null;

	public function init()
	{
		parent::init();

		$settings = EmailSubscriptions::$plugin->getSettings();
		$className = "kuriousagency\\emailsubscriptions\\integrations\\".$settings->service;
		if ($settings->service) {
			$this->service = new $className();
		}
	}

	public function update($email, $ids)
	{
		$availableLists = $this->getLists();
		$availableListsIds = [];
		foreach($availableLists as $list)
		{
			$availableListsIds[] = $list['id'];
		}

		$responses = [];

		foreach($availableListsIds as $id)
		{
			if(in_array($id, $ids)){
				$responses[] = $this->subscribe($id, $email);
			}else{
				$responses[] = $this->unsubscribe($id, $email);
			}
		}

		$success = true;

		foreach($responses as $response)
		{
			if($response['status'] == 'error'){
				Craft::$app->getSession()->setError($response['message']);
				$success = false;
			}
		}

		return $success;
	}

    /*
     * @return mixed
     */
	public function getLists($showAll=false)
	{
		if (!$this->service) {
			return [];
		}
		$toShow = [];
		$settings = EmailSubscriptions::$plugin->getSettings();
		$selectedSubLists = str_replace( '_', '',$settings->selectedSubLists);
		
		if ($showAll) {
			return $this->service->getLists();
		} elseif (empty($selectedSubLists)) {
			return;
		} else {
			foreach ($this->service->getLists() as $list) {
				if (in_array($list['id'],$selectedSubLists)) {
					$toShow[] = $list;
				} 
			};
			if ($user = Craft::$app->getUser()) {
				foreach ($this->getListsByEmail($user->getIdentity()->email) as $list) {
					if (!in_array($list['id'],$selectedSubLists)) {
						$toShow[] = $list;
					} 
				};
			}
			return $toShow;
		}
		
	}

	public function getListsByEmail($email)
	{
		return $this->service->getListsByEmail($email);
	}

    public function subscribe($listId, $email)
    {
		return $this->service->subscribe($listId, $email);
	}
	
	public function unsubscribe($listId, $email)
	{
		return $this->service->unsubscribe($listId, $email);
	}
}
