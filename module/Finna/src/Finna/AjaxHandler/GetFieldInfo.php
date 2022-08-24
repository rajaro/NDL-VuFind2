<?php
/**
 * AJAX handler for getting information for a field popover.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2022.
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
 * @package  AJAX
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace Finna\AjaxHandler;

use Finna\Db\Table\FinnaCache;
use Laminas\Config\Config;
use Laminas\Log\LoggerAwareInterface;
use Laminas\Mvc\Controller\Plugin\Params;
use VuFind\Log\LoggerAwareTrait;
use VuFind\Record\Loader;
use VuFind\Session\Settings as SessionSettings;
use VuFind\View\Helper\Root\Record;
use VuFindHttp\HttpService;
use VuFindSearch\ParamBag;

/**
 * AJAX handler for getting information for a field popover.
 *
 * @category VuFind
 * @package  AJAX
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class GetFieldInfo extends \VuFind\AjaxHandler\AbstractBase
    implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Main configuration
     *
     * @var Config
     */
    protected $config;

    /**
     * Record loader
     *
     * @var Loader
     */
    protected $loader;

    /**
     * Record plugin
     *
     * @var Record
     */
    protected $recordPlugin;

    /**
     * HTTP service
     *
     * @var HttpService
     */
    protected $httpService;

    /**
     * Cache table
     *
     * @var FinnaCache
     */
    protected $finnaCache;

    /**
     * Constructor
     *
     * @param Config          $config Main configuration
     * @param SessionSettings $ss     Session settings
     * @param Loader          $loader Record loader
     * @param Record          $rp     Record plugin
     * @param HttpService     $http   HTTP Service
     * @param FinnaCache      $cache  Cache table
     */
    public function __construct(
        Config $config,
        SessionSettings $ss,
        Loader $loader,
        Record $rp,
        HttpService $http,
        FinnaCache $cache
    ) {
        $this->config = $config;
        $this->sessionSettings = $ss;
        $this->loader = $loader;
        $this->recordPlugin = $rp;
        $this->httpService = $http;
        $this->finnaCache = $cache;
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     */
    public function handleRequest(Params $params)
    {
        $this->disableSessionWrites(); // avoid session write timing bug

        $ids = json_decode($params->fromQuery('ids', '{}'), true);
        $authIds = json_decode($params->fromQuery('authIds', '{}'), true);
        $source = $params->fromQuery('source');
        $recordId = $params->fromQuery('recordId');
        $type = $params->fromQuery('type');
        $label = $params->fromQuery('label') ?? '';

        if (!$ids || !$type) {
            return $this->formatResponse('', self::STATUS_HTTP_BAD_REQUEST);
        }

        $params = new ParamBag();
        $params->set('authorityType', $type);
        $params->set('recordSource', $source);
        $authority = null;
        $authorityFields = $this->config->LinkPopovers->authority_fields
            ? $this->config->LinkPopovers->authority_fields->toArray() : [];
        if ($authIds && $authIds[0] && preg_match('/^[\w_-]+\./', $authIds[0])
            && $authorityFields
        ) {
            try {
                $authority = $this->loader->load(
                    $authIds[0],
                    'SolrAuth',
                    false,
                    $params
                );
            } catch (\VuFind\Exception\RecordMissing $e) {
                // Ignore a missing authority record
            }
        }
        try {
            $driver = $this->loader->load($recordId, $source);
        } catch (\VuFind\Exception\RecordMissing $e) {
            return $this->formatResponse('');
        }

        // Fetch any enrichment data by the first ID:
        $enrichmentData = $this->getEnrichmentData($ids[0], $label);

        $html = ($this->recordPlugin)($driver)->renderTemplate(
            'ajax-field-info.phtml',
            compact(
                'ids',
                'authIds',
                'authority',
                'authorityFields',
                'type',
                'enrichmentData',
                'driver'
            )
        );

        return $this->formatResponse(compact('html'));
    }

    /**
     * Get enrichment data from Skosmos
     *
     * @param string $id    Identifier
     * @param string $label Label
     *
     * @return array
     */
    protected function getEnrichmentData(string $id, string $label): array
    {
        if (empty($this->config->LinkPopovers->skosmos)
            || empty($this->config->LinkPopovers->skosmos_base_url)
        ) {
            return [];
        }

        // Clean up any invalid characters from the id:
        $id = trim(
            str_replace(
                ['|', '!', '"', '#', '€', '$', '%', '&', '<', '>'],
                [],
                $id
            )
        );
        if (!$id) {
            return [];
        }

        // Check if the url has an allowed prefix:
        $match = false;
        foreach ($this->config->LinkPopovers->skosmos_id_prefix_allowed_list
            as $prefix
        ) {
            if (strncmp($id, $prefix, strlen($prefix)) === 0) {
                $match = true;
                break;
            }
        }
        if (!$match) {
            return [];
        }

        // Check cache:
        $cacheId = strlen($id) < 255 ? $id : md5($id);
        if ($cached = $this->finnaCache->getByResourceId($cacheId)) {
            return $this->parseSkosmos($cached['data'], $id, $label);
        }

        // Fetch from external API:
        if (!($data = $this->fetchFromSkosmos($id))) {
            return [];
        }

        $row = $this->finnaCache->createRow();
        $row['mtime'] = time();
        $row['resource_id'] = $cacheId;
        $row['data'] = $data;
        $row->save();

        return $this->parseSkosmos($data, $id, $label);
    }

    /**
     * Fetch data for an identifier from Skosmos
     *
     * @param string $id Identifier
     *
     * @return ?string
     */
    protected function fetchFromSkosmos(string $id): ?string
    {
        $url = $this->config->LinkPopovers->skosmos_base_url;
        if (substr($url, -1) !== '/') {
            $url .= '/';
        }
        $url .= 'v1/data?format=application/json&uri=' . urlencode($id);
        try {
            $response = $this->httpService->get(
                $url,
                [],
                10,
                ['Accept' => 'application/json']
            );
        } catch (\Exception $e) {
            $this->logError("Skosmos request for '$url' failed: " . (string)$e);
            return null;
        }

        $code = $response->getStatusCode();
        if ($code >= 300 && $code != 404) {
            $this->logError(
                "Skosmos request for '$url' failed: $code: "
                . $response->getReasonPhrase()
            );
            return null;
        }
        return $response->getBody();
    }

    /**
     * Parse Skosmos data and return labels
     *
     * @param string $response     Skoskos response
     * @param string $id           Requested id
     * @param string $displayLabel Display label
     *
     * @return array
     */
    protected function parseSkosmos(
        string $response,
        string $id,
        string $displayLabel
    ): array {
        $result = [
            'labels' => [],
            'altLabels' => [],
            'otherLanguageLabels' => [],
            'otherLanguageAltLabels' => [],
        ];
        if (!($data = json_decode($response, true))) {
            return $result;
        }
        if (!isset($data['graph'])) {
            return $result;
        }

        $pref = [];
        $alt = [];
        $labelLang = '';
        foreach ($data['graph'] as $item) {
            if (!in_array('skos:Concept', (array)($item['type'] ?? []))) {
                continue;
            }

            if ($item['uri'] === $id) {
                foreach ($item['prefLabel'] ?? [] as $label) {
                    if (!($value = $label['value'] ?? '')) {
                        continue;
                    }
                    $lng = $label['lang'] ?? '-';
                    // Try to determine the language of the display label:
                    if ($label === $displayLabel) {
                        $labelLang = $lng;
                    } else {
                        $pref[$lng][] = $value;
                    }
                }

                $altLabels = isset($item['altLabel']['value'])
                    ? [$item['altLabel']] : ($item['altLabel'] ?? []);

                foreach ($altLabels as $label) {
                    if (!($value = $label['value'] ?? '')) {
                        continue;
                    }
                    $lng = $label['lang'] ?? '-';
                    $alt[$lng][] = $value;
                }
            }

            foreach ($item['exactMatch'] ?? [] as $exactMatch) {
                $matchId = is_array($exactMatch)
                    ? ($exactMatch['uri'] ?? null)
                    : $exactMatch;
                if (!$matchId) {
                    continue;
                }

                // Check if exact match id prefix is allowed:
                $allowed = false;
                foreach ($this->config->LinkPopovers->skosmos_id_prefix_exact_matches
                    as $prefix
                ) {
                    if (strncmp($matchId, $prefix, strlen($prefix)) === 0) {
                        $allowed = true;
                        break;
                    }
                }
                if (!$allowed || !($match = $this->fetchFromSkosmos($matchId))) {
                    continue;
                }
                $matchData = json_decode($match, true);

                foreach ($matchData['graph'] ?? [] as $matchItem) {
                    if (!in_array('skos:Concept', (array)($matchItem['type'] ?? []))
                    ) {
                        continue;
                    }
                    if (($matchItem['uri'] ?? null) !== $matchId) {
                        continue;
                    }

                    foreach ($item['prefLabel'] ?? [] as $label) {
                        if (!($value = $label['value'] ?? '')) {
                            continue;
                        }
                        $lng = $label['lang'] ?? '-';
                        $pref[$lng][] = $value;
                    }

                    foreach ($item['altLabel'] ?? [] as $label) {
                        if (!($value = $label['value'] ?? '')) {
                            continue;
                        }
                        $lng = $label['lang'] ?? '-';
                        $alt[$lng][] = $value;
                    }
                }
            }
        }

        // Remove duplicates and alternate terms that exist in preferred ones:
        foreach ($pref as $lng => $values) {
            $pref[$lng] = array_values(array_unique($values));
        }
        foreach ($alt as $lng => $values) {
            $alt[$lng] = array_values(
                array_diff(
                    array_unique($values),
                    $pref[$lng] ?? []
                )
            );
        }
        $pref = array_filter($pref);
        $alt = array_filter($alt);

        // Assemble results:
        if (!$labelLang) {
            $labelLang = 'fi';
        }
        if (isset($pref[$labelLang])) {
            $result['labels'] = $pref[$labelLang];
            unset($pref[$labelLang]);
        }
        if (isset($alt[$labelLang])) {
            $result['altLabels'] = $alt[$labelLang];
            unset($alt[$labelLang]);
        }
        $result['otherLanguageLabels'] = $pref;
        $result['otherLanguageAltLabels'] = $alt;

        return $result;
    }
}
