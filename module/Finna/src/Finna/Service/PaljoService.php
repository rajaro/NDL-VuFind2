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
class PaljoService implements
    \VuFindHttp\HttpServiceAwareInterface,
    \Laminas\Log\LoggerAwareInterface
{
    use \VuFindHttp\HttpServiceAwareTrait;
    use \VuFind\Log\LoggerAwareTrait {
        logError as error;
    }

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
     * @return boolean
     */
    public function createPaljoAccount($email)
    {
        $response = $this->sendRequest(
            'paljos',
            ['email' => $email],
            'POST'
        );
        if ($response->getStatusCode() === 201) {
            return true;
        }
        return false;
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
        $path = 'organisations/' . $orgId . '/images/' . $imageId;
        $response = $this->sendRequest(
            $path,
            [],
            'GET'
        );
        if ($response->getStatusCode() === 200) {
            return true;
        }
        return false;
    }

    /**
     * Get image price
     *
     * @param string $id    image id
     * @param string $orgId organisation id
     *
     * @return array
     */
    public function getImagePrice($id, $orgId)
    {
        $orgId = 1; //TESTDATA
        $path = 'organisations/' . $orgId . '/images/' . $id . '/pricing';
        $response = $this->sendRequest(
            $path,
            [],
            'GET'
        );
        $result = json_decode($response->getBody(), true);
        $imageInfo = [];
        if (!empty($result['price'])) {
            $imageInfo['price'] = $result['price'];
        }
        if (!empty($result['license'])) {
            $imageInfo['license'] = $result['license'];
        }
        if (!empty($result['currency'])) {
            $imageInfo['currency'] = $result['license'];
        }
        return $imageInfo;
    }


    /**
     * Get user transactions from PALJO API
     *
     * @param string $paljoId paljo ID
     *
     * @return array
     */
    public function getUserTransactions($paljoId)
    {
      //  $paljoId = 'finna@finna.fi';
        $response = $this->sendRequest(
            'transactions',
            ['paljo_id' => $paljoId],
            'GET'
        );
        $result = json_decode($response->getBody(), true);
        $transactions = [];
        if (isset($result['data'])) {
            foreach ($result['data'] as $transaction) {
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
     * Get discount for user
     *
     * @param string $email user paljo id
     * @param string $code  volume code
     *
     * @return array
     */
    public function getDiscountForUser($email, $code)
    {
        // test
        $email = 'finna@finna.fi';
    //    $code = 'cde0e73c-d011-4b14-9dad-7e30e15b01de';
        // test

        $response = $this->sendRequest(
            'volume-queries',
            [
                'paljo_id' => $email,
                'volume_code' => $code
            ],
            'POST'
        );
        $result = json_decode($response->getBody(), true);
        $data = [];
        if (isset($result['data'][0])) {
            $data['discount'] = $result['data'][0]['discount'];
            $data['organisation'] = $result['data'][0]['organisation_id'];
        }
        return $data;
    }

    /**
     * Create a new PALJO transaction
     *
     * @param string $user       users paljo ID
     * @param string $imageId    Image ID from Collecte
     * @param string $volumeCode Volume code used for discount
     * @param string $imageSize  Image resolution
     *
     * @return boolean|array
     */
    public function createTransaction($user, $imageId, $volumeCode, $imageSize, $license, $priceType)
    {
        $orgId = 1;
        $imageDetails = $this->getImagePrice($imageId, $orgId);
        $cost = $imageDetails['price'][$priceType];
        $license = $imageDetails['license'][$priceType]['name'];
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        $response = $this->sendRequest(
            'transactions',
            [
                'paljo_id' => $user,
                'resources' => [
                    [
                        'organisation_id' => $orgId,
                        'image_id' => $imageId,
                        'cost' => $cost,
                        'resolution' => $imageSize,
                        'license' => $license
                    ]
                ]
            ],
            'POST',
            $headers
        );
        if ($response) {
            $result = json_decode($response->getBody(), true);
            if (isset($result['token'])) {
                $transaction = [
                    'downloadLink' => $this->apiUrl . 'transactions/'
                    . $result['token'] . '/package',
                    'token' => $result['token']
                ];
                return $transaction;
            } else {
                $this->error(
                    'Problem with PALJO API: ' . var_dump($result)
                );
            }
        }
        return false;
    }

    /**
     * Send a request to PALJO API
     *
     * @param string $path   relative path
     * @param array  $params array of parameters
     * @param string $method GET|POST
     * @param array  $headers request headers
     *
     * @return string
     */
    protected function sendRequest($path, $params, $method, $headers = null)
    {
        $url = $this->apiUrl . $path;
        $client = $this->httpService->createClient($url);

        if ($method === 'POST' && !$headers) {
            $client->setParameterPost($params);
        } else if ($method === 'GET') {
            $client->setParameterGet($params);
        }

        $client->setAuth('paljo', 'paljo2019');
        if ($headers) {
            $client->setHeaders($headers);
            $rawBody = json_encode($params);
            $client->setRawBody($rawBody);
        }
        $client->setMethod($method);
        try {
            $response = $client->send();
        } catch (\Exception $e) {
            $this->error(
                "Request for '$url' failed: " . $e->getMessage()
            );
            return false;
        }
        if (!$response->isSuccess()) {
            $this->error(
                'Request for ' . $url . ' failed '
                . 'with status ' . $response->getStatusCode() 
            );
        }
        return $response;
    }
}