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
    use \VuFind\Log\LoggerAwareTrait;
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

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
     * Url helper
     *
     * @var Url
     */
    protected $url;

    /**
     * Constructor
     *
     * @param \Zend\Config\Config    $config        OrganisationInfo configuration
     * @param \VuFind\Date\Converter $dateConverter Date converter
     * @param Url                    $url           Url helper
     */
    public function __construct($config, $dateConverter, $url)
    {
        $this->apiUrl = $config->LinkedEvents->api_url ?? '';
        $this->language = $config->General->language ?? '';
        $this->dateConverter = $dateConverter;
        $this->url = $url;
    }

    /**
     * Return events from the LinkedEvents API
     *
     * @param array $params parameters for the query
     *
     * @return array array of events
     */
    public function getEvents($params)
    {
        if (empty($this->apiUrl)) {
            $this->logError('Missing LinkedEvents API URL');
            return false;
        }

        $paramArray = $params['query'];

        $url = $this->apiUrl . 'event/';
        if (!empty($paramArray['id'])) {
            $url .= $paramArray['id'] . '/?include=location,audience';
        } else {
            $url .= '?' . http_build_query($paramArray);
        }

        $client = $this->httpService->createClient($url);
        $result = $client->send();
        if (!$result->isSuccess()) {
            return $this->logError(
                'API request failed, url: ' . $url
            );
        }

        $response = json_decode($result->getBody(), true);
        $events = [];
        $result = [];
        if (!empty($response)) {
            $responseData = empty($response['data'])
                ? [$response]
                : $response['data'];
            if (!empty($responseData)) {
                foreach ($responseData as $eventData) {
                    $link = $this->url->fromRoute('linked-events-content')
                        . '?id=' . $eventData['id'];

                    $event = [
                        'id' => $eventData['id'],
                        'name' => $this->getField($eventData, 'name'),
                        'description' => $this->getField($eventData, 'description'),
                        'imageurl' => $eventData['images'][0]['url'] ?? '',
                        'short_description' =>
                            $this->getField($eventData, 'short_description'),
                        'startTime' => $this->formatTime($eventData['start_time']),
                        'endTime' => $this->formatTime($eventData['end_time']),
                        'startDate' => $this->formatDate($eventData['start_time']),
                        'endDate' => $this->formatDate($eventData['end_time']),
                        'info_url' => $this->getField($eventData, 'info_url'),
                        'location' =>
                            $this->getField($eventData, 'location_extra_info'),
                        'position' => $this->getField($eventData, 'position'),
                        'price' => $this->getField($eventData, 'offers'),
                        'audience' => $this->getField($eventData, 'audience'),
                        'link' => $link,
                    ];

                    $events[] = $event;
                }
            }
            if (isset($response['meta'])) {
                $result = [
                    'next' => $this->getField($response['meta'], 'next'),
                    'previous' => $this->getField($response['meta'], 'previous')
                ];
            }
            $result['events'] = $events;
        }
        return $result;
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
        if ($field === 'offers' && !empty($data)) {
            if ($data[0]['is_free'] === true) {
                return null;
            } else {
                $data = $data[0]['price'];
            }
        }
        if ($field === 'audience' && !empty($data)) {
            $data = $data[0]['name'] ?? '';
        }
        if ($field === 'position' && !empty($data)) {
            return $data;
        }
        if (is_array($data)) {
            return $data[$this->getLanguage()] ?? '';
        }
        return $data;
    }

    /**
     * Get language
     *
     * @return string
     */
    public function getLanguage()
    {
        if ($this->language === null) {
            $this->language = $this->translator->getLocale();
        }
        $map = ['sv' => 'se'];
        if (isset($map[$this->language])) {
            $this->language = $map[$this->language];
        }
        return $this->language;
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
