<?php

/**
 * Database handler factory.
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
 * @package  Statistics
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace Finna\Statistics\Driver;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;

/**
 * Database handler factory.
 *
 * @category VuFind
 * @package  Statistics
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class DatabaseFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param ContainerInterface $container     Service manager
     * @param string             $requestedName Service being created
     * @param null|array         $options       Extra options (optional)
     *
     * @return object
     *
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     * creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        if (!empty($options)) {
            throw new \Exception('Unexpected options passed to factory.');
        }

        $tableManager = $container->get(\VuFind\Db\Table\PluginManager::class);
        return new $requestedName(
            $tableManager->get(\Finna\Db\Table\FinnaSessionStats::class),
            $tableManager->get(\Finna\Db\Table\FinnaPageViewStats::class),
            $tableManager->get(\Finna\Db\Table\FinnaRecordStats::class),
            $tableManager->get(\Finna\Db\Table\FinnaRecordStatsLog::class),
            $tableManager->get(\Finna\Db\Table\FinnaRecordView::class),
            $tableManager->get(\Finna\Db\Table\FinnaRecordViewInstView::class),
            $tableManager->get(\Finna\Db\Table\FinnaRecordViewRecord::class),
            $tableManager->get(\Finna\Db\Table\FinnaRecordViewRecordFormat::class),
            $tableManager->get(\Finna\Db\Table\FinnaRecordViewRecordRights::class)
        );
    }
}
