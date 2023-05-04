<?php

/**
 * Model for EAD3 records in Solr.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
 * Copyright (C) The National Library of Finland 2012-2020.
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
 * @category VuFind
 * @package  RecordDrivers
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */

namespace Finna\RecordDriver;

/**
 * Model for EAD3 records in Solr.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Eoghan O'Carragain <Eoghan.OCarragan@gmail.com>
 * @author   Luke O'Sullivan <l.osullivan@swansea.ac.uk>
 * @author   Lutz Biedinger <lutz.Biedinger@gmail.com>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
class SolrEad3 extends SolrEad
{
    // Image types
    public const IMAGE_MEDIUM = 'medium';
    public const IMAGE_LARGE = 'large';
    public const IMAGE_FULLRES = 'fullres';
    public const IMAGE_OCR = 'ocr';

    // Image type map
    public const IMAGE_MAP = [
        'Bittikartta - Fullres - Jakelukappale' => self::IMAGE_FULLRES,
        'Bittikartta - Pikkukuva - Jakelukappale' => self::IMAGE_MEDIUM,
        'OCR-data - Alto - Jakelukappale' => self::IMAGE_OCR,
        'fullsize' => self::IMAGE_FULLRES,
        'thumbnail' => self::IMAGE_MEDIUM,
    ];

    // URLs that are displayed on ExternalData record tab
    // (not below record title)
    public const EXTERNAL_DATA_URLS = [
        'Bittikartta - Fullres - Jakelukappale',
        'Bittikartta - Pikkukuva - Jakelukappale',
        'OCR-data - Alto - Jakelukappale',
    ];

    // Altformavail labels
    public const ALTFORM_LOCATION = 'location';
    public const ALTFORM_LOCATION_TYPE = 'locationType';
    public const ALTFORM_LOCATION_OFFICE = 'locationOffice';
    public const ALTFORM_PHYSICAL_LOCATION = 'physicalLocation';
    public const ALTFORM_TYPE = 'type';
    public const ALTFORM_DIGITAL_TYPE = 'digitalType';
    public const ALTFORM_FORMAT = 'format';
    public const ALTFORM_ACCESS = 'access';
    public const ALTFORM_ONLINE = 'online';
    public const ALTFORM_ORIGINAL = "original";
    public const ALTFORM_CONDITION = 'condition';
    public const ALTFORM_IMAGE_SIZE = 'imageSize';
    public const ALTFORM_IMAGE_AREA = 'imageArea';
    public const ALTFORM_IMAGE_TYPE = 'imageType';
    public const ALTFORM_MICROFILM_COPY_TYPE = 'microfilmCopyType';
    public const ALTFORM_MICROFILM_SERIES = 'microfilmSeries';
    public const ALTFORM_MAP_SCALE = "mapScale";

    // Altformavail label map
    public const ALTFORM_MAP = [
        'Tietopalvelun tarjoamispaikka' => self::ALTFORM_LOCATION,
        'Tekninen tyyppi' => self::ALTFORM_TYPE,
        'Digitaalisen ilmentymän tyyppi' => self::ALTFORM_DIGITAL_TYPE,
        'Tallennusalusta' => self::ALTFORM_FORMAT,
        'Digitaalisen aineiston tiedostomuoto' => self::ALTFORM_FORMAT,
        'Ilmentymän kuntoon perustuva käyttörajoitus'
            => self::ALTFORM_ACCESS,
        'Manifestation\'s access restrictions' => self::ALTFORM_ACCESS,
        'Bruk av manifestationen har begränsats pga' => self::ALTFORM_ACCESS,
        'Internet - ei fyysistä toimipaikkaa' => self::ALTFORM_ONLINE,
        'Lisätietoa kunnosta' => self::ALTFORM_CONDITION,
        'Säilytysyksikön tekninen tunniste' => self::ALTFORM_LOCATION_TYPE,
        'Säilytysyksikön tunniste' => self::ALTFORM_PHYSICAL_LOCATION,
        'Säilyttävä toimipiste' => self::ALTFORM_LOCATION_OFFICE,
        'Ilmentymän kuva-alan koko' => self::ALTFORM_IMAGE_AREA,
        'Valokuvan kuvakoko' => self::ALTFORM_IMAGE_SIZE,
        'Valokuvan kuvatyyppi' => self::ALTFORM_IMAGE_TYPE,
        'Mikrofilmin kopiotyyppi' => self::ALTFORM_MICROFILM_COPY_TYPE,
        'Mikrofilmin jakso' => self::ALTFORM_MICROFILM_SERIES,
        'Kartan mittakaava' => self::ALTFORM_MAP_SCALE,
        'Alkuperäisyys' => self::ALTFORM_ORIGINAL,
    ];

    // Accessrestrict types and their order in the UI
    public const ACCESS_RESTRICT_TYPES = [
        'ahaa:AI24','general', 'ahaa:KR1', 'ahaa:KR2', 'ahaa:KR3',
        'ahaa:KR5', 'ahaa:KR7', 'ahaa:KR9', 'ahaa:KR4',
    ];

    // Accessrestrict material condition
    public const ACCESS_RESTRICT_MATERIAL_CONDITION = 'ahaa:IL14';

    // relation@encodinganalog-attribute of relations used by getRelatedRecords
    public const RELATION_RECORD = 'ahaa:AI30';

    // relation@encodinganalog-attributes that are discarded from record links
    public const IGNORED_RELATIONS = [self::RELATION_RECORD, 'ahaa:AI41'];

    // Relation types
    public const RELATION_CONTINUED_FROM = 'continued-from';
    public const RELATION_PART_OF = 'part-of';
    public const RELATION_CONTAINS = 'contains';
    public const RELATION_SEE_ALSO = 'see-also';
    public const RELATION_SEPARATED = 'separated';

    // Relation type map
    public const RELATION_MAP = [
        'On jatkoa' => self::RELATION_CONTINUED_FROM,
        'Sisältyy' => self::RELATION_PART_OF,
        'Sisältää' => self::RELATION_CONTAINS,
        'Katso myös' => self::RELATION_SEE_ALSO,
        'Erotettu aineisto' => self::RELATION_SEPARATED,
    ];

    // Relator attribute for archive origination
    public const RELATOR_ARCHIVE_ORIGINATION = 'Arkistonmuodostaja';

    public const RELATOR_TIME_INTERVAL = 'suhteen ajallinen kattavuus';
    public const RELATOR_UNKNOWN_TIME_INTERVAL = 'unknown - open';

    // unitid is shown when label-attribute is missing or is one of:
    public const UNIT_IDS = [
        'Tekninen', 'Analoginen', 'Vanha analoginen', 'Vanha tekninen',
        'Diaarinumero', 'Asiaryhmän numero',
    ];

    // If any of these values are found in ocr image title, do not place
    // the image into ocr key.
    public const EXCLUDE_OCR_TITLE_PARTS = [
        'Kuva/Aukeama',
    ];

    /**
     * Get the institutions holding the record.
     *
     * @return array
     */
    public function getInstitutions()
    {
        $result = parent::getInstitutions();

        if (! $this->preferredLanguage) {
            return $result;
        }
        if ($name = $this->getRepositoryName()) {
            return [$name];
        }

        return $result;
    }

    /**
     * Return buildings from index.
     *
     * @return array
     */
    public function getBuildings()
    {
        if ($this->preferredLanguage && $name = $this->getRepositoryName()) {
            return [$name];
        }

        return parent::getBuildings();
    }

    /**
     * Return an array of associative URL arrays with one or more of the following
     * keys:
     *
     * <li>
     *   <ul>desc: URL description text to display (optional)</ul>
     *   <ul>url: fully-formed URL (required if 'route' is absent)</ul>
     *   <ul>route: VuFind route to build URL with (required if 'url' is absent)</ul>
     *   <ul>routeParams: Parameters for route (optional)</ul>
     *   <ul>queryString: Query params to append after building route (optional)</ul>
     * </li>
     *
     * @return array
     */
    public function getURLs()
    {
        $urls = $localeUrls = [];
        $record = $this->getXmlRecord();
        if (!isset($record->did)) {
            return [];
        }
        $preferredLangCodes = $this->mapLanguageCode($this->preferredLanguage);
        foreach ($record->did->xpath('//daoset') ?? [] as $daoset) {
            $localtype = (string)$daoset->attributes()->localtype;

            if ($localtype && in_array($localtype, self::EXTERNAL_DATA_URLS)) {
                continue;
            }
            foreach ($daoset->dao as $node) {
                $attr = $node->attributes();
                if ((string)$attr->linkrole === 'image/jpeg' || !$attr->href) {
                    continue;
                }
                $lang = (string)$attr->lang;
                $preferredLang = $lang && in_array($lang, $preferredLangCodes);

                $url = (string)$attr->href;
                $desc = $attr->linktitle ?? $node->descriptivenote->p ?? $url;

                if (!$this->urlBlocked($url, $desc)) {
                    $urlData = [
                        'url' => $url,
                        'desc' => (string)$desc,
                    ];
                    $urls[] = $urlData;
                    if ($preferredLang) {
                        $localeUrls[] = $urlData;
                    }
                }
            }
        }
        if ($localeUrls) {
            $urls = $localeUrls;
        }
        return $this->resolveUrlTypes($urls);
    }

    /**
     * Get origination
     *
     * @return string
     */
    public function getOrigination(): string
    {
        $originations = $this->getOriginations();
        return $originations[0] ?? '';
    }

    /**
     * Get all originations
     *
     * @return array
     */
    public function getOriginations(): array
    {
        return array_map(
            function ($origination) {
                return $origination['name'];
            },
            $this->getOriginationExtended()
        );
    }

    /**
     * Get extended origination info
     *
     * @return array
     */
    public function getOriginationExtended(): array
    {
        $record = $this->getXmlRecord();

        $localeResults = $results = [];

        // For filtering out duplicate names
        $searchNamesFn = function ($origination, $names) {
            foreach ($names as $name) {
                $detail1 = $origination['detail'] ?? null;
                $detail2 = $name['detail'] ?? null;
                if (
                    $origination['name'] === $name['name']
                    && ((!$detail1 || !$detail2) || ($detail1 === $detail2))
                ) {
                    return true;
                }
            }
            return false;
        };

        foreach ($record->did->origination ?? [] as $origination) {
            $originationLocaleResults = $originationResults = [];
            foreach ($origination->name ?? [] as $name) {
                $attr = $name->attributes();
                $id = (string)$attr->identifier;
                $currentName = null;
                $names = $name->part ?? [];
                for ($i = 0; $i < count($names); $i++) {
                    $name = $names[$i];
                    $attr = $name->attributes();
                    $value = (string)$name;
                    $localType = (string)$attr->localtype;
                    $data = [
                        'id' => $id, 'name' => $value, 'detail' => $localType,
                    ];
                    if ($localType !== self::RELATOR_TIME_INTERVAL) {
                        if ($nextEl = $names[$i + 1] ?? null) {
                            $localType
                                = (string)$nextEl->attributes()->localtype;
                            if ($localType === self::RELATOR_TIME_INTERVAL) {
                                // Pick relation time interval from
                                // next part-element
                                $date = (string)$nextEl;
                                if (
                                    $date !== self::RELATOR_UNKNOWN_TIME_INTERVAL
                                ) {
                                    $data['date'] = $date;
                                }
                                $i++;
                            }
                        }
                    }
                    $lang = $this->detectNodeLanguage($name);
                    if (
                        $lang['preferred'] ?? false
                        && !$searchNamesFn($data, $originationLocaleResults)
                    ) {
                        $originationLocaleResults[] = $data;
                    }
                    if (!$searchNamesFn($data, $originationResults)) {
                        $originationResults[] = $data;
                    }
                }
            }
            $localeResults = array_merge(
                $localeResults,
                $originationLocaleResults ?: $originationResults
            );
            $results = array_merge($results, $originationResults);
        }

        // // Loop relations and filter out names already added from did->origination
        foreach ($record->relations->relation ?? [] as $relation) {
            $attr = $relation->attributes();
            foreach (['relationtype', 'href', 'arcrole'] as $key) {
                if (!isset($attr->{$key})) {
                    continue;
                }
            }
            if (
                (string)$attr->relationtype !== 'cpfrelation'
                || (string)$attr->arcrole !== self::RELATOR_ARCHIVE_ORIGINATION
            ) {
                continue;
            }
            $id = (string)$attr->href;
            if ($name = $this->getDisplayLabel($relation, 'relationentry', true)) {
                $name = $name[0];
                if (!$searchNamesFn(compact('name'), $localeResults)) {
                    $localeResults[] = compact('id', 'name');
                }
            }
            if ($name = $this->getDisplayLabel($relation, 'relationentry')) {
                $name = $name[0];
                if (!$searchNamesFn(compact('name'), $results)) {
                    $results[] = compact('id', 'name');
                }
            }
        }

        return $localeResults ?: $results;
    }

    /**
     * Get all authors apart from presenters
     *
     * @return array
     */
    public function getNonPresenterAuthors()
    {
        $result = [];
        $xml = $this->getXmlRecord();

        $names = [];
        if (isset($xml->controlaccess->persname)) {
            foreach ($xml->controlaccess->persname as $name) {
                $names[] = $name;
            }
        }
        if (isset($xml->controlaccess->corpname)) {
            foreach ($xml->controlaccess->corpname as $name) {
                $names[] = $name;
            }
        }

        // Attempt to find names in preferred language
        foreach ($names as $node) {
            $name = $this->getDisplayLabel($node, 'part', true);
            if (empty($name) || !$name[0]) {
                continue;
            }
            $result[] = ['name' => $name[0]];
        }
        if (!empty($result)) {
            return $result;
        }

        // Not found, search again without language filters
        foreach ($names as $node) {
            $name = $this->getDisplayLabel($node);
            if (empty($name) || !$name[0]) {
                continue;
            }
            $result[] = $name[0];
        }
        $result = array_map(
            function ($name) {
                return ['name' => $name];
            },
            array_unique($result)
        );

        return $result;
    }

    /**
     * Get relations.
     *
     * @return array
     */
    public function getRelations()
    {
        $result = [];
        $xml = $this->getXmlRecord();
        if (!isset($xml->relations->relation)) {
            return $result;
        }
        foreach ($xml->controlaccess->name as $node) {
            $attr = $node->attributes();
            $relator = (string)$attr->relator;
            if (self::RELATOR_ARCHIVE_ORIGINATION === $relator) {
                continue;
            }
            $role = $this->translateRole((string)$attr->localtype, $relator);
            $name = $this->getDisplayLabel($node);
            if (empty($name) || !$name[0]) {
                continue;
            }
            $result[] = [
               'id' => (string)$node->attributes()->identifier,
               'role' => $role,
               'name' => $name[0],
            ];
        }

        return $result;
    }

    /**
     * Get location info to be used in ExternalData-record page tab.
     *
     * @param string $id If defined, return only the item with the given id
     *
     * @return array
     */
    public function getAlternativeItems($id = null)
    {
        $xml = $this->getXmlRecord();
        if (!isset($xml->altformavail->altformavail)) {
            return [];
        }

        // Collect daoset > dao ids. This list is used to separate non-online
        // altformavail items.
        $onlineIds = [];
        if (isset($xml->did->daoset)) {
            foreach ($xml->did->daoset as $daoset) {
                if (isset($daoset->descriptivenote->p)) {
                    $onlineIds[] = (string)$daoset->descriptivenote->p;
                }
            }
        }

        $onlineTypes = array_keys(
            array_filter(
                self::ALTFORM_MAP,
                function ($label, $type) {
                    return $type === self::ALTFORM_ONLINE;
                },
                ARRAY_FILTER_USE_BOTH
            )
        );

        $results = [];
        $preferredLangCodes = $this->mapLanguageCode($this->preferredLanguage);

        foreach ($xml->altformavail->altformavail as $altform) {
            $itemId = (string)$altform->attributes()->id;
            if ($id && $id !== $itemId) {
                continue;
            }
            $result = ['id' => $itemId, 'online' => in_array($itemId, $onlineIds)];
            $accessRestrictions = [];
            $owner = null;
            foreach ($altform->list->defitem ?? [] as $defitem) {
                $type = self::ALTFORM_MAP[(string)$defitem->label] ?? null;
                if (!$type) {
                    continue;
                }
                $val = (string)$defitem->item;
                switch ($type) {
                    case self::ALTFORM_LOCATION:
                        $result['location'] = $val;
                        if (in_array($val, $onlineTypes)) {
                            $result['online'] = true;
                        } else {
                            $result['service'] = true;
                        }
                        break;
                    case self::ALTFORM_LOCATION_TYPE:
                        $result['locationType'] = $val;
                        break;
                    case self::ALTFORM_PHYSICAL_LOCATION:
                        $result['physicalLocation'] = $val;
                        break;
                    case self::ALTFORM_LOCATION_OFFICE:
                        $result['locationOffice'] = $val;
                        break;
                    case self::ALTFORM_IMAGE_SIZE:
                        $result['imageSize'] = $val;
                        break;
                    case self::ALTFORM_IMAGE_AREA:
                        $result['imageArea'] = $val;
                        break;
                    case self::ALTFORM_MAP_SCALE:
                        $result['mapScale'] = $val;
                        break;
                    case self::ALTFORM_MICROFILM_SERIES:
                        $result['microfilmSeries'] = $val;
                        break;
                    case self::ALTFORM_TYPE:
                        $result['type'] = $val;
                        break;
                    case self::ALTFORM_DIGITAL_TYPE:
                        $result['digitalType'] = $val;
                        break;
                    case self::ALTFORM_FORMAT:
                        $result['format'] = $val;
                        break;
                    case self::ALTFORM_ORIGINAL:
                        $result['original'] = $val;
                        break;
                    case self::ALTFORM_MICROFILM_COPY_TYPE:
                        $result['microfilmCopyType'] = $val;
                        break;
                    case self::ALTFORM_IMAGE_TYPE:
                        $result['imageType'] = $val;
                        break;
                    case self::ALTFORM_ACCESS:
                        $lang = (string)$defitem->item->attributes()->lang ?? 'fin';
                        $accessRestrictions[$lang] = $val;
                        break;
                    case self::ALTFORM_CONDITION:
                        if ($info = (string)$defitem->label) {
                            $info .= ': ';
                        }
                        $info .= $val;
                        $result['info'] = $info;
                        break;
                }
            }
            if ($accessRestrictions) {
                $result['accessRestriction'] = reset($accessRestrictions);
                foreach ($accessRestrictions as $lang => $restriction) {
                    if (in_array($lang, $preferredLangCodes)) {
                        $result['accessRestriction'] = $restriction;
                        break;
                    }
                }
            }
            if ($id) {
                return $result;
            }
            $results[] = $result;
        }
        return $results;
    }

    /**
     * Get unit ids
     *
     * @return array
     */
    public function getUnitIds()
    {
        $xml = $this->getXmlRecord();
        if (!isset($xml->did->unitid)) {
            return [];
        }

        $ids = [];
        $manyIds = count($xml->did->unitid) > 1;
        foreach ($xml->did->unitid as $id) {
            $label = $fallbackDisplayLabel = (string)$id->attributes()->label;
            if ($label && !in_array($label, self::UNIT_IDS)) {
                continue;
            }
            $displayLabel = '';
            if ($label) {
                $displayLabel = "Unit ID:$label";
            } elseif ($manyIds) {
                $displayLabel = 'Unit ID:unique';
            }
            $val = (string)$id;
            if (!$val) {
                $val = (string)$id->attributes()->identifier;
            }
            if (!$val) {
                continue;
            }

            $ids[] = [
                'data' => $val,
                'detail'
                    => $this->translate($displayLabel, [], $fallbackDisplayLabel),
            ];
        }

        return $ids;
    }

    /**
     * Get notes on bibliography content.
     *
     * @return string[] Notes
     */
    public function getBibliographyNotes()
    {
        return [];
    }

    /**
     * Get an array of summary strings for the record.
     *
     * @return array
     */
    public function getSummary(): array
    {
        return $this->doGetSummary(false);
    }

    /**
     * Get an array of summary items for the record.
     *
     * See doGetSummary() for summary item description.
     *
     * @return array
     */
    public function getSummaryExtended(): array
    {
        return $this->doGetSummary(true);
    }

    /**
     * Get identifier
     *
     * @return array
     */
    public function getIdentifier()
    {
        $xml = $this->getXmlRecord();
        if (isset($xml->did->unitid)) {
            foreach ($xml->did->unitid as $unitId) {
                if (isset($unitId->attributes()->identifier)) {
                    return [(string)$unitId->attributes()->identifier];
                }
            }
        }
        return [];
    }

    /**
     * Get item history
     *
     * @return string
     */
    public function getItemHistory()
    {
        $xml = $this->getXmlRecord();

        if (!empty($xml->scopecontent)) {
            foreach ($xml->scopecontent as $el) {
                if (
                    ! isset($el->attributes()->encodinganalog)
                    || (string)$el->attributes()->encodinganalog !== 'AI10'
                ) {
                    continue;
                }
                if ($desc = $this->getDisplayLabel($el, 'p')) {
                    return $desc[0];
                }
            }
        }
        return '';
    }

    /**
     * Get external data (images, physical items).
     *
     * @return array
     */
    public function getExternalData()
    {
        $locale = $this->getLocale();
        $images = $this->getImagesAsAssoc($locale);
        $physicalItems = $this->getPhysicalItems();

        $result = [];
        if (!empty($images['fullres']['items'])) {
            $result['items']['fullResImages'] = $images['fullres'];
        }
        if (!empty($images['ocr']['items'])) {
            $result['items']['OCRImages'] = $images['ocr'];
        }
        if (!empty($physicalItems)) {
            $result['items']['physicalItems'] = $physicalItems;
        }
        $result['digitized'] = !empty($images['displayImages'])
            || !empty($images['fullres']['items'])
            || !empty($images['ocr']['items']);
        return $result;
    }

    /**
     * Return an array of image URLs associated with this record with keys:
     * - urls        Image URLs
     *   - small     Small image (mandatory)
     *   - medium    Medium image (mandatory)
     *   - large     Large image (optional)
     * - description Description text
     * - rights      Rights
     *   - copyright   Copyright (e.g. 'CC BY 4.0') (optional)
     *   - description Human readable description (array)
     *   - link        Link to copyright info
     *
     * @param string $language   Language for copyright information
     * @param bool   $includePdf Whether to include first PDF file when no image
     * links are found
     *
     * @return array
     */
    public function getAllImages(
        $language = 'fi',
        $includePdf = false
    ) {
        $result = $this->getImagesAsAssoc($language, $includePdf);
        return $result['displayImages'] ?? [];
    }

    /**
     * Return an associated array with all the presentation types.
     * - displayImages Images to be displayed in the front end.
     * - ocr           Ocr images.
     * - fullres       Fullres images.
     *
     * @param string $language   Language for copyright information
     * @param bool   $includePdf Whether to include first PDF file when no image
     * links are found
     *
     * @return array
     */
    protected function getImagesAsAssoc(
        string $language = 'fi',
        bool $includePdf = false
    ) {
        // Do not include pdf bool to cachekey as it does not change results
        $cacheKey = __FUNCTION__ . "/$language";
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        $result = [
            'displayImages' => [],
            'ocr' => [],
            'fullres' => [],
        ];
        $xml = $this->getXmlRecord();
        $addToResults = function ($imageData) use (&$result) {
            $sizes = ['small', 'medium', 'large'];
            $formatted = $imageData;
            if (!empty($imageData['urls'])) {
                foreach ($sizes as $size) {
                    if (!isset($imageData['urls'][$size])) {
                        $formatted['urls'][$size] = reset($imageData['urls']);
                    }
                    if (!isset($imageData['pdf'][$size])) {
                        $formatted['pdf'][$size] = reset($imageData['pdf']);
                    }
                }
            }
            $formatted['downloadable'] = $this->allowRecordImageDownload($formatted);
            $result['displayImages'][] = $formatted;
        };
        $isExcludedFromOCR = function ($title) {
            foreach (self::EXCLUDE_OCR_TITLE_PARTS as $part) {
                if (false !== strpos($title, $part)) {
                    return true;
                }
            }
            return false;
        };
        // All images have same lazy-fetched rights.
        $rights = $this->getImageRights($language, true);
        $images = [];
        $ocrImages = [];
        $fullResImages = [];
        foreach ([$xml->did ?? [], $xml->did->daoset ?? []] as $root) {
            foreach ($root as $set) {
                if (!isset($set->dao)) {
                    continue;
                }
                $attr = $set->attributes();
                $descId = (string)($set->descriptivenote->p ?? '');
                $additional = $descId
                    ? $this->getAlternativeItems($descId)
                    : [];
                if (empty($ocrImages['info']) && empty($fullResImages['info'])) {
                    $ocrImages['info'] = $fullResImages['info'] = $additional;
                    if ($format = $additional['format'] ?? null) {
                        $ocrImages['info'][] = $format;
                        $fullResImages['info'][] = $format;
                    }
                }
                // localtype could be defined for daoset or for dao-element
                $parentType = (string)($attr->localtype ?? '');
                $parentType = self::IMAGE_MAP[$parentType] ?? self::IMAGE_LARGE;
                $parentSize = $parentType === self::IMAGE_FULLRES
                        ? self::IMAGE_LARGE : $parentType;
                $displayImage = [];
                $highResolution = [];
                foreach ($set->dao as $dao) {
                    $attr = $dao->attributes();
                    if (
                        !($title = (string)($attr->linktitle ?? ''))
                        || !($url = (string)($attr->href ?? ''))
                    ) {
                        continue;
                    }
                    $type = (string)($attr->localtype ?? $parentType ?: 'none');
                    $role = (string)($attr->linkrole ?? '');
                    $sort = (string)($attr->label ?? '');

                    // Save image to another array if match is found
                    if (self::IMAGE_OCR === $type && !$isExcludedFromOCR($title)) {
                        $ocrImages['items'][] = [
                            'label' => $title,
                            'url' => $url,
                            'sort' => $sort,
                        ];
                    } elseif (self::IMAGE_FULLRES === $type) {
                        $fullResImages['items'][] = [
                            'label' => $title,
                            'url' => $url,
                        ];
                    }
                    if (!$this->isUrlLoadable($url, $this->getUniqueID())) {
                        continue;
                    }
                    [$fileType, $format] = strpos($role, '/') > 0
                        ? explode('/', $role, 2)
                        : ['image', 'jpg'];
                    // Image might be original, can not be displayed in browser.
                    if ($this->isUndisplayableFormat($format)) {
                        $highResolution['original'][] = [
                            'data' => [],
                            'url' => $url,
                            'format' => $format,
                        ];
                        continue;
                    }
                    if (empty($displayImage)) {
                        $displayImage = [
                            'urls' => [],
                            'description' => $title,
                            'rights' => $rights,
                            'descId' => $descId,
                            'sort' => $sort,
                            'type' => $type,
                            'pdf' => [],
                            'highResolution' => [],
                        ];
                    }

                    if ($size = self::IMAGE_MAP[$type] ?? false || $parentSize) {
                        if (false === $size) {
                            $size = $parentSize;
                        } else {
                            $size = ($size === self::IMAGE_FULLRES)
                                ? self::IMAGE_LARGE
                                : $size;
                        }

                        if (isset($displayImage['urls'][$size])) {
                            // Add old stash to results.
                            $displayImage['highResolution'] = $highResolution;
                            $addToResults($displayImage);
                            $displayImage = [
                                'urls' => [],
                                'description' => $title,
                                'rights' => $rights,
                                'descId' => $descId,
                                'sort' => $sort,
                                'type' => $type,
                                'pdf' => [],
                                'highResolution' => [],
                            ];
                        }
                        $displayImage['urls'][$size] = $url;
                        $displayImage['pdf'][$size] = $role === 'application/pdf';
                    }
                }
                if (!empty($displayImage)) {
                    $displayImage['highResolution'] = $highResolution;
                    $images[] = $displayImage;
                    $displayImage = [];
                    $highResolution = [];
                }
            }
        }

        if (!empty($images)) {
            foreach ($images as $image) {
                // If there is any leftover highresolution images,
                // save them just in case
                if (!empty($highResolution)) {
                    $image['highResolution'] = $highResolution;
                    $highResolution = [];
                }
                $addToResults($image);
            }
        }
        if (!empty($ocrImages['items'])) {
            $this->sortImageUrls($ocrImages['items']);
            $result['ocr'] = $ocrImages;
        }
        if (!empty($fullResImages['items'])) {
            $result['fullres'] = $fullResImages;
        }

        return $this->cache[$cacheKey] = $result;
    }

    /**
     * Get an array of physical descriptions of the item.
     *
     * @return array
     */
    public function getPhysicalDescriptions()
    {
        $xml = $this->getXmlRecord();
        if (!isset($xml->did->physdesc)) {
            return [];
        }

        return $this->getDisplayLabel($xml->did, 'physdesc', true);
    }

    /**
     * Get description of content.
     *
     * @return string
     */
    public function getContentDescription()
    {
        $xml = $this->getXmlRecord();
        if (!isset($xml->controlaccess->genreform)) {
            return [];
        }

        foreach ($xml->controlaccess->genreform as $genre) {
            if (
                ! isset($genre->attributes()->encodinganalog)
                || (string)$genre->attributes()->encodinganalog !== 'ahaa:AI46'
            ) {
                continue;
            }
            if ($label = $this->getDisplayLabel($genre)) {
                return $label[0];
            }
        }

        return null;
    }

    /**
     * Get the statement of responsibility that goes with the title (i.e. "by John
     * Smith").
     *
     * @return string
     */
    public function getTitleStatement()
    {
        $xml = $this->getXmlRecord();
        if (!isset($xml->bibliography->p)) {
            return null;
        }
        if ($label = $this->getDisplayLabel($xml->bibliography, 'p', true)) {
            return $label[0];
        } elseif ($label = $this->getDisplayLabel($xml->bibliography, 'p')) {
            return $label[0];
        }
        return null;
    }

    /**
     * Get the statement of responsibility that goes with the title.
     * Returns an array of statements that contain the fields
     * 'text' and 'url' (when available).
     *
     * @return array
     */
    public function getTitleStatementsExtended(): array
    {
        $xml = $this->getXmlRecord();
        if (!isset($xml->bibliography->p)) {
            return [];
        }
        $localeResults = $results = [];
        foreach ($xml->bibliography->p ?? [] as $p) {
            $text = (string)$p;
            $url = isset($p->ref)
                 ? (string)($p->ref->attributes()->href ?? '') : '';
            if ($url && $this->urlBlocked($url, $text)) {
                $url = '';
            }
            $data = compact('text', 'url');
            $results[] = $data;
            $lang = $this->detectNodeLanguage($p);
            if ($lang['preferred'] ?? false) {
                $localeResults[] = $data;
            }
        }
        return $localeResults ?: $results ?: [];
    }

    /**
     * Get access restriction notes for the record.
     *
     * @return string[] Notes
     */
    public function getAccessRestrictions()
    {
        $xml = $this->getXmlRecord();
        $result = [];
        if (isset($xml->userestrict)) {
            foreach ($xml->userestrict as $node) {
                if ($label = $this->getDisplayLabel($node, 'p', true)) {
                    if (empty($label[0])) {
                        continue;
                    }
                    $result[] = $label[0];
                }
            }
            if (empty($result)) {
                foreach ($xml->userestrict as $node) {
                    if ($label = $this->getDisplayLabel($node, 'p')) {
                        if (empty($label[0])) {
                            continue;
                        }
                        $result[] = $label[0];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Get extended access restriction notes for the record.
     *
     * @return string[]
     */
    public function getExtendedAccessRestrictions()
    {
        $xml = $this->getXmlRecord();
        $restrictions = [];
        foreach (self::ACCESS_RESTRICT_TYPES as $type) {
            $restrictions[$type] = [];
        }

        $processNode = function ($access) use (&$restrictions) {
            $attr = $access->attributes();
            if (! isset($attr->encodinganalog)) {
                $restrictions['general'] = array_merge(
                    $restrictions['general'],
                    $this->getDisplayLabel($access, 'p', true)
                );
            } else {
                $type = (string)$attr->encodinganalog;
                if (in_array($type, self::ACCESS_RESTRICT_TYPES)) {
                    switch ($type) {
                        case 'ahaa:KR7':
                            $label = $this->getDisplayLabel(
                                $access->p->name,
                                'part',
                                true
                            );
                            break;
                        case 'ahaa:KR9':
                            $label = [(string)($access->p->date ?? '')];
                            break;
                        default:
                            $label = $this->getDisplayLabel($access, 'p');
                    }
                    if ($label) {
                        // These are displayed under the same heading
                        if (in_array($type, ['ahaa:KR2', 'ahaa:KR3'])) {
                            $type = 'ahaa:KR1';
                        }
                        $restrictions[$type]
                            = array_merge($restrictions[$type], $label);
                    }
                }
            }
        };

        foreach ($xml->accessrestrict ?? [] as $accessNode) {
            $processNode($accessNode);
            foreach ($accessNode->accessrestrict ?? [] as $accessNode) {
                $processNode($accessNode);
                foreach ($accessNode->accessrestrict ?? [] as $accessNode) {
                    $processNode($accessNode);
                }
            }
        }

        // Sort
        $order = array_flip(self::ACCESS_RESTRICT_TYPES);
        $orderCnt = count($order);
        $sortFn = function ($a, $b) use ($order, $orderCnt) {
            $pos1 = $order[$a] ?? $orderCnt;
            $pos2 = $order[$b] ?? $orderCnt;
            return $pos1 - $pos2;
        };
        uksort($restrictions, $sortFn);
        // Rename keys to match translations and filter duplicates
        $renamedKeys = [];
        foreach ($restrictions as $key => $val) {
            if (empty($val)) {
                continue;
            }
            $key = str_replace(':', '_', $key);
            $renamedKeys[$key] = array_unique($val);
        }

        return $renamedKeys;
    }

    /**
     * Get material condition notes for the record.
     *
     * @return string[] Notes
     */
    public function getMaterialCondition()
    {
        $xml = $this->getXmlRecord();
        foreach ($xml->accessrestrict ?? [] as $accessrestrict) {
            foreach ($accessrestrict->accessrestrict ?? [] as $node) {
                $attr = $node->attributes();
                $encoding = (string)$attr->encodinganalog;
                if (
                    $encoding === self::ACCESS_RESTRICT_MATERIAL_CONDITION
                ) {
                    return
                        $this->getDisplayLabel($node, 'p', true)
                        ?: $this->getDisplayLabel($node, 'p', false);
                }
            }
        }
        return [];
    }

    /**
     * Return type of access restriction for the record.
     *
     * @param string $language Language
     *
     * @return mixed array with keys:
     *   'copyright'   Copyright (e.g. 'CC BY 4.0')
     *   'link'        Link to copyright info, see IndexRecord::getRightsLink
     *   or false if no access restriction type is defined.
     */
    public function getAccessRestrictionsType($language)
    {
        $xml = $this->getXmlRecord();
        $restrictions = [];
        foreach ($xml->userestrict as $node) {
            if (!$node->p) {
                continue;
            }
            foreach ($node->p as $p) {
                if ($label = $this->getDisplayLabel($p, 'ref', true)) {
                    if (empty($label[0])) {
                        continue;
                    }
                    $restrictions[] = $label[0];
                    break;
                }
            }
        }
        if (empty($restrictions)) {
            foreach ($xml->userestrict as $node) {
                if (!$node->p) {
                    continue;
                }
                foreach ($node->p as $p) {
                    if ($label = $this->getDisplayLabel($p, 'ref')) {
                        if (empty($label[0])) {
                            continue;
                        }
                        $restrictions[] = $label[0];
                        break;
                    }
                }
            }
        }
        if (empty($restrictions)) {
            if (! $restrictions = $this->getAccessRestrictions()) {
                return false;
            }
        }

        $copyright = $this->getMappedRights($restrictions[0]);
        $data = [];
        $data['copyright'] = $copyright;
        if ($link = $this->getRightsLink($copyright, $language)) {
            $data['link'] = $link;
        }
        return $data;
    }

    /**
     * Return image rights.
     *
     * @param string $language       Language
     * @param bool   $skipImageCheck Whether to check that images exist
     *
     * @return mixed array with keys:
     *   'copyright'   Copyright (e.g. 'CC BY 4.0') (optional)
     *   'description' Human readable description (array)
     *   'link'        Link to copyright info
     *   or false if the record contains no images
     */
    public function getImageRights($language, $skipImageCheck = false)
    {
        if (!$skipImageCheck && !$this->getAllImages($language)) {
            return false;
        }

        $rights = [];

        if ($type = $this->getAccessRestrictionsType($language)) {
            $rights['copyright'] = $type['copyright'];
            if (isset($type['link'])) {
                $rights['link'] = $type['link'];
            }
        }
        $desc = $this->getAccessRestrictions();
        if ($desc && count($desc)) {
            $rights['description'] = $desc[0];
        }

        return isset($rights['copyright']) || isset($rights['description'])
            ? $rights : false;
    }

    /**
     * Get all subject headings associated with this record with extended data.
     * (see getAllSubjectHeadings).
     *
     * @return array
     */
    public function getAllSubjectHeadingsExtended()
    {
        return $this->getAllSubjectHeadings(true);
    }

    /**
     * Get all subject headings associated with this record.  Each heading is
     * returned as an array of chunks, increasing from least specific to most
     * specific.
     *
     * @param bool $extended Whether to return a keyed array with the following
     * keys:
     * - heading: the actual subject heading chunks
     * - type: heading type
     * - source: source vocabulary
     * - id: first authority id (if defined)
     * - ids: multiple authority ids (if defined)
     * - authType: authority type (if id is defined)
     *
     * @return array
     */
    public function getAllSubjectHeadings($extended = false)
    {
        $headings = [];
        $headings = $this->getTopics();

        // geographic names are returned in getRelatedPlacesExtended
        foreach (['genre', 'era'] as $field) {
            $headings = array_merge(
                $headings,
                array_map(
                    function ($term) {
                        return ['data' => $term];
                    },
                    $this->fields[$field] ?? []
                )
            );
        }
        $headings = array_merge(
            $headings,
            $this->getRelatedPlacesExtended(['aihe'], [])
        );

        // The default index schema doesn't currently store subject headings in a
        // broken-down format, so we'll just send each value as a single chunk.
        // Other record drivers (i.e. SolrMarc) can offer this data in a more
        // granular format.
        $callback = function ($i) use ($extended) {
            if ($extended) {
                $data = [
                    'heading' => [$i['data']],
                    'type' => 'topic',
                    'source' => $i['source'] ?? '',
                    'detail' => $i['detail'] ?? '',
                ];
                if ($id = $i['id'] ?? '') {
                    $data['id'] = $id;
                    // Categorize non-URI ID's as Unknown Names, since the
                    // actual authority format can not be determined from metadata.
                    $data['authType'] = preg_match('/^https?:/', $id)
                        ? null : 'Unknown Name';
                }
            } else {
                return [$i['data']];
            }
            return $data;
        };
        return array_map($callback, $headings);
    }

    /**
     * Get related places.
     *
     * @param array $include Relator attributes to include
     * @param array $exclude Relator attributes to exclude
     *
     * @return array
     */
    public function getRelatedPlacesExtended($include = [], $exclude = ['aihe'])
    {
        $record = $this->getXmlRecord();
        if (!isset($record->controlaccess->geogname)) {
            return [];
        }

        $languageResult = $languageResultDetail = $result = $resultDetail = [];
        $languages = $this->preferredLanguage
            ? $this->mapLanguageCode($this->preferredLanguage)
            : [];

        foreach ($record->controlaccess->geogname as $name) {
            $attr = $name->attributes();
            $relator = (string)$attr->relator;
            if (!empty($include) && !in_array($relator, $include)) {
                continue;
            }
            if (!empty($exclude) && in_array($relator, $exclude)) {
                continue;
            }
            $parts = [];
            foreach ($name->part ?? [] as $place) {
                if ($p = trim((string)$place)) {
                    $parts[] = $p;
                }
            }
            if ($parts) {
                $part = implode(', ', $parts);
                $data = ['data' => $part, 'detail' => $relator];
                if (
                    $attr->lang && in_array((string)$attr->lang, $languages)
                    && !in_array($part, $languageResult)
                ) {
                    $languageResultDetail[] = $data;
                    $languageResult[] = $part;
                } elseif (!in_array($part, $result)) {
                    $resultDetail[] = $data;
                    $result[] = $part;
                }
            }
        }
        return $languageResultDetail ?: $resultDetail;
    }

    /**
     * Get the unitdate field.
     *
     * @return array
     */
    public function getUnitDates()
    {
        $record = $this->getXmlRecord();
        $result = [];

        foreach ($record->did->unitdate ?? [] as $udate) {
            $attr = $udate->attributes();
            $normal = (string)$attr->normal;
            $dates = $start = $end = '';
            $unknown = false;
            if ($normal) {
                if (strstr($normal, '/')) {
                    [$start, $end] = explode('/', $normal);
                } else {
                    $start = $normal;
                    $unknown = strstr($start, 'u');
                }
                $dates = $this->parseDate($start, true);
                if (
                    $end
                    && ($parsedEnd = $this->parseDate($end, false)) !== $dates
                    && $parsedEnd
                ) {
                    $ndash
                        = html_entity_decode('&#x2013;', ENT_NOQUOTES, 'UTF-8');
                    $dates .= "{$ndash}{$parsedEnd}";
                } elseif ($dates && $unknown) {
                    $dates = $this->translate(
                        'year_decade_or_century',
                        ['%%year%%' => $dates]
                    );
                }
            }

            if ($desc = $attr->normal ?? null) {
                $desc = $attr->label ?? null;
            }
            $ud = (string)$udate === '-' ? '' : (string)$udate;
            $result[] = [
                'data' => $dates ? $dates : $ud,
                'detail' => (string)$desc,
            ];
        }
        if ($result) {
            return $result;
        }

        if (isset($record->did->unitdatestructured->datesingle)) {
            foreach ($record->did->unitdatestructured->datesingle as $date) {
                $attr = $date->attributes();
                if ($attr->standarddate) {
                    $result[] = ['data' => (string)$attr->standarddate];
                }
            }
            if ($result) {
                return array_unique($result);
            }
        }

        return $this->getUnitDate();
    }

    /**
     * Parse dates
     *
     * @param string $date  date to parse
     * @param bool   $start whether given date is the start date of a year range
     *
     * @return string
     */
    public function parseDate($date, $start = true)
    {
        if (in_array($date, ['unknown', 'open'])) {
            return '';
        }
        $parts = explode('-', $date);
        $year = 0;
        $month = 0;
        $day = 0;
        if (isset($parts[2]) && $parts[2] !== 'uu') {
            $day = $parts[2];
        }
        if (isset($parts[1]) && $parts[1] !== 'uu') {
            $month = $parts[1];
        }
        $defaultY = $start ? '0' : '9';

        $year = str_replace('u', $defaultY, $parts[0]);
        $result = '';
        if ($day && $month && !strstr($parts[0], 'u')) {
            $result = "{$day}.{$month}.";
        }

        $result .= $year;
        return $result;
    }

    /**
     * Get related records (used by RecordDriverRelated - Related module)
     *
     * Returns an associative array of group => records, where each item in
     * records is either a record id or an array with keys:
     * - id: record identifier to search
     * - field (optional): Solr field to search in, defaults to 'identifier'.
     *                     In addition, the query includes a filter that limits the
     *                     results to the same datasource as the issuing record.
     *
     * The array may contain the following keys:
     *   - continued-from
     *   - part-of
     *   - contains
     *   - see-also
     *
     * Examples:
     * - continued-from
     *     - source1.1234
     *     - ['id' => '1234']
     *     - ['id' => '1234', 'field' => 'foo']
     *
     * @return array
     */
    public function getRelatedRecords()
    {
        $record = $this->getXmlRecord();

        if (!isset($record->relations->relation)) {
            return [];
        }

        $relations = [];
        foreach ($record->relations->relation as $relation) {
            $attr = $relation->attributes();
            foreach (['encodinganalog', 'href', 'arcrole'] as $key) {
                if (!isset($attr->{$key})) {
                    continue 2;
                }
            }
            if ((string)$attr->encodinganalog !== self::RELATION_RECORD) {
                continue;
            }
            $role = self::RELATION_MAP[(string)$attr->arcrole] ?? null;
            if (!$role) {
                continue;
            }
            if (!isset($relations[$role])) {
                $relations[$role] = [];
            }
            // Search by id in identifier-field
            $relations[$role][]
                = ['id' => (string)$attr->href, 'field' => 'identifier'];
        }
        return $relations;
    }

    /**
     * Whether the record has related records declared in metadata.
     * (used by RecordDriverRelated related module).
     *
     * @return bool
     */
    public function hasRelatedRecords()
    {
        return !empty($this->getRelatedRecords());
    }

    /**
     * Get all record links related to the current record. Each link is returned as
     * array.
     * Format:
     * array(
     *        array(
     *               'title' => label_for_title
     *               'value' => link_name
     *               'link'  => link_URI
     *        ),
     *        ...
     * )
     *
     * @return null|array
     */
    public function getAllRecordLinks()
    {
        $record = $this->getXmlRecord();

        if (!isset($record->relations->relation)) {
            return [];
        }

        $relations = [];
        foreach ($record->relations->relation as $relation) {
            $attr = $relation->attributes();
            foreach (['encodinganalog', 'relationtype', 'href'] as $key) {
                if (!isset($attr->{$key})) {
                    continue 2;
                }
            }
            if (
                (string)$attr->relationtype !== 'resourcerelation'
                || in_array((string)$attr->encodinganalog, self::IGNORED_RELATIONS)
            ) {
                continue;
            }
            $value = $href = (string)$attr->href;
            if ($title = (string)$relation->relationentry) {
                $value = $title;
            }
            $relations[] = [
                'value' => $value,
                'detail' => !empty($attr->arcrole) ? (string)$attr->arcrole : null,
                'link' => [
                    'value' => $href,
                    'type' => 'identifier',
                    'filter' => ['datasource_str_mv' => $this->getDatasource()],
                ],
            ];
        }
        return $relations;
    }

    /**
     * Get the hierarchy parents associated with this item (empty if none).
     * The parents are listed starting from the root of the hierarchy,
     * i.e. the closest parent is at the end of the result array.
     *
     * @param string[] $levels Optional list of level types to return
     * (defaults to series and subseries)
     *
     * @return array Array with id and title
     */
    protected function getHierarchyParents(
        array $levels = self::SERIES_LEVELS
    ): array {
        $xml = $this->getXmlRecord();
        if (!isset($xml->{'add-data'}->parent)) {
            return [];
        }
        $result = [];
        foreach ($xml->{'add-data'}->parent as $parent) {
            $attr = $parent->attributes();
            if (!in_array((string)$attr->level, $levels)) {
                continue;
            }
            $result[] = [
                'id' => $this->getDatasource() . '.' . (string)$attr->id,
                'title' => (string)$attr->title,
            ];
        }
        return array_reverse($result);
    }

    /**
     * Get parent series
     *
     * @return array
     */
    public function getParentSeries(): array
    {
        return $this->getHierarchyParents();
    }

    /**
     * Get the hierarchy_parent_id(s) associated with this item (empty if none).
     *
     * @param string[] $levels Optional list of level types to return
     * (defaults to series and subseries)
     *
     * @return array
     */
    public function getHierarchyParentID(array $levels = self::SERIES_LEVELS): array
    {
        if ($parents = $this->getHierarchyParents($levels)) {
            return array_map(
                function ($parent) {
                    return $parent['id'];
                },
                $parents
            );
        }
        return parent::getHierarchyParentID($levels);
    }

    /**
     * Get the parent title(s) associated with this item (empty if none).
     * (defaults to series and subseries)
     *
     * @param string[] $levels Optional list of level types to return
     *
     * @return array
     */
    public function getHierarchyParentTitle(
        array $levels = self::SERIES_LEVELS
    ): array {
        if ($parents = $this->getHierarchyParents($levels)) {
            return array_map(
                function ($parent) {
                    return $parent['title'];
                },
                $parents
            );
        }
        return parent::getHierarchyParentTitle($levels);
    }

    /**
     * Get place of storage.
     *
     * @return string|null
     */
    public function getPlaceOfStorage(): ?string
    {
        $xml = $this->getXmlRecord();
        $firstLoc = $defaultLoc = null;
        foreach ($xml->did->physloc ?? [] as $loc) {
            if (!$firstLoc) {
                $firstLoc = (string)$loc;
            }
            if ($lang = $this->detectNodeLanguage($loc)) {
                if ($lang['preferred']) {
                    return (string)$loc;
                }
                if ($lang['default']) {
                    $defaultLoc = (string)$loc;
                }
            }
        }
        return $defaultLoc ?? $firstLoc;
    }

    /**
     * Get filing unit.
     *
     * @return string|null
     */
    public function getFilingUnit(): ?string
    {
        $xml = $this->getXmlRecord();
        return isset($xml->did->container)
            ? (string)$xml->did->container : null;
    }

    /**
     * Get appraisal information.
     *
     * @return string[]
     */
    public function getAppraisal(): array
    {
        $xml = $this->getXmlRecord();
        $result = $localeResult = [];
        $preferredLangCodes = $this->mapLanguageCode($this->preferredLanguage);
        foreach ($xml->appraisal->p ?? [] as $p) {
            $value = (string)$p;
            $result[] = $value;
            if (in_array((string)$p->attributes()->lang, $preferredLangCodes)) {
                $localeResult[] = $value;
            }
        }
        return $localeResult ?: $result;
    }

    /**
     * Get material arrangement information.
     *
     * @return string[]
     */
    public function getMaterialArrangement(): array
    {
        $xml = $this->getXmlRecord();
        $result = [];
        foreach ($xml->arrangement ?? [] as $arrangement) {
            $label = $this->getDisplayLabel($arrangement, 'p');
            $localeLabel = $this->getDisplayLabel($arrangement, 'p', true);
            $result = array_merge($result, $localeLabel ?: $label);
        }
        return $result;
    }

    /**
     * Get notes on finding aids related to the record.
     *
     * @return array
     */
    public function getFindingAids()
    {
        $xml = $this->getXmlRecord();
        $result = $localeResult = [];
        foreach ($xml->otherfindaid ?? [] as $aid) {
            $result[] = (string)($aid->p ?? '');
            if ($text = $this->getDisplayLabel($aid, 'p')) {
                $localeResult[] = $text[0];
            }
        }
        return $localeResult ?: $result ?: parent::getFindingAids();
    }

    /**
     * Get notes with URLs on finding aids related to the record
     *
     * @return array
     */
    public function getFindingAidsExtended()
    {
        $xml = $this->getXmlRecord();
        $result = $localeResult = [];
        foreach ($xml->otherfindaid ?? [] as $aid) {
            foreach ($aid->p as $p) {
                $data = [
                    'label' => (string)$p,
                    'url' => $p->ref
                        ? (string)($p->ref->attributes()->href ?? '')
                        : '',
                ];
                $result[] = $data;
                $lang = $this->detectNodeLanguage($p);
                if ($lang['preferred'] ?? false) {
                    $localeResult[] = $data;
                }
            }
        }
        return $localeResult ?: $result;
    }

    /**
     * Get container information.
     *
     * @return string
     */
    public function getContainerInformation()
    {
        $xml = $this->getXmlRecord();
        return (string)($xml->did->container ?? '');
    }

    /**
     * Sort an array of image URLs in place.
     *
     * @param array  $urls  URLs
     * @param string $field Field to use for sorting.
     * The field value is casted to int before sorting.
     *
     * @return void
     */
    protected function sortImageUrls(&$urls, $field = 'sort')
    {
        usort(
            $urls,
            function ($a, $b) use ($field) {
                $f1 = (int)$a[$field];
                $f2 = (int)$b[$field];
                if ($f1 === $f2) {
                    return 0;
                } elseif ($f1 < $f2) {
                    return -1;
                } else {
                    return 1;
                }
            }
        );
    }

    /**
     * Return physical items.
     *
     * @return array
     */
    protected function getPhysicalItems()
    {
        return array_filter(
            $this->getAlternativeItems(),
            function ($item) {
                return empty($item['online']) && !empty($item['location']);
            }
        );
    }

    /**
     * Get topics.
     *
     * @return array
     */
    protected function getTopics(): array
    {
        $record = $this->getXmlRecord();

        $topics = [];
        if (isset($record->controlaccess->subject)) {
            foreach ([true, false] as $obeyPreferredLanguage) {
                foreach ($record->controlaccess->subject as $subject) {
                    $attr = $subject->attributes();
                    if (
                        $topic = $this->getDisplayLabel(
                            $subject,
                            'part',
                            $obeyPreferredLanguage
                        )
                    ) {
                        if (!$topic[0]) {
                            continue;
                        }
                        $topics[] = [
                            'data' => $topic[0],
                            'id' => (string)$attr->identifier,
                            'source' => (string)$attr->source,
                            'detail' => (string)$subject->attributes()->relator,
                        ];
                    }
                }
                if (!empty($topics)) {
                    return $topics;
                }
            }
        }
        return $topics;
    }

    /**
     * Return translated repository display name from metadata.
     *
     * @return string
     */
    protected function getRepositoryName()
    {
        $record = $this->getXmlRecord();

        if (isset($record->did->repository->corpname)) {
            foreach ($record->did->repository->corpname as $corpname) {
                if ($name = $this->getDisplayLabel($corpname, 'part', true)) {
                    return $name[0];
                }
            }
        }
        return null;
    }

    /**
     * Get related material
     *
     * @return array
     */
    protected function getOtherRelatedMaterial()
    {
        $xml = $this->getXmlRecord();
        $result = [];
        if (isset($xml->relatedmaterial)) {
            foreach ($xml->relatedmaterial as $material) {
                $text = $this->getDisplayLabel(
                    $material->p,
                    'ref'
                );
                $url = (string)$material->attributes()->href ?? '';
                if ($this->urlBlocked($url, $text[0])) {
                    $url = '';
                }
                $result[] = ['text' => $text[0], 'url' => $url];
            }
        }
        return $result;
    }

    /**
     * Helper function for returning an array of summary strings or summary items for
     * the record.
     *
     * Each summary item includes the following fields:
     * - 'text'
     *     Summary text
     * - 'lang'
     *     Language information array or null, as returned by detectNodeLanguage()
     * - 'url'
     *     URL from a ref element or null
     *
     * @param boolean $returnItems Return summary items? Optional, defaults to false.
     *
     * @return array
     */
    protected function doGetSummary($returnItems = false): array
    {
        $xml = $this->getXmlRecord();
        $stringResult = $itemResult = $localeItemResult = [];
        if (!empty($xml->scopecontent)) {
            foreach ($xml->scopecontent as $el) {
                if (isset($el->attributes()->encodinganalog)) {
                    continue;
                }
                if (isset($el->head) && (string)$el->head !== 'Tietosisältö') {
                    continue;
                }
                if (!$returnItems) {
                    if ($desc = $this->getDisplayLabel($el, 'p', true)) {
                        $stringResult = array_merge($stringResult, $desc);
                    }
                } else {
                    foreach ($el->p ?? [] as $p) {
                        $text = (string)$p;
                        $lang = $this->detectNodeLanguage($p);
                        $url = isset($p->ref)
                            ? (string)($p->ref->attributes()->href ?? '') : '';
                        if ($url && $this->urlBlocked($url, $text)) {
                            $url = '';
                        }
                        $data = compact('text', 'lang', 'url');
                        $itemResult[] = $data;
                        if ($lang['preferred'] ?? false) {
                            $localeItemResult[] = $data;
                        }
                    }
                }
            }
        }

        if (!$returnItems) {
            return $stringResult;
        }
        if ($res = $localeItemResult ?: $itemResult) {
            return $res;
        }
        $summary = parent::getSummary();

        // Return parent summary text only if it differs from item history
        // (otherwise it gets displayed multiple times on record page).
        $itemHistory = trim($this->getItemHistory());
        $summary = array_filter(
            $summary,
            function ($item) use ($itemHistory) {
                return trim($item) !== $itemHistory;
            }
        );
        if ($summary) {
            if ($returnItems) {
                return array_map(
                    function ($text) {
                        return compact('text');
                    },
                    $summary
                );
            }
        }
        return $summary;
    }

    /**
     * Helper function for returning a specific language version of a display label.
     *
     * @param \SimpleXMLElement $node                  XML node
     * @param string            $childNodeName         Name of the child node that
     * contains the display label.
     * @param bool              $obeyPreferredLanguage If true, returns the
     * translation that corresponds with the current locale.
     * If false, the default language version 'fin' is returned. If not found,
     * the first display label is retured.
     *
     * @return string[]
     */
    protected function getDisplayLabel(
        $node,
        $childNodeName = 'part',
        $obeyPreferredLanguage = false
    ): array {
        if (! isset($node->$childNodeName)) {
            return [];
        }
        $allResults = [];
        $defaultLanguageResults = [];
        $languageResults = [];
        $lang = $this->detectNodeLanguage($node);
        $resolveLangFromChildNode = $lang === null;
        foreach ($node->{$childNodeName} as $child) {
            $name = trim((string)$child);
            $allResults[] = $name;

            if ($resolveLangFromChildNode) {
                $lang = $this->detectNodeLanguage($child);
            }
            if ($lang['default'] ?? false) {
                $defaultLanguageResults[] = $name;
            }
            if ($lang['preferred'] ?? false) {
                $languageResults[] = $name;
            }
        }

        if ($obeyPreferredLanguage && $lang) {
            return $languageResults;
        }
        if (! empty($languageResults)) {
            return $languageResults;
        } elseif (! empty($defaultLanguageResults)) {
            return $defaultLanguageResults;
        }

        return $allResults;
    }

    /**
     * Helper for detecting the language of an XML node.
     *
     * Compares the language attribute of the node to the user's preferred language.
     * Returns an array with keys:
     *
     * - 'value'
     *     Node language
     * - 'default'
     *     Is the node language the same as the default language?
     * - 'preferred'
     *     Is the node language the same as the user's preferred language?
     *
     * Returns null if the node doesn't have the language attribute.
     *
     * @param \SimpleXMLElement $node              XML node
     * @param string            $languageAttribute Name of the language attribute
     * @param string            $defaultLanguage   Default language
     *
     * @return null|array
     */
    protected function detectNodeLanguage(
        \SimpleXMLElement $node,
        string $languageAttribute = 'lang',
        string $defaultLanguage = 'fin'
    ): ?array {
        if (!isset($node->attributes()->{$languageAttribute})) {
            return null;
        }

        $languages = $this->preferredLanguage
            ? $this->mapLanguageCode($this->preferredLanguage)
            : [];

        $lang = (string)$node->attributes()->{$languageAttribute};
        return [
            'value' => $lang,
            'default' => $defaultLanguage === $lang,
            'preferred' => in_array($lang, $languages),
        ];
    }

    /**
     * Convert Finna language codes to EAD3 codes.
     *
     * @param string $languageCode Language code
     *
     * @return string[]
     */
    protected function mapLanguageCode($languageCode)
    {
        $langMap
            = ['fi' => ['fi','fin'], 'sv' => ['sv','swe'], 'en-gb' => ['en','eng']];
        return $langMap[$languageCode] ?? [$languageCode];
    }

    /**
     * Get role translation key
     *
     * @param string $role     EAD3 role
     * @param string $fallback Fallback to use when no supported role is found
     *
     * @return string Translation key
     */
    protected function translateRole($role, $fallback = null)
    {
        // Map EAD3 roles to CreatorRole translations
        $roleMap = [
            'http://rdaregistry.info/Elements/e/P20047' => 'ive',
            'http://rdaregistry.info/Elements/e/P20032' => 'ivr',
            'http://rdaregistry.info/Elements/w/P10046' => 'pbl',
            'http://www.rdaregistry.info/Elements/w/#P10311' => 'fac',
            'http://rdaregistry.info/Elements/e/P20042' => 'ctg',
            'http://rdaregistry.info/Elements/a/P50190' => 'cng',
            'http://rdaregistry.info/Elements/w/P10058' => 'art',
            'http://rdaregistry.info/Elements/w/P10066' => 'drt',
            'http://rdaregistry.info/Elements/e/P20033' => 'drm',
            'http://rdaregistry.info/Elements/e/P20024' => 'spk',
            'http://rdaregistry.info/Elements/w/P10204' => 'lyr',
            'http://rdaregistry.info/Elements/e/P20029' => 'arr',
            'http://rdaregistry.info/Elements/w/P10053' => 'cmp',
            'http://rdaregistry.info/Elements/w/P10065' => 'aut',
            'http://rdaregistry.info/Elements/w/P10298' => 'edt',
            'http://rdaregistry.info/Elements/w/P10064' => 'pro',
            'http://www.rdaregistry.info/Elements/u/P60429' => 'pht',
            'http://www.rdaregistry.info/Elements/e/#P20052' => 'rpy',
            'http://rdaregistry.info/Elements/w/P10304' => 'rpy',

            'http://rdaregistry.info/Elements/w/P10061' => 'rda:writer',
            'http://rdaregistry.info/Elements/a/P50045' => 'rda:collector',
            'http://www.rdaregistry.info/Elements/i/#P40019' => 'rda:former-owner',
        ];

        return $roleMap[$role] ?? $fallback;
    }
}
