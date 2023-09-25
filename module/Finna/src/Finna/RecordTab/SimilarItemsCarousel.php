<?php

/**
 * Similar items carousel tab.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland, 2023
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
 * @package  RecordTabs
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_tabs Wiki
 */

namespace Finna\RecordTab;

use VuFindSearch\Command\SimilarCommand;

/**
 * Similar items carousel tab.
 *
 * @category VuFind
 * @package  RecordTabs
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_tabs Wiki
 */
class SimilarItemsCarousel extends \VuFind\RecordTab\SimilarItemsCarousel
{
    /**
     * Get an array of Record Driver objects representing items similar to the one
     * passed to the constructor.
     *
     * @param int $amount Amount of similar records
     *
     * @return RecordCollectionInterface
     */
    public function getResults(int $amount = 15)
    {
        $record = $this->getRecordDriver();
        $params = new \VuFindSearch\ParamBag(['rows' => $amount]);
        $command = new SimilarCommand(
            $record->getSourceIdentifier(),
            $record->getUniqueId(),
            $params
        );
        return $this->searchService->invoke($command)->getResult();
    }
}
