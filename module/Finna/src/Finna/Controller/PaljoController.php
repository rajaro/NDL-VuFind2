<?php
/**
 * Paljo Controller
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2020.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Controller
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace Finna\Controller;

use VuFind\Exception\Mail as MailException;

/**
 * Paljo controller
 *
 * @category VuFind
 * @package  Controller
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class PaljoController extends \VuFind\Controller\AbstractBase
{
    /**
     * Paljo subscription action
     *
     * @return \Laminas\View
     */
    public function subscriptionAction()
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirect()->toRoute(
                'default', ['controller' => 'MyResearch', 'action' => 'Login']
            );
        }
        $id = $this->params()->fromQuery('imageId', '');
        $organisationId = $this->params()->fromQuery('organisationId', '');
        $recordId = $this->params()->fromQuery('recordId', '');

        if ($user->getPaljoId() === null) {
            $view = $this->createViewModel();
            $view->setTemplate('RecordDriver/SolrLido/paljo-account-creation');
        } else {
            $paljo = $this->serviceLocator->get(\Finna\Service\PaljoService::class);
            $prices = $paljo->getImagePrice($id, $organisationId);
            $driver = $this->getRecordLoader()->load($recordId, 'Solr', true);
            $view = $this->createViewModel(
                [
                    'driver' => $driver, 'id' => $id,
                    'recordId' => $recordId, 'prices' => $prices,
                    'organisationId' => $organisationId
                ]
            );
            $view->setTemplate('RecordDriver/SolrLido/paljo-subscribe');
        }
        return $view;
    }

    /**
     * Paljo account creation action
     *
     * @return \Laminas\View
     */
    public function paljoAccountCreationAction()
    {
        $user = $this->getUser();
        $email = $this->params()->fromPost('email', '');
        try {
            $this->sendVerificationEmail($user, $email);
        } catch (\Exception $e) {
            return var_dump($e->getMessage());
        }
        $flashMessage = 'paljo_email_verification_sent';
        $this->flashMessenger()->addMessage($flashMessage, 'info');
        return $this->redirect()->toRoute(
            'default',
            ['controller' => 'Paljo', 'action' => 'MyPaljoSubscriptions']
        );
    }

    /**
     * Send email containing a download link to
     * the subscribed image
     *
     * @param string $paljoId      users paljo id (email address)
     * @param string $downloadLink link to the downloadable image 
     *                             in paljo api
     *
     * @return boolean
     */
    public function sendDownloadEmail($paljoId, $downloadLink)
    {
        try {
            $config = $this->getConfig();
            $renderer = $this->getViewRenderer();
            $message = $renderer->render(
                'Email/paljo-download-link.phtml',
                [
                    'link' => $downloadLink
                ]
            );
            $to = 'jaro.ravila@helsinki.fi';//$paljoId;
            $this->serviceLocator->get(\VuFind\Mailer\Mailer::class)->send(
                $to,
                $config->Site->email,
                $this->translate('verification_email_subject'),
                $message
            );
        } catch (MailException $e) {
            $this->flashMessenger()->addMessage('paljo_download_link_email_error', 'error');
            return false;
        }
        return true;
    }

    /**
     * Send a verification email to users email address
     * that contains a link to create a paljo account
     *
     * @param \Finna\Db\Row\User $user  User object
     * @param string             $email email address to use as paljo id
     *
     * @return boolean
     */
    public function sendVerificationEmail($user, $email)
    {
        if (!$user) {
            return false;
        }
        try {
            $config = $this->getConfig();
            $user->updateHash();
            $renderer = $this->getViewRenderer();
            $message = $renderer->render(
                'Email/paljo-verify-email.phtml',
                [
                    'url' => $this->getServerUrl('paljo-verifyemail')
                    . '?hash=' . $user->verify_hash
                    . '&email=' . $email
                ]
            );
            $to = 'jaro.ravila@helsinki.fi';//$email;
            $this->serviceLocator->get(\VuFind\Mailer\Mailer::class)->send(
                $to,
                $config->Site->email,
                $this->translate('verification_email_subject'),
                $message
            );
        } catch (MailException $e) {
            $this->flashMessenger()->addMessage($e->getMessage(), 'error');
            return false;
        }
        return true;
    }

    /**
     * Verify users email and create a paljo username
     *
     * @return view
     */
    public function verifyEmailAction()
    {
        $hash = $this->params()->fromQuery('hash');
        $email = $this->params()->fromQuery('email');
        $table = $this->getTable('User');
        $user = $table->getByVerifyHash($hash);
        if (!$user) {
            $this->flashMessenger()->addMessage(
                'paljo_account_creation_error_user_not_found', 'error'
            );
        } else {
            $paljo = $this->serviceLocator->get(\Finna\Service\PaljoService::class);
            if ($paljo->createPaljoAccount($email)) {
                $user->setPaljoId($email);
                $this->flashMessenger()->addMessage(
                    'paljo_account_creation_success', 'success'
                );
                return $this->redirect()->toRoute(
                    'default',
                    ['controller' => 'Paljo', 'action' => 'MyPaljoSubscriptions']
                );
            } else {
                $this->flashMessenger()->addMessage(
                    'paljo_account_creation_error', 'error'
                );
            }
        }
        return $this->forwardTo('MyResearch', 'Home');
    }

    public function changePaljoIdAction()
    {
        $userId = $this->params()->fromPost('user');
        $newId = $this->params()->fromPost('new-id');
        $user = $this->getUser();
        $user->setPaljoId($newId);
        return $this->redirect()->toRoute(
            'default',
            ['controller' => 'Paljo', 'action' => 'MyPaljoSubscriptions']
        );
    }

    /**
     * Create a new subscription
     *
     * @return view
     */
    public function createSubscriptionAction()
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirect()->toRoute(
                'default', ['controller' => 'MyResearch', 'action' => 'Login']
            );
        }
        $userPaljoId = $user->getPaljoId();
        $imageId = $this->params()->fromPost('image-id', '');
        $volumeCode = $this->params()->fromPost('volume-code', '');
        $imageSize = $this->params()->fromPost('image-size', '');
        $orgId = $this->params()->fromPost('organisationId', '');
        $cost = $this->params()->fromPost('cost', '');
        $license = $this->params()->fromPost('license', '');
        $priceType = $this->params()->fromPost('price-type', 'private');
        $paljo = $this->serviceLocator->get(\Finna\Service\PaljoService::class);
       // return;
        $payment = true; // handle the payment
        if ($payment) {
            $transaction = $paljo->createTransaction(
                $userPaljoId, $imageId, $volumeCode, $imageSize, $license, $priceType
            );
            if ($transaction) {
                $this->sendDownloadEmail($userPaljoId, $transaction['downloadLink']);   
            }
            $this->flashMessenger()->addMessage(
                'paljo_subscription_success', 'success'
            );
            return $this->redirect()->toRoute(
                'default',
                ['controller' => 'Paljo', 'action' => 'MyPaljoSubscriptions']
            );
        }
        $this->flashMessenger()->addMessage(
            'paljo_subscription_creation_error', 'error'
        );
        return $this->redirect()->toRoute(
            'default',
            ['controller' => 'Paljo', 'action' => 'MyPaljoSubscriptions']
        );
    }

    public function saveVolumeCode()
    {
        $userId = $this->getUser()->id;
        $volumeCode = $this->params()->fromQuery('volumeCode', '');
        $paljo = $this->serviceLocator->get(\Finna\Service\PaljoService::class);
        $response = $paljo->getDiscountForUser($userId, $volumeCode);
        if ($response) {
            $volumeCodeTable = $this->getTable('volumeCode');
            $feedback->saveVolumeCode($user, $volumeCode);
            return $response;
        }
        return false;
    }

    /**
     * Paljo subscriptions for user
     *
     * @return \Laminas\View
     */
    public function myPaljoSubscriptionsAction()
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirect()->toRoute(
                'default', ['controller' => 'MyResearch', 'action' => 'Login']
            );
        }
        $userPaljoId = $user->getPaljoId();
        $paljo = $this->serviceLocator->get(\Finna\Service\PaljoService::class);
        $transactions = $paljo->getUserTransactions($userPaljoId);
        $table = $this->getTable('PaljoVolumeCode');
        $volumeCodes = $table->getVolumeCodesForUser($userPaljoId);
        $codes = [];
        if ($volumeCodes) {
            $codes = [
                'code' => $volumeCodes['volume_code']
            ];
        }
        $view = $this->createViewModel(
            [
                'transactions' => $transactions, 'paljoId' => $userPaljoId,
                'volumeCodes' => $codes
            ]
        );
        $view->setTemplate('myresearch/paljo-subscriptions');
        return $view;
    }
}