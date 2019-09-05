<?php
/**
 * Email Subscriptions plugin for Craft CMS 3.x
 *
 * Allows subscribing and unsubscribing from 3rd party email lists.
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2018 Kurious Agency
 */

namespace kuriousagency\emailsubscriptions;

use kuriousagency\emailsubscriptions\services\Service;
use kuriousagency\emailsubscriptions\variables\EmailSubscriptionsVariable;
use kuriousagency\emailsubscriptions\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterUrlRulesEvent;
use craft\elements\User;

use yii\base\Event;

/**
 * Class EmailSubscriptions
 *
 * @author    Kurious Agency
 * @package   EmailSubscriptions
 * @since     0.0.1
 *
 * @property  Service $emailSubscriptionsService
 */
class EmailSubscriptions extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var EmailSubscriptions
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
	public $schemaVersion = '0.0.1';
	
	public $services = [
		'MailChimp' => 'MailChimp',
		'HubSpot' => 'HubSpot',
		'Klaviyo' => 'Klaviyo',
	];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;


		
		Event::on(User::class, User::EVENT_AFTER_SAVE, function(Event $e){
			$user = $e->sender;
			$lists = Craft::$app->getRequest()->getParam('lists', []);
			
			if (count($lists)) {
				$this->service->update($user->email, $lists);
			}
		});

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('emailSubscriptions', EmailSubscriptionsVariable::class);
            }
        );

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                }
            }
		);
		
		//events
		//save user. -> check for emailSubscriptions[service] fields
		//delete user.

        Craft::info(
            Craft::t(
                'email-subscriptions',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'email-subscriptions/settings',
            [
				'settings' => $this->getSettings(),
				'services' => $this->services,
            ]
        );
    }
}
