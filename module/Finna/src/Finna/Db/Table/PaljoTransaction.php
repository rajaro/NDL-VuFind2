<?php
/**
 * Table Definition for paljo transactions
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
 * Table Definition for paljo transactions
 *
 * @category VuFind
 * @package  Db_Table
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class PaljoTransaction extends \VuFind\Db\Table\Gateway
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
        RowGateway $rowObj = null, $table = 'finna_paljo_transaction'
    ) {
        parent::__construct($adapter, $tm, $cfg, $rowObj, $table);
    }

    /**
     * Save PALJO transaction to database
     *
     * @param string $userId      User id
     * @param string $paljoId     Paljo ID
     * @param string $recordId    Record id
     * @param string $token Paljo transaction token
     * @param string $userMessage Users message about the transaction
     * @param string $amount      Amount paid
     * @param string $currency    Currency used
     * @param Date   $expires     The date the subscription expires
     */
    public function saveTransaction(
        $userId, $paljoId, $recordId, $imageId, $token, $userMessage, $imageSize, $amount, $currency, $priceType, $expires
    ) {
        $pt = $this->createRow();
        $pt->user_id = $userId;
        $pt->paljo_id = $paljoId;
        $pt->record_id = $recordId;
        $pt->image_id = $imageId;
        $pt->token = $token;
        $pt->user_message = $userMessage;
        $pt->image_size = $imageSize;
        $pt->amount = $amount;
        $pt->currency = $currency;
        $pt->price_type = $priceType;
        $pt->created = date("Y-m-d H:i:s");
        $pt->expires = $expires;
        $pt->save();
        return $pt;
    }

    /**
     * Get transactions for user.
     *
     * @param string $userId User ID.
     *
     * @return PaljoTransaction paljo transaction or false on error
     */
    public function getTransactions($paljoId)
    {
        $activeCallback = function ($select) use ($paljoId) {
            $select->where->equalTo('paljo_id', $paljoId);
            $select->where->greaterThan('expires', date('Y-m-d H:i:s'));
        };
        $expiredCallback = function($select) use ($paljoId) {
            $select->where->equalTo('paljo_id', $paljoId);
            $select->where->lessThan('expires', date('Y-m-d H:i:s'));
        };
        $transactions = [];
        foreach ($this->select($activeCallback) as $result) {
            $transactions['active'][] = $result;
        }
        foreach ($this->select($expiredCallback) as $result) {
          $transactions['expired'][] = $result;
        }
        return $transactions;
    }


}
