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
     * Language
     *
     * @var string
     */
    protected $language;

    /**
     * Date converter
     *
     * @var \VuFind\Date\Converter
     */
    protected $dateConverter;

    /**
     * Constructor
     *
     * @param \Zend\Config\Config    $config        Main configuration
     * @param \VuFind\Date\Converter $dateConverter Date converter
     */
    public function __construct($config, $dateConverter)
    {
        $this->apiUrl = $config->LinkedEvents->api_url ?? '';
        $this->language = $config->Site->language ?? '';
        $this->dateConverter = $dateConverter;
    }

    /**
     * Return events from the LinkedEvents API
     *
     * @param array $params parameters for the query
     *
     * @return array
     */
    public function getEvents($params)
    {
       // $url = $this->apiUrl . '?publisher=pori:kaupunki';
       // $url = $this->apiUrl . '?page_size=10';
       // $url = $this->apiUrl . '?keyword=pori:topic:music&page_size=5';
        $url = $this->apiUrl . $params;
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
        //    var_dump($response);
            try {
                foreach ($response['data'] as $eventData) {
                    $event = [
                    'name' =>$this->getField($eventData, 'name'),
                    'description' => $this->getField($eventData, 'description'),
                    'imageurl' => $eventData['images'][0]['url'] ?? '',
                    'short_description' =>
                        $this->getField($eventData, 'short_description'),
                    'start_time' => $this->formatTime($eventData['start_time']),
                    'end_time' => $this->formatTime($eventData['end_time']), 
                    'start_date' => $this->formatDate($eventData['start_time']),
                    'end_date' => $this->formatDate($eventData['end_time']),
                    'info_url' => $this->getField($eventData, 'info_url'),
                    'location' => $this->getField($eventData, 'location_extra_info')
                    ];
                    $events[] = $event;
                }
            } catch (\Exception $e) {
                var_dump($e->getMessage());
            }
        }
        return $events;
    }

    /**
     * Return the value of the field in the configured language
     *
     * @param array  $object object
     * @param string $field  field
     *
     * @return string 
     */
    public function getField($object, $field)
    {
        if (!isset($object[$field])) {
            return '';
        }
        $data = $object[$field];
        return $data[$this->language] ?? '';
    }

    /**
     * Format date
     *
     * @param string $date date to format
     *
     * @return string
     */
    public function formatDate($date)
    {
        return $this->dateConverter->convertToDisplayDate('Y-m-d', $date);
    }

    /**
     * Format time
     *
     * @param string $time time to format
     *
     * @return string
     */
    public function formatTime($time)
    {
        return $this->dateConverter->convertToDisplayTime('Y-m-d', $time);
    }
}

