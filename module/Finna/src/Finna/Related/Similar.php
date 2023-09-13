<?php

/**
 * Related Records: Solr-based similarity
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2023.
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
 * @package  Related_Records
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:related_records_modules Wiki
 */

namespace Finna\Related;

use VuFindSearch\Command\SimilarCommand;

/**
 * Related Records: Solr-based similarity
 *
 * @category VuFind
 * @package  Related_Records
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:related_records_modules Wiki
 */
class Similar extends \VuFind\Related\Similar
{
    /**
     * Return similar records for similar carousel
     *
     * @param int                               $amount Amount of similar records
     * @param \VuFind\RecordDriver\AbstractBase $driver Record driver object
     *
     * @return void
     */
    public function initCarousel($amount, $driver)
    {
        $command = new SimilarCommand(
            $driver->getSourceIdentifier(),
            $driver->getUniqueId(),
            new \VuFindSearch\ParamBag(['rows' => $amount])
        );
        $this->results = $this->searchService->invoke($command)->getResult();
    }
}
