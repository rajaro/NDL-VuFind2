<?php

/**
 * Blender aspect of the Search Multi-class (Options)
 *
 * PHP version 8
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
 * @package  Search_Blender
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace Finna\Search\Blender;

/**
 * Blender Search Options
 *
 * @category VuFind
 * @package  Search_Blender
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class Options extends \VuFind\Search\Blender\Options
{
    /**
     * Date range visualization settings
     *
     * @var string
     */
    protected $dateRangeVis;

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
}
