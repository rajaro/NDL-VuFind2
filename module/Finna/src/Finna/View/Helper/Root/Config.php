<?php

/**
 * Config view helper
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2010.
 * Copyright (C) The National Library of Finland 2015-2019.
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
 * @package  View_Helpers
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */

namespace Finna\View\Helper\Root;

/**
 * Config view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class Config extends \VuFind\View\Helper\Root\Config
{
    /**
     * Is video embedding on record page enabled
     *
     * @return boolean
     */
    public function inlineVideoEnabled()
    {
        return !empty($this->get('config')->Record->embedVideo);
    }

    /**
     * Get default facet fields
     *
     * @return array
     */
    public function getFacetFields(): array
    {
        $config = $this->get('facets')->Results ?? null;
        return $config ? $config->toArray() : [];
    }

    /**
     * Get default checkbox facets
     *
     * @return array
     */
    public function getCheckboxFacets(): array
    {
        $config = $this->get('facets')->CheckboxFacets ?? null;
        return $config ? $config->toArray() : [];
    }
}
