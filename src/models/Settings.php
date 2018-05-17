<?php
/**
 * Email Subscriptions plugin for Craft CMS 3.x
 *
 * Allows subscribing and unsubscribing from 3rd party email lists.
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2018 Kurious Agency
 */

namespace kuriousagency\emailsubscriptions\models;

use kuriousagency\emailsubscriptions\EmailSubscriptions;

use Craft;
use craft\base\Model;

/**
 * @author    Kurious Agency
 * @package   EmailSubscriptions
 * @since     0.0.1
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
	public $service = null;
	public $apiKey = null;
	//public $terms = null;

	public $services = [
		'MailChimp' => 'MailChimp',
	];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['service','apiKey'], 'string'],
			//[['terms'], 'number', 'integerOnly' => true],
			[['service','apiKey'], 'required'],
        ];
    }
}
