<?php

/**
 * Solr Search Parameters
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2015-2023.
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
 * @package  Search_Solr
 * @author   Mika Hatakka <mika.hatakka@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace Finna\Search\Solr;

use Laminas\Config\Config;
use VuFind\Solr\Utils;

/**
 * Solr Search Parameters
 *
 * @category VuFind
 * @package  Search_Solr
 * @author   Mika Hatakka <mika.hatakka@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class Params extends \VuFind\Search\Solr\Params
{
    use \Finna\Search\FinnaParams;
    use ParamsSharedTrait;

    /**
     * Maximum facet limit
     *
     * @var int
     */
    public const MAX_FACET_LIMIT = 100;

    /**
     * Date converter
     *
     * @var \Vufind\Date\Converter
     */
    protected $dateConverter;

    /**
     * New items facet configuration
     *
     * @var array
     */
    protected $newItemsFacets = [];

    /**
     * Query debug flag
     *
     * @var bool
     */
    protected $debugQuery = false;

    /**
     * Whether to request checkbox facet counts
     *
     * @var bool
     */
    protected $checkboxFacetCounts = false;

    // Date range index field (VuFind1)
    public const SPATIAL_DATERANGE_FIELD_VF1 = 'search_sdaterange_mv';
    public const SPATIAL_DATERANGE_FIELD_TYPE_VF1 = 'search_sdaterange_mvtype';

    // Default daterange type value
    public const DATERANGE_DEFAULT_TYPE = 'overlap';

    /**
     * Hierarchical facet limit when facets are requested.
     *
     * @var int|null
     */
    protected $hierarchicalFacetLimit = null;

    /**
     * Helper for formatting authority id filter display texts.
     *
     * @var AuthorityHelper
     */
    protected $authorityHelper = null;

    /**
     * Facet filters.
     *
     * @var array
     */
    protected $facetFilters = [];

    /**
     * Constructor
     *
     * @param \VuFind\Search\Base\Options  $options         Options to use
     * @param \VuFind\Config\PluginManager $configLoader    Config loader
     * @param HierarchicalFacetHelper      $facetHelper     Hierarchical
     * facet helper
     * @param AuthorityHelper              $authorityHelper Authority helper
     * @param \VuFind\Date\Converter       $dateConverter   Date converter
     */
    public function __construct(
        $options,
        \VuFind\Config\PluginManager $configLoader,
        HierarchicalFacetHelper $facetHelper,
        AuthorityHelper $authorityHelper,
        \VuFind\Date\Converter $dateConverter
    ) {
        parent::__construct($options, $configLoader, $facetHelper);

        $this->dateConverter = $dateConverter;
        $config = $configLoader->get($options->getFacetsIni());

        // New items facets
        if (isset($config->SpecialFacets->newItems)) {
            $this->newItemsFacets = $config->SpecialFacets->newItems->toArray();
        }

        $this->authorityHelper = $authorityHelper;
    }

    /**
     * Restore settings from a minified object found in the database.
     *
     * @param \VuFind\Search\Minified $minified Minified Search Object
     *
     * @return void
     */
    public function deminify($minified)
    {
        parent::deminify($minified);
        $dateRangeField = $this->getDateRangeSearchField();
        if (!$dateRangeField) {
            return;
        }
        // Convert any VuFind 1 spatial date range filter
        if (isset($this->filterList[self::SPATIAL_DATERANGE_FIELD_VF1])) {
            $dateRangeFilters = $this->filterList[self::SPATIAL_DATERANGE_FIELD_VF1];
            unset($this->filterList[self::SPATIAL_DATERANGE_FIELD_VF1]);

            foreach ($dateRangeFilters as $filter) {
                if ($range = $this->parseDateRangeFilter($filter)) {
                    $from = $range['from'];
                    $to = $range['to'];
                    $type = $range['type'] ?? 'overlap';
                    $filter = "$dateRangeField:$type|[$from TO $to]";
                    parent::addFilter($filter);
                }
            }
        }
    }

    /**
     * Does the object already contain the specified filter?
     *
     * @param string $filter A filter string from url : "field:value"
     *
     * @return void
     */
    public function addFilter($filter)
    {
        // Extract field and value from URL string:
        [$field, $value] = $this->parseFilter($filter);

        if (
            $field == $this->getDateRangeSearchField()
            || $field == self::SPATIAL_DATERANGE_FIELD_VF1
        ) {
            // Date range filters are processed
            // separately (see initSpatialDateRangeFilter)
            return;
        }
        parent::addFilter($filter);
    }

    /**
     * Return current date range filter.
     *
     * @return mixed false|array Filter
     */
    public function getDateRangeFilter()
    {
        $filterList = $this->getFilterList();
        foreach ($filterList as $facet => $filters) {
            foreach ($filters as $filter) {
                if ($this->isDateRangeFilter($filter['field'])) {
                    return $filter;
                }
            }
        }
        return false;
    }

    /**
     * Format a Solr date for display
     *
     * @param string $date   Date
     * @param string $domain Translation domain
     *
     * @return string
     */
    protected function formatNewItemsDateForDisplay($date, $domain)
    {
        if ($date == '' || $date == '*') {
            return ['', true];
        }
        if (preg_match('/^NOW-(\w+)/', $date, $matches)) {
            return [
                $this->translate("$domain::new_items_" . strtolower($matches[1])),
                false,
            ];
        }
        $date = substr($date, 0, 10);
        return [
            $this->dateConverter->convertToDisplayDate('Y-m-d', $date),
            true,
        ];
    }

    /**
     * Return the current filters as an array of strings ['field:filter']
     *
     * @return array $filterQuery
     */
    public function getFilterSettings()
    {
        $result = parent::getFilterSettings();

        // Special processing for date range filters
        $dateRangeField = $this->getDateRangeSearchField();
        if ($dateRangeField) {
            foreach ($result as &$filter) {
                $dateRange = strncmp(
                    $filter,
                    "$dateRangeField:",
                    strlen($dateRangeField) + 1
                ) == 0;
                if ($dateRange) {
                    [$field, $value] = $this->parseFilter($filter);
                    [$op, $range] = explode('|', $value);
                    $op = $op == 'within' ? 'Within' : 'Intersects';
                    $filter = "{!field f=$dateRangeField op=$op}$range";
                }
            }
        }
        return $result;
    }

    /**
     * Create search backend parameters for advanced features.
     *
     * @return ParamBag
     */
    public function getBackendParameters()
    {
        $result = parent::getBackendParameters();

        if ($this->debugQuery) {
            $result->add('debugQuery', 'true');
        }

        // Restore original sort if we have geographic filters
        $sort = $this->normalizeSort($this->getSort() ?? '');
        $newSort = $result->get('sort');
        if ($newSort && $newSort[0] != $sort) {
            $filters = $result->get('fq');
            if (null !== $filters) {
                foreach ($filters as $filter) {
                    if (strncmp($filter, '{!geofilt ', 10) == 0) {
                        $newSort[0] = $this->normalizeSort($sort);
                        $result->set('sort', $newSort);
                        break;
                    }
                }
            }
        }

        foreach ($this->facetFilters as $filter => $value) {
            $result->add($filter, $value);
        }

        return $result;
    }

    /**
     * Return current facet configurations.
     * Add checkbox facets to list.
     *
     * @return array $facetSet
     */
    public function getFacetSettings()
    {
        $facetSet = parent::getFacetSettings();
        if (
            !empty($facetSet)
            && null !== $this->hierarchicalFacetLimit
            && $this->facetLimit !== $this->hierarchicalFacetLimit
        ) {
            $hierarchicalFacets = $this->getOptions()->getHierarchicalFacets();
            foreach ($hierarchicalFacets as $field) {
                $facetSet["f.{$field}.facet.limit"] = $this->hierarchicalFacetLimit;
            }
        }

        // For checkbox counts
        if ($this->checkboxFacetCounts && !empty($this->checkboxFacets)) {
            foreach (array_keys($this->checkboxFacets) as $facetField) {
                $facetField = '{!ex=' . $facetField . '_filter}' . $facetField;
                $facetSet['field'][] = $facetField;
            }
        }

        return $facetSet;
    }

    /**
     * Pull the search parameters
     *
     * @param \Laminas\Stdlib\Parameters $request Parameter object representing user
     * request.
     *
     * @return void
     */
    public function initFromRequest($request)
    {
        // Check for advanced search from VuFind1 missing join and/or bool parameter:
        if (null === $request->get('lookfor')) {
            if (null === $request->get('join')) {
                $request->set('join', 'AND');
            }
            $bool0 = $request->get('bool0');
            if (!is_array($bool0) || empty(array_filter($bool0))) {
                $request->set('bool0', ['AND']);
            }
        }

        // Check for VuFind1 orfilters and convert them:
        if ($orFilters = $request->get('orfilter')) {
            $filters = $request->get('filter', []);
            foreach ($orFilters as $filter) {
                $filters[] = "~$filter";
            }
            $request->set('filter', $filters);
            $request->set('orfilter', null);
        }

        parent::initFromRequest($request);

        $this->setDebugQuery($request->get('debugSolrQuery', false));
    }

    /**
     * Initialize coordinate filter (coordinates, VuFind1)
     *
     * @param \Laminas\Stdlib\Parameters $request Parameter object representing user
     * request.
     *
     * @return void
     */
    public function initCoordinateFilter($request)
    {
        $coordinates = $request->get('coordinates');
        if (null === $coordinates) {
            return;
        }

        // Convert simple coordinates to a polygon
        $simple = preg_match(
            '/^([\d\.]+)\s+([\d\.]+)\s+([\d\.]+)\s+([\d\.]+)$/',
            $coordinates,
            $matches
        );
        if ($simple) {
            [, $minX, $minY, $maxX, $maxY] = $matches;
            $coordinates = "POLYGON(($minX $maxY,$maxX $maxY,$maxX $minY"
                . ",$minX $minY,$minX $maxY))";
        }
        $this->addFilter(
            '{!score=none}location_geo:"Intersects('
            . str_replace('"', '\"', $coordinates) . ')"'
        );
    }

    /**
     * Initialize date range filter (search_daterange_mv)
     *
     * @param \Laminas\Stdlib\Parameters $request Parameter object representing user
     * request.
     *
     * @return void
     */
    public function initSpatialDateRangeFilter($request)
    {
        $dateRangeField = $this->getDateRangeSearchField();
        if (!$dateRangeField) {
            return;
        }
        $type = $request->get("{$dateRangeField}_type");
        if (!$type) {
            // VuFind 1
            $type = $request->get(self::SPATIAL_DATERANGE_FIELD_TYPE_VF1);
        }
        if (!$type) {
            $type = self::DATERANGE_DEFAULT_TYPE;
        }

        $from = $to = null;
        $found = false;
        // Date range filter
        if (($reqFilters = $request->get('filter')) && is_array($reqFilters)) {
            foreach ($reqFilters as $f) {
                [$field, $value] = $this->parseFilter($f);
                if (
                    $field == $dateRangeField
                    || $field == self::SPATIAL_DATERANGE_FIELD_VF1
                ) {
                    if ($range = $this->parseDateRangeFilter($f)) {
                        $from = $range['from'];
                        $to = $range['to'];
                        if (
                            isset($range['type'])
                            && $range['type'] !== self::DATERANGE_DEFAULT_TYPE
                        ) {
                            $type = $range['type'];
                        }
                        $found = true;
                        break;
                    }
                }
            }
        }

        // Uninitialized VuFind1 date range query
        if (!$found && $request->get('sdaterange')) {
            // Search for VuFind1 search_sdaterange_mvfrom, search_sdaterange_mvto
            $from = $request->get('search_sdaterange_mvfrom');
            $to = $request->get('search_sdaterange_mvto');
            if (!empty($from) || !empty($to)) {
                if (empty($from)) {
                    $from = -9999;
                }
                if (empty($to)) {
                    $to = 9999;
                }
                $found = true;
            }
        }

        if (!$found) {
            return;
        }

        // Add filter. The final Solr filter is constructed in getFilterSettings.
        $filter = "$dateRangeField:$type|[$from TO $to]";
        parent::addFilter($filter);
    }

    /**
     * Get query debug flag status
     *
     * @return bool
     */
    public function getDebugQuery()
    {
        return $this->debugQuery;
    }

    /**
     * Enable or disable query debugging
     *
     * @param bool $value Whether to enable debugging
     *
     * @return void
     */
    public function setDebugQuery($value)
    {
        $this->debugQuery = $value;
    }

    /**
     * Whether to request checkbox facet counts
     *
     * @return bool
     */
    public function getCheckboxFacetCounts()
    {
        return $this->checkboxFacetCounts;
    }

    /**
     * Whether to request checkbox facet counts
     *
     * @param bool $value Enable or disable
     *
     * @return void
     */
    public function setCheckboxFacetCounts($value)
    {
        $this->checkboxFacetCounts = $value;
    }

    /**
     * Remove all hidden filters
     *
     * @return void
     */
    public function clearHiddenFilters()
    {
        $this->hiddenFilters = [];
    }

    /**
     * Get current limit for hierarchical facets
     *
     * @return int
     */
    public function getHierarchicalFacetLimit()
    {
        return $this->hierarchicalFacetLimit;
    }

    /**
     * Set limit for hierarchical facets
     *
     * @param int $limit New limit
     *
     * @return void
     */
    public function setHierarchicalFacetLimit($limit)
    {
        $this->hierarchicalFacetLimit = $limit;
    }

    /**
     * Filter facets by prefix.
     *
     * @param string $field Facet field
     * @param string $value Facet value
     *
     * @return void
     */
    public function addFacetFilter($field, $value)
    {
        $this->facetFilters["f.{$field}.facet.prefix"] = $value;
    }

    /**
     * Return active author id filters.
     *
     * @param boolean $includeRole Return role with author id
     *
     * @return mixed null|array
     */
    public function getAuthorIdFilter($includeRole = false)
    {
        $result = [];
        foreach ($this->getFilterList() as $key => $val) {
            foreach ($val as $filterItem) {
                $filter = $filterItem['value'] ?? null;
                if (!$filter) {
                    continue;
                }
                $field = $filterItem['field'];
                if (
                    in_array(
                        $field,
                        [AuthorityHelper::AUTHOR2_ID_FACET,
                        AuthorityHelper::TOPIC_ID_FACET]
                    )
                ) {
                    // Author id filter
                    $result[] = $filter;
                } elseif ($field === AuthorityHelper::AUTHOR_ID_ROLE_FACET) {
                    // Author id-role filter
                    if ($includeRole) {
                        $result[] = $filter;
                    } else {
                        [$id, $role]
                            = $this->authorityHelper->extractRole($filter);
                        $result[] = $id;
                    }
                }
            }
        }
        return !empty($result) ? $result : null;
    }

    /**
     * Format a single filter for use in getFilterList().
     *
     * @param string $field     Field name
     * @param string $value     Field value
     * @param string $operator  Operator (AND/OR/NOT)
     * @param bool   $translate Should we translate the label?
     *
     * @return array
     */
    protected function formatFilterListEntry($field, $value, $operator, $translate)
    {
        if (
            !in_array($field, $this->newItemsFacets)
            || !($range = Utils::parseRange($value))
        ) {
            if (
                $translate
                && in_array($field, $this->getOptions()->getHierarchicalFacets())
            ) {
                return $this->translateHierarchicalFacetFilter(
                    $field,
                    $value,
                    $operator
                );
            }
            $result = parent::formatFilterListEntry(
                $field,
                $value,
                $operator,
                $translate
            );

            if ($this->isDateRangeFilter($field)) {
                return $this->formatDateRangeFilterListEntry(
                    $result,
                    $field,
                    $value
                );
            }
            if ($this->isGeographicFilter($field)) {
                return $this->formatGeographicFilterListEntry(
                    $result,
                    $field,
                    $value
                );
            }

            return $this->formatAuthorIdFilterListEntry($result, $field, $value);
        }

        $domain = $this->getOptions()->getTextDomainForTranslatedFacet($field);
        [$from, $fromDate] = $this->formatNewItemsDateForDisplay(
            $range['from'],
            $domain
        );
        [$to, $toDate] = $this->formatNewItemsDateForDisplay(
            $range['to'],
            $domain
        );
        $ndash = html_entity_decode('&#x2013;', ENT_NOQUOTES, 'UTF-8');
        if ($fromDate && $toDate) {
            $displayText = $from ? "$from $ndash" : $ndash;
            $displayText .= $to ? " $to" : '';
        } else {
            $displayText = $from;
            $displayText .= $to ? " $ndash $to" : '';
        }

        return compact('value', 'displayText', 'field', 'operator');
    }

    /**
     * Get a user-friendly string to describe the provided facet field.
     *
     * @param string $field   Facet field name.
     * @param string $value   Facet value.
     * @param string $default Default field name (null for default behavior).
     *
     * @return string         Human-readable description of field.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getFacetLabel($field, $value = null, $default = null)
    {
        if ($field === AuthorityHelper::AUTHOR2_ID_FACET) {
            return 'authority_id_label';
        }
        if (strpos($field, '{!geofilt ') === 0) {
            return 'Geographical Area';
        }
        return parent::getFacetLabel($field, $value, $default);
    }

    /**
     * Is author id filter active?
     *
     * @return boolean
     */
    public function hasAuthorIdFilter()
    {
        foreach ($this->getFilterList() as $field => $facets) {
            foreach ($facets as $facet) {
                if (
                    in_array(
                        $facet['field'],
                        $this->authorityHelper->getAuthorIdFacets()
                    )
                ) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Add filters to the object based on values found in the request object.
     *
     * @param \Laminas\Stdlib\Parameters $request Parameter object representing user
     * request.
     *
     * @return void
     */
    protected function initFilters($request)
    {
        parent::initFilters($request);
        $this->initSpatialDateRangeFilter($request);
        $this->initNewItemsFilter($request);
        $this->initCoordinateFilter($request);
    }

    /**
     * Initialize new items filter (first_indexed)
     *
     * @param \Laminas\Stdlib\Parameters $request Parameter object representing user
     * request.
     *
     * @return void
     */
    protected function initNewItemsFilter($request)
    {
        // first_indexed filter automatically included, no query param required
        // (compatible with Finna 1 implementation)
        $from = $request->get('first_indexedfrom', '');
        $from = $this->formatDateForFullDateRange($from);

        if ($from != '*') {
            $rangeFacet
                = $this->buildFullDateRangeFilter('first_indexed', $from, '*');
            $this->addFilter($rangeFacet);
        }
    }

    /**
     * Initialize facet limit from a Config object.
     *
     * @param Config $config Configuration
     *
     * @return void
     */
    protected function initFacetLimitsFromConfig(Config $config = null)
    {
        parent::initFacetLimitsFromConfig($config);
        $this->constrainFacetLimits();
    }

    /**
     * Constrain facet limits to 1-100.
     *
     * @return void
     */
    protected function constrainFacetLimits(): void
    {
        $this->facetLimit
            = max(min((int)$this->facetLimit, static::MAX_FACET_LIMIT), 1);
        foreach ($this->facetLimitByField as &$value) {
            $value = max(min((int)$value, static::MAX_FACET_LIMIT), 1);
        }
        unset($value);
    }

    /**
     * Set the sorting value (note: sort will be set to default if an illegal
     * or empty value is passed in).
     *
     * @param string $sort  New sort value (null for default)
     * @param bool   $force Set sort value without validating it?
     *
     * @return void
     */
    public function setSort($sort, $force = false)
    {
        if (!$force) {
            // Check if we need to convert the sort to a currently valid option
            // (it must be a prefix of a currently valid option):
            $validOptions = array_keys($this->getOptions()->getSortOptions());
            if (!empty($sort) && !in_array($sort, $validOptions)) {
                $sortLen = strlen($sort);
                foreach ($validOptions as $valid) {
                    if (
                        strlen($valid) > $sortLen
                        && strncmp($sort, $valid, $sortLen) === 0
                    ) {
                        $sort = $valid;
                        break;
                    }
                }
            }
        }

        parent::setSort($sort, $force);
    }
}
