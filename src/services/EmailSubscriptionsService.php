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
		$this->service = new $className();
	}

    /*
     * @return mixed
     */
	public function getLists()
	{
		return $this->service->getLists();
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
