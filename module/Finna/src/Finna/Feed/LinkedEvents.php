<?php
/**
 * Feed service
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2016-2019.
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
namespace Finna\Feed;

/**
 * Feed service
 *
 * @category VuFind
 * @package  Content
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class LinkedEvents implements \VuFindHttp\HttpServiceAwareInterface
{
    use \VuFindHttp\HttpServiceAwareTrait;

    /**
     * Api url
     *
     * @var string
     */
    protected $apiUrl = '';

    /**
     * URL
     */

    /**
     * Constructor
     *
     * @param Config $config Main configuration
     */
    public function __construct($config)
    {
        $this->apiUrl = $config->LinkedEvents->api_url ?? '';

    }

    /**
     * Query the API for events
     *
     * @param array $params parameters for the query
     *
     * @return array
     */
    public function getEvents($params)
    {
        $url = $this->apiUrl . '?publisher=pori:kaupunki';
        $client = $this->httpService->createClient($url);
        //$client->setParameterGet($params);
        $result = $client->send();
        if (!$result->isSuccess()) {
            return $this->handleError(
                'API request failed, url: ' . $url
            );
        }

        $response = json_decode($result->getBody(), true);
        $events = [];
        if (! empty($response['data'])) {
            foreach ($response['data'] as $eventData) {

                $event = [
                  'name' => $eventData['name'],
                  'description' => $eventData['description'],
                  'imageurl' => $eventData['images'][0]['url'] ?? '',
                  'short_description' => $eventData['short_description'],
                  'start_time' => $eventData['start_time'],
                  'end_time' => $eventData['end_time']
                ];
                $events[] = $event;
            }
        }
        return $events;
    }
}

