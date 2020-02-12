<?php
/**
 * GetFeed AJAX handler
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2015-2018.
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
 * @package  AJAX
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace Finna\AjaxHandler;

use Zend\Config\Config;
use Zend\Mvc\Controller\Plugin\Params;
use Zend\Mvc\Controller\Plugin\Url;

/**
 * GetLinkedEvents AJAX handler
 *
 * @category VuFind
 * @package  AJAX
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class GetLinkedEvents extends \VuFind\AjaxHandler\AbstractBase
{

    /**
     * Main config
     *
     * @var Config $config
     */
    protected $config;

    /**
     * Linked Events
     *
     * @var LinkedEvents $linkedEvents
     */
    protected $linkedEvents;

    /**
     * View renderer
     *
     * @var ViewRenderer $viewRenderer
     */
    protected $viewRenderer;

    /**
     * Constructor
     *
     * @param Config       $config       Configuration
     * @param LinkedEvents $linkedEvents linkedEvents service
     * @param ViewRenderer $viewRenderer view renderer
     */
    public function __construct(Config $config, $linkedEvents, $viewRenderer)
    {
        $this->config = $config;
        $this->linkedEvents = $linkedEvents;
        $this->viewRenderer = $viewRenderer;
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     */
    public function handleRequest(Params $params)
    {
        $param = $params->fromQuery('params');
        try {
            $events = $this->linkedEvents->getEvents($param);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
        $html = $this->viewRenderer->partial(
            'ajax/linked-events.phtml', ['events' => $events]
        );
        return $this->formatResponse($html);
        ;
    }
}