<?php

/**
 * Solr aspect of the Search Multi-class (Options)
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2015-2020.
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
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace Finna\Search\Solr;

/**
 * Solr Search Options
 *
 * @category VuFind
 * @package  Search_Solr
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class Options extends \VuFind\Search\Solr\Options
{
    use \Finna\Search\FinnaOptions;
    use \Finna\I18n\Translator\TranslatorAwareTrait;

    /**
     * Date range visualization settings
     *
     * @var string
     */
    protected $dateRangeVis;

    /**
     * Whether to display record versions
     *
     * Finna: keep it false by default for now
     *
     * @var bool
     */
    protected $displayRecordVersions = false;

    /**
     * Constructor
     *
     * @param \VuFind\Config\PluginManager $configLoader Config loader
     */
    public function __construct(\VuFind\Config\PluginManager $configLoader)
    {
        parent::__construct($configLoader);

        $facetSettings = $this->configLoader->get($this->facetsIni);
        $this->dateRangeVis = $facetSettings->SpecialFacets->dateRangeVis ?? '';

        // Back-compatibility for display_versions setting in config.ini:
        $searchSettings = $configLoader->get($this->searchIni);
        if (!isset($searchSettings->General->display_versions)) {
            $config = $configLoader->get($this->mainIni);
            if (isset($config->Record->display_versions)) {
                $this->displayRecordVersions
                    = (bool)$config->Record->display_versions;
            }
        }

        // Back-compatibility for hierarchical facet filters:
        $this->hierarchicalExcludeFilters
            = $facetSettings?->HierarchicalExcludeFilters?->toArray()
            ?? $facetSettings?->ExcludeFilters?->toArray()
            ?? [];
        $this->hierarchicalFacetFilters
            = $facetSettings?->HierarchicalFacetFilters?->toArray()
            ?? $facetSettings?->FacetFilters?->toArray()
            ?? [];
    }

    /**
     * Get the field used for date range search
     *
     * @return string
     */
    public function getDateRangeSearchField()
    {
        [$field] = explode(':', $this->dateRangeVis);
        return $field;
    }

    /**
     * Get the field used for date range visualization
     *
     * @return string
     */
    public function getDateRangeVisualizationField()
    {
        $fields = explode(':', $this->dateRangeVis);
        return $fields[1] ?? '';
    }

    /**
     * Translate a field name to a displayable string for rendering a query in
     * human-readable format:
     *
     * @param string $field Field name to display.
     *
     * @return string       Human-readable version of field name.
     */
    public function getHumanReadableFieldName($field)
    {
        $result = parent::getHumanReadableFieldName($field);
        if ($result != $field) {
            return $result;
        }
        return $this->translate("search_field_$field", null, $field);
    }
}
