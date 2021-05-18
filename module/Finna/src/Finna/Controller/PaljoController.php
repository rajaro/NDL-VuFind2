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
        $userPaljoId = $user->paljo_id;
        if ($userPaljoId === null) {
            $view = $this->createViewModel();
            $this->flashMessenger()->addMessage(
                $this->transEsc('paljo_login_required'),
                'info'
            );
            $view->setTemplate('RecordDriver/SolrLido/paljo-account-creation');
        } else {
            $paljo = $this->serviceLocator->get(\Finna\Service\PaljoService::class);
            $table = $this->getTable('PaljoVolumeCode');
            $volumeCodes = $table->getVolumeCodesForUser($userPaljoId);
            $prices = $paljo->getImagePrice($id, $organisationId);
            $driver = $this->getRecordLoader()->load($recordId, 'Solr', true);
            $view = $this->createViewModel(
                [
                    'driver' => $driver, 'id' => $id,
                    'recordId' => $recordId, 'prices' => $prices,
                    'organisationId' => $organisationId,
                    'volumeCodes' => $volumeCodes,
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
            ['controller' => 'Paljo', 'action' => 'Subscriptions']
        );
    }

    /**
     * Send email containing a download link to
     * the ordered image
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
                $this->translate('paljo_download_email_subject'),
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
                $this->translate('paljo_verification_email_subject'),
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
        if ($user) {
            $paljo = $this->serviceLocator->get(\Finna\Service\PaljoService::class);
            if ($paljo->createPaljoAccount($email)) {
                $user->setPaljoId($email);

                return $this->redirect()->toRoute(
                    'default',
                    ['controller' => 'Paljo', 'action' => 'Subscriptions']
                );
            }
        } else {
            $this->flashMessenger()->addMessage(
                'paljo_account_creation_error', 'error'
            );
        }
        return $this->forwardTo('MyResearch', 'Home');
    }

    /**
     * Change users paljo id
     *
     * @return view
     */
    public function changePaljoIdAction()
    {
        $userId = $this->params()->fromPost('user');
        $newId = $this->params()->fromPost('new-id');
        $user = $this->getUser();
        $user->setPaljoId($newId);
        return $this->redirect()->toRoute(
            'default',
            ['controller' => 'Paljo', 'action' => 'Subscriptions']
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
        $userPaljoId = $user->paljo_id;
        $imageId = $this->params()->fromPost('image-id', '');
        $volumeCode = $this->params()->fromPost('volume-code', '');
        $imageSize = $this->params()->fromPost('image-size', '');
        $orgId = $this->params()->fromPost('organisationId', '');
        $priceType = $this->params()->fromPost('price-type', '');
        $recordId = $this->params()->fromPost('record-id', '');
        $userMessage = $this->params()->fromPost('user-message', '');
        $paljo = $this->serviceLocator->get(\Finna\Service\PaljoService::class);
        $imageDetails = $paljo->getImagePrice($imageId, $orgId);
        if ($imageDetails && $priceType) {
            $cost = $imageDetails['price'][$priceType];
            $license = $imageDetails['license'][$priceType]['name'];
            $currency = $imageDetails['currency'][$priceType];
            $discount = $volumeCode
                ? $paljo->getDiscountForUser($user->paljo_id, $volumeCode)
                : '';
            if ($discount) {
                if ($discount['organisation'] === $orgId) {
                    $discountAmount = $discount['discount'];
                    $cost = (1 - $discountAmount / 100) * $cost;
                }
            }
            $payment = true; // handle the payment
            if ($payment) {
                $transaction = $paljo->createTransaction(
                    $userPaljoId, $imageId, $volumeCode, $imageSize, $cost, $license
                );
                $registered = 1;//!empty($transaction);
                $token = $transaction['token'] ?? '';
                $paljoTransactions = $this->getTable('PaljoTransaction');
                $paljoTransactions->saveTransaction(
                    $user->id, $user->paljo_id, $recordId, $imageId, $token, $userMessage, $imageSize,
                    $cost, $currency, $priceType, '2021-05-28', $registered, $volumeCode //random date for testing purposes
                );
                if ($transaction) {
                    $this->sendDownloadEmail($userPaljoId, $transaction['downloadLink']);
                    $this->flashMessenger()->addMessage(
                        'paljo_order_success', 'info'
                    );
                } else {
                    $this->flashMessenger()->addMessage(
                        'paljo_register_error_message', 'error'
                    );
                }
            } else {
                $this->flashMessenger()->addMessage(
                    'paljo_payment_error', 'error'
                );
            }
        }
        return $this->redirect()->toRoute(
            'default',
            ['controller' => 'Paljo', 'action' => 'Subscriptions']
        );
    }

    /**
     * Attempt to register a failed transaction to Paljo
     *
     */
    public function registerTransactionAction() {
        $user = $this->getUser();
        $transactionId = $this->params()->fromQuery('id', '');
        $paljo = $this->serviceLocator->get(\Finna\Service\PaljoService::class);
        $paljoTransactions = $this->getTable('PaljoTransaction');
        $transaction = $paljoTransactions->getById($transactionId);
        if ($transaction) {
            $response = $paljo->createTransaction(
                $user->paljo_id, $transaction->image_id, '', $transaction->image_size, $transaction->amount, 'CC0'
            );
            if ($response) {
                $paljoTransactions->registerTransaction($transactionId);
                $this->sendDownloadEmail($user->paljo_id, $response['downloadLink']);
                $this->flashMessenger()->addMessage(
                    'paljo_subscription_success', 'info'
                );
            } else {
                $this->flashMessenger()->addMessage(
                    'paljo_subscription_creation_error', 'error'
                );
            }
        } else {
            $this->flashMessenger()->addMessage(
                'paljo_transaction_not_found', 'error'
            );
        }
        return $this->redirect()->toRoute(
            'default',
            ['controller' => 'Paljo', 'action' => 'Subscriptions']
        );
    }

    /**
     * Delete a volume code
     *
     * @return \Laminas\View
     */
    public function deleteVolumeCodeAction()
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirect()->toRoute(
                'default', ['controller' => 'MyResearch', 'action' => 'Login']
            );
        }
        $codeId = $this->params()->fromQuery('codeid');
        $volumeCodeTable = $this->getTable('PaljoVolumeCode');
        $volumeCodeTable->deleteVolumeCode($codeId, $user->paljo_id);
        return $this->redirect()->toRoute(
            'default',
            ['controller' => 'Paljo', 'action' => 'Subscriptions']
        );
    }

    /**
     * Paljo subscriptions for user
     *
     * @return \Laminas\View
     */
    public function subscriptionsAction()
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirect()->toRoute(
                'default', ['controller' => 'MyResearch', 'action' => 'Login']
            );
        }
        $userPaljoId = $user->paljo_id;
        $paljo = $this->serviceLocator->get(\Finna\Service\PaljoService::class);
        $volumeCodetable = $this->getTable('PaljoVolumeCode');
        $volumeCodes = $volumeCodetable->getVolumeCodesForUser($userPaljoId);
        $transactionTable = $this->getTable('PaljoTransaction');
        $limit = $this->params()->fromQuery('limit', '50');
        $page = $this->params()->fromQuery('page', 1);
        $active = $this->params()->fromQuery('show', 'active');

        $transactions = $transactionTable->getTransactions($user->paljo_id, $page, $limit, $active === 'active');

        $totalTransactions = $transactionTable->getTotalTransactions($user->paljo_id, $active === 'active');
        $apiUrl = $this->getConfig('paljo')->General->api_url;
        $view = $this->createViewModel(
            [
                'transactions' => $transactions, 'volumeCodes' => $volumeCodes,
                'apiurl' => $apiUrl, 'show' => $active, 'limit' => $limit,
                'page' => $page, 'total' => $totalTransactions
            ]
        );
        $view->setTemplate('myresearch/paljo-subscriptions');
        return $view;
    }
}