<?php

/**
 * Finna Cache Manager
 *
 * PHP version 7
 *
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
 * @package  Cache
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */

namespace Finna\Cache;

use Laminas\Cache\Service\StorageAdapterFactory;
use Laminas\Config\Config;

/**
 * Finna Cache Manager
 *
 * @category VuFind
 * @package  Cache
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Page
 */
class Manager extends \VuFind\Cache\Manager
{
    /**
     * Constructor
     *
     * @param Config                $config       Main VuFind configuration
     * @param Config                $searchConfig Search configuration
     * @param StorageAdapterFactory $factory      Cache storage adapter factory
     */
    public function __construct(
        Config $config,
        Config $searchConfig,
        StorageAdapterFactory $factory
    ) {
        parent::__construct($config, $searchConfig, $factory);

        $cacheBase = $this->getCacheDir();
        $ids = ['feed', 'description', 'organisation-info', 'stylesheet'];
        foreach ($ids as $cache) {
            $this->createFileCache($cache, $cacheBase . $cache . 's');
        }
    }
}
