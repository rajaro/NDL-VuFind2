<?php

/**
 * Authentication Api Controller
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2017-2019.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.    See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA    02111-1307    USA
 *
 * @category VuFind
 * @package  Controller
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace FinnaApi\Controller;

use VuFind\Exception\ILS as ILSException;

/**
 * Provides an API for user authentication.
 *
 * @category VuFind
 * @package  Controller
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class AuthApiController extends \VuFindApi\Controller\ApiController implements
    \VuFindApi\Controller\ApiInterface,
    \Laminas\Log\LoggerAwareInterface
{
    use \VuFindApi\Controller\ApiTrait;
    use \VuFind\Log\LoggerAwareTrait;

    /**
     * Execute the request
     *
     * @param \Laminas\Mvc\MvcEvent $e Event
     *
     * @return mixed
     * @throws Exception\DomainException
     */
    public function onDispatch(\Laminas\Mvc\MvcEvent $e)
    {
        // Add CORS headers and handle OPTIONS requests. This is a simplistic
        // approach since we allow any origin. For more complete CORS handling
        // a module like zfr-cors could be used.
        $response = $this->getResponse();
        $headers = $response->getHeaders();
        $headers->addHeaderLine('Access-Control-Allow-Origin: *');
        $request = $this->getRequest();
        if ($request->getMethod() == 'OPTIONS') {
            // Disable session writes
            $this->disableSessionWrites();
            $headers->addHeaderLine(
                'Access-Control-Allow-Methods',
                'GET, POST, OPTIONS'
            );
            $headers->addHeaderLine('Access-Control-Max-Age', '86400');

            return $this->output(null, 204);
        }

        return parent::onDispatch($e);
    }

    /**
     * Retrieve backends available for library card authentication
     *
     * @return \Laminas\Http\Response
     */
    public function getLoginTargetsAction()
    {
        $this->disableSessionWrites();
        $this->determineOutputMode();

        if ($result = $this->isAccessDenied('access.finna.api.auth.backendlist')) {
            return $result;
        }

        $backends = [];
        $catalog = $this->getILS();
        $targets = $this->getAvailableLoginTargets();
        foreach ($targets as $target) {
            $config = [];
            try {
                $config = $catalog->getConfig(
                    'patronLogin',
                    ['cat_username' => "$target.username"]
                );
            } catch (\Exception $e) {
                // nevermind
            }

            $loginMethod = $config['loginMethod'] ?? 'password';
            if (in_array($loginMethod, ['vufind', 'email'])) {
                continue;
            }

            $backend = [
                'id' => $target,
                'name' => $this->translate("source_$target", null, $target),
            ];
            $backends[] = $backend;
        }

        return $this->output(['targets' => $backends], self::STATUS_OK);
    }

    /**
     * Login with library card and return status
     *
     * @return \Laminas\Http\Response
     */
    public function libraryCardLoginAction()
    {
        $this->disableSessionWrites();
        $this->determineOutputMode();

        if (
            $result = $this->isAccessDenied('access.finna.api.auth.librarycardlogin')
        ) {
            return $result;
        }

        $catalog = $this->getILS();
        $defaultTarget = $catalog->checkCapability('getDefaultLoginDriver')
            ? $catalog->getDefaultLoginDriver()
            : '';

        $request = $this->getRequest();
        $target = trim(
            $request->getPost('target', $request->getQuery('target', $defaultTarget))
        );
        $username = trim(
            $request->getPost('username', $request->getQuery('username'))
        );
        $password = trim(
            $request->getPost('password', $request->getQuery('password'))
        );

        if (empty($username) || empty($password)) {
            return $this->output(
                [],
                self::STATUS_ERROR,
                400,
                'Missing parameters'
            );
        }

        if (!empty($target)) {
            $targets = $this->getAvailableLoginTargets();
            if (!in_array($target, $targets)) {
                return $this->output(
                    [],
                    self::STATUS_ERROR,
                    400,
                    'Invalid login target'
                );
            }
            $username = "$target.$username";
        }

        $config = [];
        try {
            $config = $catalog->getConfig(
                'patronLogin',
                ['cat_username' => $username]
            );
        } catch (\Exception $e) {
            // nevermind
        }
        $loginMethod = $config['loginMethod'] ?? 'password';
        if (in_array($loginMethod, ['vufind', 'email'])) {
            return $this->output(
                [],
                self::STATUS_ERROR,
                400,
                'Invalid login target'
            );
        }

        try {
            $result = $catalog->patronLogin($username, $password);
        } catch (ILSException $e) {
            $this->logError(
                "$target login ILS exception: " . $e->getMessage()
            );
            return $this->output([], self::STATUS_ERROR, 503, 'Backend unavailable');
        } catch (\Exception $e) {
            $this->logError("$target login exception: " . $e->getMessage());
            return $this->output([], self::STATUS_ERROR, 500, 'Server error');
        }
        return $this->output(
            ['result' => $result ? 'success' : 'failure'],
            self::STATUS_OK
        );
    }

    /**
     * Get Swagger specification JSON fragment for services provided by the
     * controller
     *
     * @return string
     */
    public function getApiSpecFragment()
    {
        $spec = [];
        if (!$this->isAccessDenied('access.finna.api.auth.backendlist')) {
            $spec['paths']['/auth/getLoginTargets']['get'] = [
                'summary' => 'Get login targets',
                'description' => 'Lists the possible login targets.',
                'parameters' => [],
                'tags' => ['auth'],
                'responses' => [
                    '200' => [
                        'description' => 'List of targets',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'properties' => [
                                        'targets' => [
                                            'description' => 'Login targets',
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'id' => [
                                                        'description'
                                                            => 'Target identifier',
                                                        'type' => 'string',
                                                    ],
                                                    'name' => [
                                                        'description'
                                                            => 'Target name',
                                                        'type' => 'string',
                                                    ],
                                                ],
                                            ],
                                        ],
                                        'status' => [
                                            'description' => 'Status code',
                                            'type' => 'string',
                                            'enum' => ['OK'],
                                        ],
                                    ],
                                ],
                            ],
                            'required' => ['resultCount', 'status'],
                        ],
                    ],
                    'default' => [
                        'description' => 'Error',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/Error',
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }
        if (!$this->isAccessDenied('access.finna.api.auth.backendlist')) {
            $spec['paths']['/auth/getLoginTargets']['get'] = [
                'summary' => 'Get login targets',
                'description' => 'Lists the possible login targets.',
                'parameters' => [],
                'tags' => ['auth'],
                'responses' => [
                    '200' => [
                        'description' => 'List of targets',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'properties' => [
                                        'targets' => [
                                            'description' => 'Login targets',
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'id' => [
                                                        'description'
                                                            => 'Target identifier',
                                                        'type' => 'string',
                                                    ],
                                                    'name' => [
                                                        'description'
                                                            => 'Target name',
                                                        'type' => 'string',
                                                    ],
                                                ],
                                            ],
                                        ],
                                        'status' => [
                                            'description' => 'Status code',
                                            'type' => 'string',
                                            'enum' => ['OK'],
                                        ],
                                    ],
                                    'required' => ['resultCount', 'status'],
                                ],
                            ],
                        ],
                    ],
                    'default' => [
                        'description' => 'Error',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/Error',
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        if (!$this->isAccessDenied('access.finna.api.auth.librarycardlogin')) {
            $spec['paths']['/auth/libraryCardLogin']['post'] = [
                'summary' => 'Check login with a library card',
                'description'
                    => 'Returns a success or failure for given credentials',
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/x-www-form-urlencoded' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'target' => [
                                        'type' => 'string',
                                        'description' => 'Login target (backend'
                                            . ' from getLoginTargets)',
                                    ],
                                    'username' => [
                                        'type' => 'string',
                                        'description' => 'Library card number',
                                    ],
                                    'password' => [
                                        'type' => 'string',
                                        'description' => 'Password',
                                    ],
                                ],
                                'required' => [
                                    'target',
                                    'username',
                                    'password',
                                ],
                            ],
                        ],
                    ],
                ],
                'tags' => ['auth'],
                'responses' => [
                    '200' => [
                        'description' => 'List of targets',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'properties' => [
                                        'result' => [
                                            'description' => 'Login result',
                                            'type' => 'string',
                                            'enum' => ['success', 'failure'],
                                        ],
                                        'status' => [
                                            'description' => 'Status code',
                                            'type' => 'string',
                                            'enum' => ['OK'],
                                        ],
                                    ],
                                    'required' => ['resultCount', 'status'],
                                ],
                            ],
                        ],
                    ],
                    '500' => [
                        'description' => 'Processing of the login request failed',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/Error',
                                ],
                            ],
                        ],
                    ],
                    '503' => [
                        'description'
                            => 'Connection to the backend system (ILS) failed',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/Error',
                                ],
                            ],
                        ],
                    ],
                    'default' => [
                        'description' => 'Error',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/Error',
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        return json_encode($spec);
    }

    /**
     * Get a list of available login targets
     *
     * @return array
     */
    protected function getAvailableLoginTargets()
    {
        $targets = [];
        $catalog = $this->getILS();
        if ($catalog->checkCapability('getLoginDrivers')) {
            $targets = $catalog->getLoginDrivers();
        }
        $config = $this->getConfig();
        if (!empty($config['Authentication']['include_api_targets'])) {
            $include = array_map(
                'trim',
                explode(',', $config['Authentication']['include_api_targets'])
            );
            $targets = array_intersect($targets, $include);
        }
        if (!empty($config['Authentication']['exclude_api_targets'])) {
            $exclude = array_map(
                'trim',
                explode(',', $config['Authentication']['exclude_api_targets'])
            );
            $targets = array_diff($targets, $exclude);
        }
        return $targets;
    }
}
