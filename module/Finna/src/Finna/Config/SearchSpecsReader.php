<?php
/**
 * VuFind SearchSpecs Configuration Reader
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2010.
 * Copyright (C) The National Library of Finland 2015.
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
 * @package  Config
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace Finna\Config;

use VuFind\Config\Locator;

/**
 * VuFind SearchSpecs Configuration Reader
 *
 * @category VuFind
 * @package  Config
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class SearchSpecsReader extends \VuFind\Config\SearchSpecsReader
{
    /**
     * Cache for loaded searchspecs
     *
     * @var array
     */
    protected $searchSpecs = [];

    /**
     * Return search specs
     *
     * @param string  $filename        Config file name
     * @param boolean $useLocalConfig  Use local configuration if available
     * @param boolean $ignoreFileCache Read from file even if config has been cached.
     *
     * @return array
     */
    public function get($filename, $useLocalConfig = true, $ignoreFileCache = false)
    {
        // Load data if it is not already in the object's cache:
        if ($ignoreFileCache || !isset($this->searchSpecs[$filename])) {
            // Connect to searchspecs cache:
            $cache = (null !== $this->cacheManager)
                ? $this->cacheManager->getCache('searchspecs') : false;

            // Determine full configuration file path:
            $fullpath = Locator::getBaseConfigPath($filename);
            $local = Locator::getLocalConfigPath($filename);
            $finna = Locator::getLocalConfigPath($filename, 'config/finna');

            // Generate cache key:
            $cacheKey = $filename . '-'
                . (file_exists($fullpath) ? filemtime($fullpath) : 0);
            if ($useLocalConfig && !empty($finna)) {
                $cacheKey .= '-finna-' . filemtime($local);
            }
            if ($useLocalConfig && !empty($local)) {
                $cacheKey .= '-local-' . filemtime($local);
            }
            $cacheKey = md5($cacheKey);

            // Generate data if not found in cache:
            if ($cache === false || !($results = $cache->getItem($cacheKey))) {
                $results = file_exists($fullpath)
                    ? $this->parseYaml($fullpath) : [];
                if (!empty($finna)) {
                    $localResults = $this->parseYaml($finna);
                    foreach ($localResults as $key => $value) {
                        $results[$key] = $value;
                    }
                }
                if (!empty($local)) {
                    $localResults = $this->parseYaml($local);
                    if (!empty($localResults)) {
                        foreach ($localResults as $key => $value) {
                            $results[$key] = $value;
                        }
                    }
                }
                if ($cache !== false) {
                    $cache->setItem($cacheKey, $results);
                }
            }
            $this->searchSpecs[$filename] = $results;
        }

        return $this->searchSpecs[$filename];
    }
}
