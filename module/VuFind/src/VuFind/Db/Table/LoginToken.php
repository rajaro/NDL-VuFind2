<?php
/**
 * Table Definition for login_token
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
 * @package  Db_Table
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
namespace VuFind\Db\Table;

use Laminas\Db\Adapter\Adapter;
use VuFind\Db\Row\RowGateway;
use VuFind\Exception\Auth as AuthException;

/**
 * Table Definition for login_token
 *
 * @category VuFind
 * @package  Db_Table
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class LoginToken extends Gateway
{
    /**
     * Constructor
     *
     * @param Adapter       $adapter Database adapter
     * @param PluginManager $tm      Table manager
     * @param array         $cfg     Laminas configuration
     * @param RowGateway    $rowObj  Row prototype object (null for default)
     * @param string        $table   Name of database table to interface with
     */
    public function __construct(
        Adapter $adapter,
        PluginManager $tm,
        $cfg,
        ?RowGateway $rowObj = null,
        $table = 'login_token'
    ) {
        parent::__construct($adapter, $tm, $cfg, $rowObj, $table);
    }


    /**
     * Generate new token
     *
     * @param string $username Username
     * @param string $series   Series the token belongs to
     * @param string $browser  User browser
     * @param string $platform User platform
     *
     * @return array 
     */
    public function createToken($username, $series = null, $browser = null, $platform = null)
    {
        $token = bin2hex(random_bytes(32));
        $series = $series ?? bin2hex(random_bytes(32));
        $row = $this->saveToken($username, $token, $series, $browser, $platform);
        $result = [
          'username' => $username,
          'series' => $series,
          'token' => $token,
          'expires' => $row->expires
        ];
        return $result;
    }

    /**
     * Save a token
     *
     * @param string $username Username
     * @param string $token    Login token
     * @param string $series   Series the token belongs to
     * @param string $browser  User browser 
     * @param string $platform User platform
     *
     * @return LoginToken
     */
    public function saveToken($username, $token, $series, $browser = null, $platform = null)
    {
        $row = $this->createRow();
        $row->token = hash('sha256', $token);
        $row->series = $series;
        $row->username = $username;
        $row->last_login = date('Y-m-d H:i:s');
        $row->browser = $browser;
        $row->platform = $platform;
        $row->expires = time() + 365 * 60 * 60 * 24;
        $row->save();
        return $row;
    }

    /**
     * Check if a login token matches one in database. If match is found, old token
     * is deleted and a new one created with the same username and series as the old
     *
     * @param array $token array containing username, token and series
     *
     * @return mixed
     * @throws AuthException
     */
    public function matchToken($token)
    {
        $row = $this->select(
            [
                'username' => $token['username'],
                'series' => $token['series']
            ]
        )->current();
        if ($row && hash_equals($row['token'], hash('sha256', $token['token']))) {
            if (time() > $row['expires']) {
                $row->delete();
                return false;
            }
            return $row;
        } else if ($row) {
            // Matching series and username found, but token does not match: 
            // delete all tokens for the user
            $this->delete(
                [
                    'username' => $token['username'],
                ]
            );
            throw new AuthException("Token does not match");
        }
        return false;
    }

    /**
     * Delete all tokens in a given series
     *
     * @param string $series series
     *
     * @return void
     */
    public function deleteBySeries($series)
    {
        $this->delete(['series' => $series]);
    }
  
    /**
     * Delete all tokens for a user
     *
     * @param string $username username
     *
     * @return void
     */
    public function deleteByUsername($username)
    {
        $this->delete(['username' => $username]);
    }

    /**
     * Get tokens for a given user
     *
     * @param string $username Username
     *
     * @return array
     */
    public function getByUsername($username)
    {
        return $this->select(['username' => $username]);
    }

    /**
     * Remove expired login tokens
     *
     * @return void
     */
    public function removeExpired()
    {
        $callback = function ($select) {
            $select->where->lessThanOrEqualTo('expires', time());
        };
        $this->delete($callback);
    }
}
