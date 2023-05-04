<?php

/**
 * Video handler class.
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
 * @package  Video
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */

namespace Finna\Video;

use Finna\Video\Handler\PluginManager as HandlerPluginManager;

/**
 * Video handler class.
 *
 * @category VuFind
 * @package  Video
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class Video
{
    use \VuFind\Log\LoggerAwareTrait;

    /**
     * HandlerPluginManager
     *
     * @var HandlerPluginManager
     */
    protected $pluginManager;

    /**
     * Data source configuration
     *
     * @var \Laminas\Config\Config
     */
    protected $config;

    /**
     * Constructor.
     *
     * @param HandlerPluginManager   $pluginManager Instanciated Handler
     * @param \Laminas\Config\Config $config        Datasource config
     */
    public function __construct(
        HandlerPluginManager $pluginManager,
        \Laminas\Config\Config $config
    ) {
        $this->pluginManager = $pluginManager;
        $this->config = $config;
    }

    /**
     * Get video handler or null if not configured properly
     *
     * @param string $source Datasource
     *
     * @return Handler\AbstractBase || null
     */
    public function getHandler(string $source): ?Handler\AbstractBase
    {
        if (!($handlerName = $this->getHandlerName($source))) {
            $handlerName = 'Default';
        }
        if (!$this->pluginManager->has($handlerName)) {
            $this->logError("Stream handler $handlerName not found for $source");
            return null;
        }

        $handler = $this->pluginManager->get($handlerName);
        $handler->init($this->getConfig($source), $source);
        if ($handler->verifyConfig()) {
            return $handler;
        }
        return null;
    }

    /**
     * Get video handler name.
     *
     * @param string $source Datasource
     *
     * @return string
     */
    public function getHandlerName(string $source): string
    {
        if ($config = $this->getConfig($source)) {
            return $config['handler'] ?? '';
        }
        return '';
    }

    /**
     * Check if video is enabled for a datasource.
     *
     * @param string $source Datasource
     *
     * @return bool
     */
    public function isEnabled(string $source): bool
    {
        return $this->getConfig($source) ? true : false;
    }

    /**
     * Get video handler configuration for a datasource.
     *
     * @param string $source Datasource
     *
     * @return array
     */
    protected function getConfig(string $source): array
    {
        if ($config = $this->config[$source]['video'] ?? []) {
            $config = $config->toArray();
        }
        if ($sources = $this->config[$source]['video_sources'] ?? []) {
            $config['video_sources'] = $sources->toArray();
        }
        return $config;
    }
}
