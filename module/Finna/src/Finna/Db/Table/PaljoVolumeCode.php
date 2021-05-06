<?php
/**
 * Table Definition for paljo volume codes
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2021.
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
 * @package  Db_Table
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace Finna\Db\Table;

use Laminas\Db\Adapter\Adapter;
use VuFind\Db\Row\RowGateway;
use VuFind\Db\Table\PluginManager;

/**
 * Table Definition for paljo volume codes
 *
 * @category VuFind
 * @package  Db_Table
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class PaljoVolumeCode extends \VuFind\Db\Table\Gateway
{
    /**
     * Constructor
     *
     * @param Adapter       $adapter Database adapter
     * @param PluginManager $tm      Table manager
     * @param array         $cfg     Laminas configuration
     * @param RowGateway    $rowObj  Row prototype object (null for default)
     * @param string        $table   Name of database table to interface with
     */
    public function __construct(Adapter $adapter, PluginManager $tm, $cfg,
        RowGateway $rowObj = null, $table = 'finna_paljo_volume_code'
    ) {
        parent::__construct($adapter, $tm, $cfg, $rowObj, $table);
    }

    /**
     * Save volume code to database
     *
     * @param string $paljoId User Valjo ID
     * @param string $volumeCode   Volume code
     * @param string $organisation Organisation associated with the code
     * @param string $discount     The discount percentage
     *
     * @return PaljoVolumeCodeRow
     */
    public function saveVolumeCode($paljoId, $code, $organisation, $discount)
    {
        $volumeCode = $this->createRow();
        $volumeCode->paljo_id = $paljoId;
        $volumeCode->volume_code = $code;
        $volumeCode->organisation_id = $organisation;
        $volumeCode->discount = $discount;
        $volumeCode->save();

        return $volumeCode;
    }

    /**
     * Delete a volume code
     *
     * @param string $codeId volume code ID
     * @param string $userId user PALJO ID
     */
    public function deleteVolumeCode($codeId, $paljoId)
    {
        $matches = $this->select(['id' => $codeId, 'paljo_id' => $paljoId]);
        if (count($matches) == 0 || !($row = $matches->current())) {
            return false;
        }
        $row->delete();
    }

    /**
     * Get volume codes associated with a user
     *
     * @param string $paljoId User paljo id
     *
     * @return \Laminas\Db\ResultSet\AbstractResultSet
     */
    public function getVolumeCodesForUser($paljoId)
    {
        return $this->select(['paljo_id' => $paljoId]);
    }
}
