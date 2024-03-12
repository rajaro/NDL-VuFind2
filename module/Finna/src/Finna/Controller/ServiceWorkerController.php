<?php

/**
 * Service Worker Controller
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2024.
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
 * @package  Controller
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */

namespace Finna\Controller;

use Laminas\Config\Config;
use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 * Service Worker Controller
 *
 * @category VuFind
 * @package  Controller
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
class ServiceWorkerController extends \VuFind\Controller\AbstractBase
{
    /**
     * VuFind configuration
     *
     * @var Config
     */
    protected $config;

    /**
     * Image to display on the offline page.
     *
     * @var string
     */
    protected $offlineImage;

    /**
     * Constructor
     *
     * @param ServiceLocatorInterface $sm     Service manager
     * @param Config                  $config VuFind configuration
     */
    public function __construct(ServiceLocatorInterface $sm, Config $config)
    {
        // Call standard record controller initialization:
        parent::__construct($sm);

        $this->config = $config;
        $this->offlineImage = $config->Site->offlinePageImage ?? '';
    }

    /**
     * Get service worker
     *
     * @return Response
     */
    public function getAction()
    {
        $response = $this->getResponse();
        $headers = $response->getHeaders();
        $headers->addHeaderLine('Content-type', 'application/javascript');
        $headers->addHeaderLine('Service-Worker-Allowed', '/');
        $workerVersion = $this->config->Site->serviceWorkerVersion ?? 0;
        if ($workerVersion) {
            $scriptHelper = $this->serviceLocator->get('ViewRenderer')->plugin('scriptSrc');
            $imageHelper = $this->serviceLocator->get('ViewRenderer')->plugin('imageSrc');
            $serviceWorker = file_get_contents($scriptHelper('finna-service-worker.js'));
            $serviceWorker = str_replace(
                '%%fallback_url%%',
                $this->getServerUrl('serviceworker-offlinepage'),
                $serviceWorker
            );
            $serviceWorker = str_replace(
                '%%offline_image_url%%',
                $imageHelper->getSourceAddress($this->offlineImage),
                $serviceWorker
            );
            $serviceWorker = str_replace(
                '%%service_worker_version%%',
                (string)$workerVersion,
                $serviceWorker
            );
        } else {
            $serviceWorker = <<<JS
                self.addEventListener('install', () => {
                    self.skipWaiting();
                });
                JS;
        }

        $response->setContent($serviceWorker);
        return $response;
    }

    /**
     * Get offline page
     *
     * @return Response
     */
    public function getOfflinePageAction()
    {
        $view = $this->createViewModel(['image' => $this->offlineImage]);
        $view->setTemplate('serviceworker/offlinepage');
        return $view;
    }
}
