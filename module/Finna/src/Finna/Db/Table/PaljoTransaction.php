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
     * @param string  $userId      User id
     * @param string  $paljoId     Paljo ID
     * @param string  $recordId    Record id
     * @param string  $token Paljo transaction token
     * @param string  $userMessage Users message about the transaction
     * @param string  $amount      Amount paid
     * @param string  $currency    Currency used
     * @param Date    $expires     The date the subscription expires
     * @param boolean $registered  Whether the transaction is registered to paljo
     * @param string  $volumeCode  Volume code used in transaction
     *
     * @return PaljoTransaction paljo transaction
     */
    public function saveTransaction(
        $userId, $paljoId, $recordId, $imageId, $token, $userMessage, $imageSize, $amount, $currency, $priceType, $expires, $registered, $volumeCode
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
        $pt->registered = $registered;
        $pt->volume_code = $volumeCode;
        $pt->save();
        return $pt;
    }

    /**
     * Get users PALJO transactions
     *
     * @param string $paljoId users paljo id
     * @param int $offset 
     * @param int $limit 
     * @param boolean $active whether to return active or expired transactinos
     */
    public function getTransactions($paljoId, $offset = 0, $limit = 50, $active = true)
    {
        $sql = $this->getSql();
        $select = $sql->select();
        $select->where->equalTo('paljo_id', $paljoId);
        if ($active) {
            $select->where->greaterThan('expires', date('Y-m-d H:i:s'));
        } else {
            $select->where->lessThan('expires', date('Y-m-d H:i:s'));
        }
        $select->offset($offset);
        $select->limit($limit);
        $select->order('created');

        $adapter = new \Laminas\Paginator\Adapter\DbSelect($select, $sql);
        $paginator = new \Laminas\Paginator\Paginator($adapter);
        $paginator->setItemCountPerPage($limit);
        if (null !== $offset) {
            $paginator->setCurrentPageNumber($offset);
        }
        return $paginator;
    }

    public function getTotalTransactions($paljoId, $active = true) {
       $callback = function ($select) use ($paljoId, $active) {
            $select->where->equalTo('paljo_id', $paljoId);
            if ($active) {
                $select->where->greaterThan('expires', date('Y-m-d'));
            } else {
                $select->where->lessThan('expires', date('Y-m-d'));
            }
       };
       return count($this->select($callback));
    }

    public function getTotalExpiredTransactions($paljoId) {
        $callback = function ($select) use ($paljoId) {
          $select->where->equalTo('paljo_id', $paljoId);
          $select->where->lessThan('expires', date('Y-m-d'));
        };
        return count($this->select($callback));
   }

    /**
     * Get transaction by transaction ID
     */
    public function getById($transactionId) {
        $row = $this->select(['id' => $transactionId])->current();
        return (empty($row)) ? false : $row;
    }

    /**
     * Mark transaction as registered to PALJO
     */
    public function registerTransaction($transactionId) {
        $this->update(
            ['registered' => true],
            ['id' => $transactionId]
        );
    }

}
