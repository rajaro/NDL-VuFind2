<?php

/**
 * LoginToken Manager
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2023.
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
 * @package  VuFind\Auth
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

declare(strict_types=1);

namespace VuFind\Auth;

/**
 * Class LoginToken
 *
 * @category VuFind
 * @package  VuFind\Auth
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class LoginToken implements \VuFind\I18n\Translator\TranslatorAwareInterface
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    /**
     * User table gateway
     *
     * @var UserTable
     */
    protected $userTable;

    /**
     * Login token table gateway
     *
     * @var LoginToken
     */
    protected $loginTokenTable;

    /**
     * Cookie Manager
     *
     * @var CookieManager
     */
    protected $cookieManager;

    /**
     * View renderer
     *
     * @var RendererInterface
     */
    protected $viewRenderer;

    /**
     * Mailer
     *
     * @var \Mailer
     */
    protected $mailer;

    /**
     * LoginToken constructor.
     *
     * @param UserTable         $userTable       User table gateway
     * @param LoginTokenTable   $loginTokenTable Login Token table gateway
     * @param CookieManager     $cookieManager   Cookie manager
     * @param Mailer            $mailer          Mailer
     * @param RendererInterface $viewRenderer    View renderer
     */
    public function __construct(
        $userTable,
        $loginTokenTable,
        $cookieManager,
        $mailer,
        $viewRenderer,
    ) {
        $this->userTable = $userTable;
        $this->loginTokenTable = $loginTokenTable;
        $this->cookieManager = $cookieManager;
        $this->mailer = $mailer;
        $this->viewRenderer = $viewRenderer;
    }

    /**
     * Token login
     *
     * @return UserRow Object representing logged-in user.
     */
    public function tokenLogin()
    {
        $cookie = $this->cookieManager->get('loginToken');
        $user = null;
        if ($cookie) {
            try {
                if ($token = $this->loginTokenTable->matchToken($cookie)) {
                    $this->loginTokenTable->deleteBySeries($token->series);
                    $user = $this->userTable->getByUsername($token['username']);
                    $this->createToken($user, $token->series);
                }
            } catch (AuthException $e) {
                $user = $this->userTable->getByUsername($cookie['username']);
                $this->sendLoginTokenWarningEmail($user);
                $this->loginTokenTable->deleteByUsername($cookie['username']);
                $this->logError((string)$e);
            }
        }
        return $user;
    }

    /**
     * Create a new login token
     *
     * @param User   $user   user
     * @param string $series login token series
     *
     * @return void 
     */
    public function createToken($user, $series = null)
    {
        $userInfo = get_browser();
        $browser = $userInfo->browser ?? '';
        $platform = $userInfo->platform ?? '';
        $loginToken = $this->loginTokenTable->createToken($user->username, $series, $browser, $platform);
        $this->setLoginTokenCookie($loginToken);
    }

    /**
     * Delete a login token
     *
     * @param string $series Series to identify the token
     *
     * @return void
     */
    public function deleteTokenSeries($series)
    {
        $cookie = $this->cookieManager->get('loginToken');
        if (isset($cookie['series']) && $cookie['series'] === $series) {
            $this->cookieManager->clear('loginToken');
        }
        $this->loginTokenTable->deleteBySeries($series);
    }

    /**
     * Delete a login token from cookies and database
     *
     * @return void
     */
    public function deleteActiveToken()
    {
        $cookie = $this->cookieManager->get('loginToken');
        if (isset($cookie['series'])) {
            $this->loginTokenTable->deleteBySeries($cookie['series']);
        }
        $this->cookieManager->clear('loginToken');
    }

    /**
     * Send email warning to user
     *
     * @param User $user User
     *
     * @return void
     */
    public function sendLoginTokenWarningEmail($user)
    {
        $message = $this->viewRenderer->render(
            'Email/login-warning-email.phtml', ['username' => $user->username]
        );
        $test = $this->mailer->send(
            $user->email,
            $this->config->Site->email,
            $this->translate('login_warning_email_subject'),
            $message
        );
        var_dump($test);
    }


    /**
     * Set login token cookie
     *
     * @param array $token token
     *
     * @return void
     */
    public function setLoginTokenCookie($token)
    {
        $this->cookieManager->set(
            'loginToken',
            [
                'username' => $token['username'],
                'token' => $token['token'],
                'series' => $token['series']
            ],
            $token['expires'],
            true
        );
    }
}
