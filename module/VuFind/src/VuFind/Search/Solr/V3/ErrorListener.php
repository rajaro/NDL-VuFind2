<?php

/**
 * SOLR 3.x error listener.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2013.
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace VuFind\Search\Solr\V3;

use VuFindSearch\Backend\Exception\HttpErrorException;

use Zend\EventManager\EventInterface;

/**
 * SOLR 3.x error listener.
 *
 * @category VuFind2
 * @package  Search
 * @author   David Maus <maus@hab.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class ErrorListener
{

    /**
     * Backends ot listen on.
     *
     * @var array
     */
    protected $backends;

    /**
     * Constructor.
     *
     * @param array $backends Name of backends to listen on
     *
     * @return void
     */
    public function __construct(array $backends)
    {
        $this->backends = $backends;
    }

    /**
     * VuFindSearch.error
     *
     * @param EventInterface $event Event
     *
     * @return EventInterface
     */
    public function onSearchError(EventInterface $event)
    {
        $backend = $event->getParam('backend');
        if (in_array($backend, $this->backends)) {
            $error  = $event->getTarget();
            if ($error instanceOf HttpErrorException) {
                $reason = $error->getResponse()->getReasonPhrase();
                if (stristr($error, 'org.apache.lucene.queryParser.ParseException')
                    || stristr($error, 'undefined field')
                ) {
                    $error->addTag('VuFind\Search\ParserError');
                }
            }
        }
        return $event;
    }
}