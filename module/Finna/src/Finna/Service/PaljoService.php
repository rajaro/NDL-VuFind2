<?php
/**
 * PALJO Service
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
 * @package  Content
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace Finna\Service;

/**
 * PALJO service
 *
 * @category VuFind
 * @package  Content
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class PaljoService implements \VuFindHttp\HttpServiceAwareInterface
{
    use \VuFindHttp\HttpServiceAwareTrait;

    /**
     * PALJO api url
     *
     * @var string
     */
    protected $apiUrl = 'https://paljo.userix.fi/api/v1/';

    /**
     * Mailer
     *
     * @var \VuFind\Mailer\Mailer
     */
    protected $mailer;

    /**
     * Constructor
     *
     * @param \VuFind\Mailer\Mailer $mailer mailer
     */
    public function __construct($mailer)
    {
        $this->mailer = $mailer;
    }
    /**
     * Create paljo account
     *
     * @param string $email email
     *
     * @return string
     */
    public function createPaljoAccount($email)
    {
        $response = $this->sendRequest(
            'paljos',
            ['email' => 'testipaljo@testausta.fi'],
            'POST'
        );
        return $response;
    }

    /**
     * Check image availability from PALJO API
     *
     * @param string $orgId   organisation id
     * @param string $imageId image id
     *
     * @return boolean 
     */
    public function checkAvailability($orgId, $imageId)
    {
        $orgId = '1';       // Test data
        $imageId = '273';  // Test data
        $path = 'organisations/' . $orgId . '/images/' . $imageId;
        $response = $this->sendRequest(
            $path,
            [],
            'GET'
        );
        if ($response['message'] === 'Image exists') {
            return true;
        }
        return false;
    }

    /**
     * Get user transactions from PALJO API
     *
     * @param string $paljoId paljo ID
     *
     * @return array
     */
    public function getMyTransactions($paljoId)
    {
        $paljoId = 'finna@finna.fi';
        $response = $this->sendRequest(
            'transactions',
            ['paljo_id' => $paljoId],
            'GET'
        );
        $transactions = [];
        if ($response['data']) {
            foreach ($response['data'] as $transaction) {
                $resource = $transaction['transaction_resources'][0];
                $transactions[] = [
                  'id' => $resource['image_id'],
                  'resolution' => $resource['resolution'],
                  'cost' => $resource['cost'],
                  'license' => $resource['license']
                ];
            }
        }
        return $transactions;
    }

    /**
     * Send a request to PALJO API
     *
     * @param string $path   relative path
     * @param array  $params array of parameters
     * @param string $method GET|POST
     *
     * @return string
     */
    public function sendRequest($path, $params, $method)
    {
        $url = $this->apiUrl . $path;
        $client = $this->httpService->createClient($url);

        if ($method === 'POST') {
            $client->setParameterPost($params);
        } else if ($method === 'GET') {
            $client->setParameterGet($params);
        }
        $client->setAuth('paljo', 'paljo2019');
        $client->setMethod($method);
        try {
            $response = $client->send();
        } catch (\Exception $e) {
            $this->error(
                "POST request for '$apiUrl' failed: " . $e->getMessage()
            );
            return $e->getMessage();
        }
        $result = json_decode($response->getBody(), true);
        return $result;
    }
}