<?php

/**
 * Model for curated VuFind records.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2022-2023.
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
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */

namespace Finna\RecordDriver;

use Finna\RecordDriver\Feature\ContainerFormatInterface;
use Finna\RecordDriver\Feature\ContainerFormatTrait;
use Finna\RecordDriver\Feature\EncapsulatedRecordInterface;
use Finna\RecordDriver\Feature\EncapsulatedRecordTrait;
use Finna\RecordDriver\Feature\FinnaXmlReaderTrait;
use VuFindSearch\Response\RecordInterface;

use function count;

/**
 * Model for curated VuFind records.
 *
 * This driver is designed to be used as a virtual record driver by container format
 * drivers encapsulating curated VuFind records.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
class CuratedRecord extends SolrDefault implements
    ContainerFormatInterface,
    EncapsulatedRecordInterface
{
    use ContainerFormatTrait;
    use EncapsulatedRecordTrait;
    use FinnaXmlReaderTrait;

    /**
     * Get records encapsulated in this container record.
     *
     * @param int  $offset Offset for results
     * @param ?int $limit  Limit for results (null for none)
     *
     * @return RecordInterface[]
     * @throws \RuntimeException If the format of an encapsulated record is not
     * supported
     */
    public function getEncapsulatedRecords(
        int $offset = 0,
        ?int $limit = null
    ): array {
        return isset($this->fields['record']) ? [$this->fields['record']] : [];
    }

    /**
     * Returns the requested encapsulated record or null if not found.
     *
     * @param string $id Encapsulated record ID
     *
     * @return ?RecordInterface
     * @throws \RuntimeException If the format is not supported
     */
    public function getEncapsulatedRecord(string $id): ?RecordInterface
    {
        if ($id !== $this->getUniqueID() || !isset($this->fields['record'])) {
            return null;
        }
        return $this->fields['record'];
    }

    /**
     * Returns the total number of encapsulated records.
     *
     * @return int
     */
    public function getEncapsulatedRecordTotal(): int
    {
        return count($this->getEncapsulatedRecords());
    }

    /**
     * Get the full title of the record.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->fields['title'] ?? '';
    }

    /**
     * Get the position of the record.
     *
     * @return int
     */
    public function getPosition(): int
    {
        return $this->fields['position'] ?? 0;
    }

    /**
     * Get record notes.
     *
     * @return string
     */
    public function getNotes(): string
    {
        return $this->fields['notes'] ?? '';
    }

    /**
     * Return full record as a filtered SimpleXMLElement for public APIs.
     *
     * @return \SimpleXMLElement
     */
    public function getFilteredXMLElement(): \SimpleXMLElement
    {
        $record = clone $this->getXmlRecord();
        $filterFields = ['comment'];
        foreach ($filterFields as $filterField) {
            while ($record->{$filterField}) {
                unset($record->{$filterField}[0]);
            }
        }
        // Only the URL of the single encapsulated record is in the XML record, so
        // there is no need to call filterEncapsulatedRecords().
        return $record;
    }
}
