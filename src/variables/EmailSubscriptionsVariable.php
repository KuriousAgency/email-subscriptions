<?php
/**
 * Email Subscriptions plugin for Craft CMS 3.x
 *
 * Allows subscribing and unsubscribing from 3rd party email lists.
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2018 Kurious Agency
 */

namespace kuriousagency\emailsubscriptions\variables;

use kuriousagency\emailsubscriptions\EmailSubscriptions;

use Craft;

/**
 * @author    Kurious Agency
 * @package   EmailSubscriptions
 * @since     0.0.1
 */
class EmailSubscriptionsVariable
{
    // Public Methods
    // =========================================================================

    /**
     * @param null $optional
     * @return string
     */
    public function lists($showAll=false)
    {
        return EmailSubscriptions::$plugin->service->getLists($showAll);
	}
	
	public function getListsByEmail($email=null)
	{
		if(!$email){
			$email = Craft::$app->getUser()->getIdentity()->email;
		}

		//Craft::dd($email);

		return EmailSubscriptions::$plugin->service->getListsByEmail($email);
	}

	public function service()
	{
		return EmailSubscriptions::$plugin->service->service;
	}
}
