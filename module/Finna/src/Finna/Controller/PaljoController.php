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
        $id = $this->params()->fromRoute('id', '');
        $organisationId = $this->params()->fromRoute('organisationId', '');
        $recordId = $this->params()->fromRoute('recordId', '');

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
                    'recordId' => $recordId, 'prices' => $prices
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
        $this->sendVerificationEmail($user, $email);
        $view = $this->createViewModel(
            [
                'url' => $this->getServerUrl('paljo-verifyemail')
                . '?hash=' . $user->verify_hash
                . '&email=' . $email
            ]
        );
        $view->setTemplate(
            'Email/paljo-verify-email.phtml',
            [
            'url' => $this->getServerUrl('paljo-verifyemail')
            . '?hash=' . $user->verify_hash
            ]
        );
        return $view;
    }

    /**
     * Send a verification email to users to email address
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
                ]
            );
            $to = $email;
            $to = 'jaro.ravila@helsinki.fi';
            return $message;
            $this->serviceLocator->get(\VuFind\Mailer\Mailer::class)->send(
                $to,
                $config->Site->email,
                $this->translate('verification_email_subject'),
                $message
            );
            $flashMessage = 'paljo_email_verification_sent';
            $this->flashMessenger()->addMessage($flashMessage, 'info');
        } catch (MailException $e) {
            $this->flashMessenger()->addMessage($e->getMessage(), 'error');
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
            return false;
        }
        $paljo = $this->serviceLocator->get(\Finna\Service\PaljoService::class);
        if ($paljo->createPaljoAccount($email)) {
            $user->setPaljoId($email);
        }
        return $this->forwardTo('MyResearch', 'Home');
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
        $transactions = $paljo->getMyTransactions($userPaljoId);
        $view = $this->createViewModel(
            ['transactions' => $transactions, 'paljoId' => $userPaljoId]
        );
        $view->setTemplate('myresearch/paljo-subscriptions');
        return $view;
    }
}