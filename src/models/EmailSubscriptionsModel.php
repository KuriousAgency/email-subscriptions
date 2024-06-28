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
class EmailSubscriptionsModel extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
	public $email;

	public $listId;

	public $terms;

	public $action;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
			['email', 'string'],
			['listId', 'number'],
			['terms', 'string'],
			['action', 'string'],
        ];
    }
}
