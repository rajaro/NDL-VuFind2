<?php

/**
 * Class LoginTokenTest
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
 * @package  Tests
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

declare(strict_types=1);

namespace VuFindTest\Auth;

use VuFind\Auth\LoginToken;
use VuFind\Auth\Manager;
use VuFind\Db\Row\User;
use Laminas\Config\Config;
use Laminas\Session\SessionManager;
use Laminas\Stdlib\Parameters;

/**
 * Class AuthTokenTest
 *
 * @category VuFind
 * @package  Tests
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class LoginTokenTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Test creating token
     *
     * @return void
     */
    public function testCreateToken()
    {
        $table = $this->getMockTable(['getByUserId']);
        $token = $this->getLoginToken($table);
        $token->createToken($this->getMockUser(), '111', '222');
        $token->setLoginTokenCookie(1, '111', '222', 123124123124);
        $cookie = $token->getLoginTokenCookie();

        var_dump($cookie);
        
    }

    /**
     * Get a mock user object
     *
     * @param array $methods Methods to mock
     *
     * @return User
     */
    protected function getMockUser($methods = [])
    {
        $user = $this->getMockBuilder(\VuFind\Db\Row\User::class)
            ->disableOriginalConstructor()
            ->onlyMethods($methods)
            ->getMock();
        $user->id = 1;
        return $user;
    }

    /**
     * Get a mock auth manager
     *
     * @param array $methods Methods to mock
     *
     * @return Manager
     */
    protected function getMockManager($methods = [])
    {
        return $this->getMockBuilder(\VuFind\Auth\Manager::class)
            ->disableOriginalConstructor()
            ->onlyMethods($methods)
            ->getMock();
    }

    /**
     * Get a mock row object
     *
     * @return \VuFind\Db\Row\LoginToken
     */
    protected function getMockRow()
    {
        return $this->getMockBuilder(\VuFind\Db\Row\LoginToken::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Get a mock table object
     *
     * @param array $methods Methods to mock
     *
     * @return \VuFind\Db\Table\LoginToken
     */
    protected function getMockTable($methods = [])
    {
        $mock = $this->getMockBuilder(\VuFind\Db\Table\LoginToken::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mock->expects($this->any())->method('getByUserId')
            ->will(
                $this->returnValue($this->getMockRow())
            );
        return $mock;
    }

    /**
     * Get login token class
     *
     * @return LoginToken
     */
    public function getLoginToken()
    {
        $config = new Config(
            [
                'Authentication' => [
                    'persistent_login' => 'Database;MultiILS',
                    'persistent_login_lifetime' => 1
                ],
            ]
        );
        $mockUser = $this->getMockBuilder(\VuFind\Db\Row\User::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $mockUser = $this->getMockUser();
        $mockUserTable = $this->getMockBuilder(\VuFind\Db\Table\User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getById'])
            ->getMock();

        $mockUserTable->expects($this->any())
            ->method('getById')
            ->willReturn($mockUser);
        $mockToken = $this->getMockBuilder(\VuFind\Db\Row\LoginToken::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockToken->series = '222';
        $mockToken->user_id = 1;
        $mockLoginTokenTable = $this->getMockBuilder(\VuFind\Db\Table\LoginToken::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockLoginTokenTable->expects($this->any())
            ->method('getByUserId')
            ->willReturn($mockToken);
        $mockCookie = $this->getMockBuilder(\VuFind\Cookie\CookieManager::class)->disableOriginalConstructor()->getMock();
        $mockSession = $this->getMockBuilder(\Laminas\Session\SessionManager::class)->disableOriginalConstructor()->getMock();
        $mockMailer = $this->getMocKBuilder(\VuFind\Mailer\Mailer::class)->disableOriginalConstructor()->getMock();

    //    return $this->getMocKBuilder(\VuFind\Auth\LoginToken::class)->disableOriginalConstructor()->getMock();
        return new LoginToken(
            $config,
            $mockUserTable,
            $this->getMockTable(['getByUserId']),
            $mockCookie,
            $mockSession,
            $mockMailer
        );
    }
}