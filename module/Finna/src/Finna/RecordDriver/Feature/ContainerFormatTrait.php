<?php

/**
 * Common functionality for container record formats.
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

namespace Finna\RecordDriver\Feature;

use Finna\Record\Loader;
use Finna\RecordDriver\PluginManager;
use VuFind\RecordDriver\AbstractBase;
use VuFindSearch\Response\RecordInterface;

use function count;
use function is_callable;

/**
 * Common functionality for container record formats.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
trait ContainerFormatTrait
{
    /**
     * Cache for encapsulated records.
     *
     * @var array
     */
    protected array $encapsulatedRecordCache;

    /**
     * Record driver plugin manager.
     *
     * @var PluginManager
     */
    protected PluginManager $recordDriverManager;

    /**
     * Record loader.
     *
     * @var Loader
     */
    protected Loader $recordLoader;

    /**
     * Attach record driver plugin manager.
     *
     * @param PluginManager $recordDriverManager Record driver plugin manager
     *
     * @return void
     */
    public function attachRecordDriverManager(
        PluginManager $recordDriverManager
    ): void {
        $this->recordDriverManager = $recordDriverManager;
    }

    /**
     * Attach record loader.
     *
     * @param Loader $recordLoader Record loader
     *
     * @return void
     */
    public function attachRecordLoader(Loader $recordLoader): void
    {
        $this->recordLoader = $recordLoader;
    }

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
        if (null !== $limit) {
            $limit += $offset;
        }
        $cache = $this->getEncapsulatedRecordCache();
        $results = [];
        for ($p = $offset; null === $limit || $p < $limit; $p++) {
            if (!isset($cache[$p])) {
                // Reached end of records
                break;
            }
            $results[] = $this->getCachedEncapsulatedRecordDriver($p);
        }
        return $results;
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
        $cache = $this->getEncapsulatedRecordCache();
        foreach ($cache as $position => $record) {
            if ($id === $record['id']) {
                return $this->getCachedEncapsulatedRecordDriver($position);
            }
        }
        return null;
    }

    /**
     * Returns the total number of encapsulated records.
     *
     * @return int
     */
    public function getEncapsulatedRecordTotal(): int
    {
        return count($this->getEncapsulatedRecordCache());
    }

    /**
     * Returns the tag name of XML elements containing an encapsulated record.
     *
     * @return string
     */
    protected function getEncapsulatedRecordElementTagName(): string
    {
        return 'item';
    }

    /**
     * Return all encapsulated record items.
     *
     * @return array
     */
    protected function getEncapsulatedRecordItems(): array
    {
        // Implementation for XML items
        $items = [];
        $xml = $this->getXmlRecord();
        $tagName = $this->getEncapsulatedRecordElementTagName();
        foreach ($xml->{$tagName} as $item) {
            $items[] = $item;
        }
        return $items;
    }

    /**
     * Return ID for an encapsulated record.
     *
     * @param mixed $item Encapsulated record item.
     *
     * @return string
     */
    protected function getEncapsulatedRecordId($item): string
    {
        // Implementation for XML items with ID specified in an 'id' element
        if ($item instanceof \SimpleXMLElement) {
            return (string)$item->id;
        }
        if ($item instanceof \DOMNode) {
            return $item->getElementsByTagName('id')[0]->nodeValue;
        }
        throw new \RuntimeException('Unable to determine ID');
    }

    /**
     * Return format for an encapsulated record.
     *
     * @param mixed $item Encapsulated record item
     *
     * @return string
     * @throws \RuntimeException If the format can not be determined
     */
    protected function getEncapsulatedRecordFormat($item): string
    {
        // Implementation for XML items with format specified in a 'format' attribute
        $format = $item->attributes()->{'format'} ?? null;
        if (isset($format)) {
            return ucfirst(strtolower((string)$format));
        }
        throw new \RuntimeException('Unable to determine format');
    }

    /**
     * Return position for an encapsulated record, or null for unspecified position
     *
     * @param mixed $item Encapsulated record item
     *
     * @return int|null
     */
    protected function getEncapsulatedRecordPosition($item): ?int
    {
        // Implementation for XML items with position optionally specified in a
        // 'position' attribute
        $position = $item->attributes()->{'position'} ?? null;
        if (isset($position)) {
            return (int)$item->position;
        }
        return null;
    }

    /**
     * Return record driver for an encapsulated record.
     *
     * @param mixed $item Encapsulated record item
     *
     * @return ?AbstractBase
     * @throws \RuntimeException If the format is not supported
     */
    protected function getEncapsulatedRecordDriver($item): ?AbstractBase
    {
        $format = $this->getEncapsulatedRecordFormat($item);
        $method = "get{$format}Driver";
        if (!is_callable([$this, $method])) {
            throw new \RuntimeException('No driver for format ' . $format);
        }
        return $this->$method($item);
    }

    /**
     * Get cache containing all encapsulated records.
     *
     * The cache is an array of arrays with the following keys:
     * - id: Record ID
     * - item: Record item
     *
     * and if the driver has been loaded using
     * ContainerFormatTrait::getCachedEncapsulatedRecordDriver():
     * - driver: VuFind record driver
     *
     * @return array
     */
    protected function getEncapsulatedRecordCache(): array
    {
        if (isset($this->encapsulatedRecordCache)) {
            return $this->encapsulatedRecordCache;
        }

        $records = [];
        foreach ($this->getEncapsulatedRecordItems() as $item) {
            $record = [
                'id' => $this->getEncapsulatedRecordId($item),
                'item' => $item,
            ];
            // Position is optional
            if ($position = $this->getEncapsulatedRecordPosition($item)) {
                $records[$position] = $record;
            } else {
                $records[] = $record;
            }
        }
        // Sort by key in ascending order
        ksort($records);
        // Ensure that keys start from 0 and are sequential
        $records = array_values($records);

        $this->encapsulatedRecordCache = $records;
        return $records;
    }

    /**
     * Return record driver for an encapsulated record in the provided position or
     * null if the position is not valid.
     *
     * @param int $position Record position
     *
     * @return ?AbstractBase
     * @throws \RuntimeException If the format is not supported
     */
    protected function getCachedEncapsulatedRecordDriver(
        int $position
    ): ?EncapsulatedRecordInterface {
        // Ensure cache is warm
        $cache = $this->getEncapsulatedRecordCache();
        // Ensure position is valid
        if (!isset($cache[$position])) {
            return null;
        }
        // Try to get driver from cache
        if (!$driver = $cache[$position]['driver'] ?? null) {
            // Not in cache so get driver and add it to cache
            $driver
                = $this->encapsulatedRecordCache[$position]['driver']
                    = $this->getEncapsulatedRecordDriver($cache[$position]['item']);
        }
        return $driver;
    }

    /**
     * Filter encapsulated records of this format for public APIs.
     *
     * @param \SimpleXMLElement $record Container record XML.
     *
     * @return \SimpleXMLElement Filtered container record XML.
     */
    protected function filterEncapsulatedRecords(\SimpleXMLElement $record): \SimpleXMLElement
    {
        $container = dom_import_simplexml($record);
        $tagName = $this->getEncapsulatedRecordElementTagName();
        foreach ($container->getElementsByTagName($tagName) as $item) {
            $encapsulated = $this->getEncapsulatedRecord(
                $this->getEncapsulatedRecordId($item)
            );
            if (is_callable([$encapsulated, 'getFilteredXMLElement'])) {
                $filtered = dom_import_simplexml($encapsulated->getFilteredXMLElement());
                $container->replaceChild(
                    $container->ownerDocument->importNode($filtered, true),
                    $item
                );
            }
        }
        return simplexml_import_dom($container);
    }

    /**
     * Return full record as a filtered SimpleXMLElement for public APIs.
     *
     * @return \SimpleXMLElement
     */
    public function getFilteredXMLElement(): \SimpleXMLElement
    {
        $record = clone $this->getXmlRecord();
        return $this->filterEncapsulatedRecords($record);
    }

    /**
     * Return full record as filtered XML for public APIs.
     *
     * @return string
     */
    public function getFilteredXML()
    {
        return $this->getFilteredXMLElement()->asXML();
    }
}
