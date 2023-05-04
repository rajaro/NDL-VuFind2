<?php

/**
 * Holdings callnumber view helper
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2016.
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
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace Finna\View\Helper\Root;

use Finna\LocationService\LocationService;

/**
 * Holdings callnumber view helper
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class Callnumber extends \Laminas\View\Helper\AbstractHelper
{
    /**
     * Location Service.
     *
     * @var LocationService
     */
    protected $locationService = null;

    /**
     * Constructor
     *
     * @param LocationService $locationService Location Service
     * of Finland Location Service
     */
    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    /**
     * Returns HTML for a holding callnumber.
     *
     * @param string $source             Record source
     * @param string $title              Record title
     * @param string $callnumber         Callnumber
     * @param string $collection         Collection
     * @param string $location           Location
     * @param string $language           Language
     * @param string $page               Page (record|results)
     * @param array  $fields             Additional data fields
     * @param bool   $useLocationService Whether to display location service links
     * (if available)
     *
     * @return string
     */
    public function callnumber(
        $source,
        $title,
        $callnumber,
        $collection,
        $location,
        $language,
        $page = 'record',
        $fields = [],
        $useLocationService = true
    ) {
        $params = compact(
            'callnumber',
            'collection',
            'location',
            'title',
            'page',
            'source'
        );

        $config = $useLocationService ? $this->locationService->getConfig(
            $source,
            $title,
            $callnumber,
            $collection,
            $location,
            $language,
            $fields
        ) : null;

        if ($config) {
            $params['fields'] = $fields;
            $params['locationServiceUrl'] = $config['url'];
            $params['locationServiceModal'] = $config['modal'];
            // Extract the page from something like 'results' or 'results-online':
            [$basePage] = explode('-', $page);
            $section = $basePage === 'results' ? 'qrCodeResults' : 'qrCodeRecord';
            $params['qrCode'] = $config[$section];
        }
        return $this->getView()->render('Helpers/holding-callnumber.phtml', $params);
    }

    /**
     * Check if QR-code option is enabled.
     *
     * @return boolean
     */
    public function useQrCode()
    {
        return $this->locationService->useQrCode();
    }
}
