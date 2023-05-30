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

use VuFind\Exception\LoginToken as LoginTokenException;

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
     * VuFind configuration
     *
     * @var Config
     */
    protected $config;

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
     * Session table gateway
     *
     * @var Session
     */
    protected $sessionTable;

    /**
     * Cookie Manager
     *
     * @var CookieManager
     */
    protected $cookieManager;

    /**
     * Mailer
     *
     * @var \VuFind\Mailer\Mailer
     */
    protected $mailer;

    /**
     * LoginToken constructor.
     *
     * @param Config                $config          Configuration
     * @param UserTable             $userTable       User table gateway
     * @param LoginTokenTable       $loginTokenTable Login Token table gateway
     * @param Session               $sessionTable    Session table gateway
     * @param CookieManager         $cookieManager   Cookie manager
     * @param \VuFind\Mailer\Mailer $mailer          Mailer
     */
    public function __construct(
        $config,
        $userTable,
        $loginTokenTable,
        $sessionTable,
        $cookieManager,
        $mailer,
    ) {
        $this->config = $config;
        $this->userTable = $userTable;
        $this->loginTokenTable = $loginTokenTable;
        $this->sessionTable = $sessionTable;
        $this->cookieManager = $cookieManager;
        $this->mailer = $mailer;
    }

    /**
     * Authenticate user using a login token cookie
     *
     * @param string $sessionId Session identifier
     *
     * @return UserRow Object representing logged-in user.
     */
    public function tokenLogin($sessionId)
    {
        $cookie = $this->getLoginTokenCookie();
        $user = null;
        if ($cookie) {
            try {
                if ($token = $this->loginTokenTable->matchToken($cookie)) {
                    $this->loginTokenTable->deleteBySeries($token->series);
                    $user = $this->userTable->getById($token->user_id);
                    $this->createToken($user, $token->series, $sessionId);
                }
            } catch (LoginTokenException $e) {
                // Delete all login tokens for the user and all sessions
                // associated with the tokens and send a warning email to user
                $user = $this->userTable->getById($cookie['user_id']);
                $userTokens = $this->loginTokenTable->getByUserId($cookie['user_id']);
                foreach ($userTokens as $t) {
                    $this->sessionTable->destroySession($t->last_session_id);
                }
                $this->loginTokenTable->deleteByUserId($cookie['user_id']);
                $this->sendLoginTokenWarningEmail($user);
                return null;
            }
        }
        return $user;
    }

    /**
     * Create a new login token
     *
     * @param User   $user      user
     * @param string $series    login token series
     * @param string $sessionId Session identifier
     *
     * @return void 
     */
    public function createToken($user, $series = null, $sessionId = null)
    {
        $token = bin2hex(random_bytes(32));
        $series = $series ?? bin2hex(random_bytes(32));
        $browser = '';
        $platform = '';
        try {
            $userInfo = get_browser(null, true) ?? [];
            $browser = $userInfo['browser'];
            $platform = $userInfo['platform'];
        } catch (\Exception $e) {
            // Problem with browscap.ini, continue without
            // browser information
        }
        $lifetime = $this->config->Authentication->persistent_login_lifetime ?? 10;
        $expires = time() + $lifetime * 60 * 60 * 24;

        $this->setLoginTokenCookie($user->id, $token, $series, $expires);
        
        $this->loginTokenTable->saveToken($user->id, $token, $series, $browser, $platform, $expires, $sessionId);
    }

    /**
     * Delete a login token by series. Also destroys
     * sessions associated with the login token
     *
     * @param string $series Series to identify the token
     *
     * @return void
     */
    public function deleteTokenSeries($series)
    {
        $cookie = $this->getLoginTokenCookie();
        if (!empty($cookie) && $cookie['series'] === $series) {
            $this->cookieManager->clear('loginToken');
        }
        $token = $this->loginTokenTable->getBySeries($series);
        $this->sessionTable->destroySession($token->last_session_id);
        $this->loginTokenTable->deleteBySeries($series);
    }

    /**
     * Delete a login token from cookies and database
     *
     * @return void
     */
    public function deleteActiveToken()
    {
        $cookie = $this->getLoginTokenCookie();
        if (!empty($cookie) && $cookie['series']) {
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
        $test = $this->mailer->send(
            $user->email,
            $this->config->Site->email,
            $this->translate('login_warning_email_subject'),
            $this->translate('login_warning_email_message')
        );
    }


    /**
     * Set login token cookie
     *
     * @param string $userId   User identifier
     * @param string $token    Login token
     * @param string $series   Series the token belongs to
     * @param string $expires  Token expiration date
     *
     * @return void
     */
    public function setLoginTokenCookie($userId, $token, $series, $expires)
    {
        $token = implode(';', [$series, $userId, $token]);
        $this->cookieManager->set(
            'loginToken',
            $token,
            $expires,
            true
        );
    }

    /**
     * Get login token cookie in array format
     *
     * @return array
     */
    public function getLoginTokenCookie()
    {
        $result = [];
        if ($cookie = $this->cookieManager->get('loginToken')) {
            $parts = explode(';', $cookie);
            $result = [
                'series' => $parts[0] ?? '',
                'user_id' => $parts[1] ?? '',
                'token' => $parts[2] ?? ''
            ];
        }
        return $result;
    }
}
