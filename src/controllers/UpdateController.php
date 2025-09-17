<?php

/**
 * Email Subscriptions plugin for Craft CMS 3.x
 *
 * Allows subscribing and unsubscribing from 3rd party email lists.
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2018 Kurious Agency
 */

namespace kuriousagency\emailsubscriptions\controllers;

use kuriousagency\emailsubscriptions\EmailSubscriptions;
use kuriousagency\emailSubscriptions\services\EmailSubscriptionsService;

use Craft;
use craft\web\Controller;

/**
 * @author    Kurious Agency
 * @package   EmailSubscriptions
 * @since     0.0.1
 */
class UpdateController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected array|int|bool $allowAnonymous = ['index'];

    // Public Methods
    // =========================================================================
    public function actionIndex(): \yii\web\Response
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        $email = Craft::$app->getUser()->getIdentity()->email;

        $listIds = $request->getBodyParam('lists', []); // ? Craft::$app->security->validateData($request->post('lists')) : [];

        if (EmailSubscriptions::$plugin->service->update($email, $listIds)) {
            Craft::$app->session->setFlash('notice', 'Subscriptions updated.');
        }


        return $this->redirectToPostedUrl();
    }
}
