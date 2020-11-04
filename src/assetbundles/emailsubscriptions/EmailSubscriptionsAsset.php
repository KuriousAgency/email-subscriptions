<?php
/**
 * Email Subscriptions plugin for Craft CMS 3.x
 *
 * Allows subscribing and unsubscribing from 3rd party email lists.
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2018 Kurious Agency
 */

namespace kuriousagency\emailsubscriptions\assetbundles\emailsubscriptions;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Kurious Agency
 * @package   EmailSubscriptions
 * @since     0.0.1
 */
class EmailSubscriptionsAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@kuriousagency/emailsubscriptions/assetbundles/emailsubscriptions/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/EmailSubscriptions.js',
        ];

        $this->css = [
            'css/EmailSubscriptions.css',
        ];

        parent::init();
    }
}
