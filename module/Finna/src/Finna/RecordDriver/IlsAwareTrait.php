<?php
/**
 * ILS support for MARC and other types of records.
 *
 * PHP version 7
 *
 * Copyright (C) The National Library 2015-2019.
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
 * @package  RecordDrivers
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
namespace Finna\RecordDriver;

/**
 * ILS support for MARC and other types of records.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
trait IlsAwareTrait
{
    /**
     * Get a link for placing a title level hold.
     *
     * @return mixed A url if a hold is possible, boolean false if not
     */
    public function getRealTimeTitleHold()
    {
        if ($this->hasILS()) {
            $biblioLevel = strtolower($this->tryMethod('getBibliographicLevel'));
            if ("monograph" == $biblioLevel || strstr($biblioLevel, "part")) {
                if ($this->ils->getTitleHoldsMode() != "disabled") {
                    return $this->titleHoldLogic->getHold($this->getUniqueID());
                }
            }
            if ($bibLevels = $this->ils->getConfig('getTitleHoldBibLevels')) {
                if (in_array($biblioLevel, $bibLevels)) {
                    if ($this->ils->getTitleHoldsMode() != "disabled") {
                        return $this->titleHoldLogic->getHold($this->getUniqueID());
                    }
                }
            }
        }
        return false;
    }
}
