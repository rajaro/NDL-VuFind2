<?php

/**
 * Model for LIDO records in Solr.
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2015-2022.
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
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */

namespace Finna\RecordDriver;

/**
 * Model for LIDO records in Solr.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Konsta Raunio <konsta.raunio@helsinki.fi>
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @author   Aleksi Peebles <aleksi.peebles@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
class SolrLido extends \VuFind\RecordDriver\SolrDefault implements \Laminas\Log\LoggerAwareInterface
{
    use Feature\SolrFinnaTrait;
    use Feature\FinnaXmlReaderTrait;
    use Feature\FinnaUrlCheckTrait;
    use \VuFind\Log\LoggerAwareTrait;

    /**
     * Map from site locale to Lido language codes.
     */
    public const LANGUAGE_CODES = [
        'fi' => ['fi','fin'],
        'sv' => ['sv','swe'],
        'en-gb' => ['en','eng'],
    ];

    /**
     * Image types array
     *
     * @var array
     */
    protected $imageTypes = [
        'image_thumb' => 'small',
        'thumb' => 'small',
        'medium' => 'medium',
        'image_large' => 'large',
        'large' => 'large',
        'zoomview' => 'large',
        'image_master' => 'master',
        'image_original' => 'original',
    ];

    /**
     * Model types array
     *
     * @var array
     */
    protected $modelTypes = [
        'preview_3D' => 'preview',
        'provided_3D' => 'provided',
    ];

    /**
     * Audio types array
     *
     * @var array
     */
    protected $audioTypes = [
        'preview_audio' => 'audio',
    ];

    /**
     * Video types array
     *
     * @var array
     */
    protected $videoTypes = [
        'preview_video' => 'video',
    ];

    /**
     * Document types array
     *
     * @var array
     */
    protected $documentTypes = [
        'preview_text' => 'document',
        'provided_text' => 'document',
    ];

    /**
     * Supported audio formats
     *
     * @var array
     */
    protected $supportedAudioFormats = [
        'mp3' => 'mpeg',
        'wav' => 'wav',
    ];

    /**
     * Supported video formats
     *
     * @var array
     */
    protected $supportedVideoFormats = [
        'mp4' => 'video/mp4',
    ];

    /**
     * Description type mappings
     *
     * @var array
     */
    protected $descriptionTypeMappings = [
        'preview_video' => 'displayLink',
        'preview_audio' => 'displayLink',
        'preview_text' => 'displayLink',
        'provided_text' => 'displayLink',
    ];

    /**
     * Measurement type mappings
     *
     * @var array
     */
    protected $measurementTypeMappings = [
        'leveys' => 'width',
        'korkeus' => 'height',
        'koko' => 'size',
    ];

    /**
     * Measurement unit mappings
     *
     * @var array
     */
    protected $measurementUnitMappings = [
        'tavua' => 'byte',
        'bytes' => 'byte',
        'pikseli' => 'pixel',
    ];

    /**
     * Array of web friendly model formats
     *
     * @var array
     */
    protected $displayableModelFormats = ['gltf', 'glb'];

    /**
     * Array of excluded classifications
     *
     * @var array
     */
    protected $excludedClassifications = ['language'];

    /**
     * Array of excluded measurements
     *
     * @var array
     */
    protected $excludedMeasurements = ['extent'];

    /**
     * Events used for author information.
     *
     * Key is event type, value is priority (lower is more important),
     *
     * @var array
     */
    protected $authorEvents = [
        'suunnittelu' => 1,
        'valmistus' => 2,
    ];

    /**
     * Mapping from related work type to possible type attributes
     *
     * @var array
     */
    protected $relatedWorkTypeMap = [
        'archive' => ['archive', 'arkisto', 'henkilöarkisto', 'yhteisöarkisto'],
        'collection' => ['collection', 'kokoelma', 'purchase batch', 'hankintaerä'],
        'subcollection' => ['subcollection', 'alakokoelma'],
        'series' => ['series', 'sarja', 'hankintaerä (lapsi)'],
        'work' => ['work', 'teos'],
    ];

    /**
     * Return access restriction notes for the record.
     *
     * @param string $language Optional primary language to look for
     *
     * @return array
     */
    public function getAccessRestrictions($language = '')
    {
        $restrictions = [];
        $rights = $this->getXmlRecord()->xpath(
            'lido/administrativeMetadata/resourceWrap/resourceSet/rightsResource/'
            . 'rightsType'
        );
        if ($rights) {
            foreach ($rights as $right) {
                if (!isset($right->conceptID)) {
                    continue;
                }
                $type = strtolower((string)$right->conceptID->attributes()->type);
                if ($type == 'copyright') {
                    $term = (string)$this->getLanguageSpecificItem(
                        $right->term,
                        $language
                    );
                    if ($term) {
                        $restrictions[] = $term;
                    }
                }
            }
        }
        return array_unique($restrictions);
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
        $rightsNodes = $this->getXmlRecord()->xpath(
            'lido/administrativeMetadata/resourceWrap/resourceSet/rightsResource/'
            . 'rightsType'
        );

        foreach ($rightsNodes as $rights) {
            if ($conceptID = $rights->xpath('conceptID')) {
                $conceptID = $conceptID[0];
                $attributes = $conceptID->attributes();
                if ($attributes->type && strtolower($attributes->type) == 'copyright') {
                    $data = [];

                    $copyright = trim((string)$conceptID);
                    if ($copyright) {
                        $copyright = $this->getMappedRights($copyright);
                        $data['copyright'] = $copyright;

                        if ($link = $this->getRightsLink($copyright, $language)) {
                            $data['link'] = $link;
                        }
                        return $data;
                    }
                }
            }
        }
        return false;
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
     *   - rightsHolders Rights holders
     *   - creditLine  Credit line
     * - identifier  Image identifier
     * - type        Image type
     * - relationTypes Image relationships with the object
     * - dateTaken   Photo date taken
     * - perspectives Image perspectives
     *
     * @param string $language Language for textual information
     *
     * @return array
     */
    public function getAllImages($language = null)
    {
        $language ??= $this->getTranslatorLocale();
        $representations = $this->getRepresentations($language);
        return array_filter(array_column($representations, 'images'));
    }

    /**
     * Function to format given resourceMeasurementsSet to readable format
     *
     * @param \SimpleXmlElement $measurements of the image
     *
     * @return array
     */
    protected function formatImageMeasurements(
        \SimpleXmlElement $measurements
    ): array {
        $results = [];
        foreach ($measurements as $set) {
            $value = trim((string)$set->measurementValue);
            if (!$value) {
                continue;
            }
            $type = '';
            foreach ($set->measurementType as $t) {
                $type = trim((string)$t);
                $type = $this->measurementTypeMappings[$type] ?? $type;
                break;
            }
            $unit = '';
            foreach ($set->measurementUnit as $u) {
                $unit = trim((string)$u);
                $unit = $this->measurementUnitMappings[$unit] ?? $unit;
                break;
            }
            // The museumplus cannot handle image sizes with multiple layers
            // so explode the results and sum them if the value is too long.
            // Example of the value to be summed: 400030 400030 21313 223314.
            // Only do this with sizes.
            if ($type === 'size' && strlen($value) > 15) {
                $tmpValue = 0;
                foreach (explode(' ', $value) as $part) {
                    $tmpValue += (int)$part;
                }
                $value = $tmpValue;
            }
            $results[$type] = [
                'unit' => $unit,
                'value' => $value,
            ];
        }
        return $results;
    }

    /**
     * Get 3D models
     *
     * @return array
     */
    public function getModels(): array
    {
        $language = $this->getTranslatorLocale();
        $representations = $this->getRepresentations($language);
        return array_filter(array_column($representations, 'models'));
    }

    /**
     * Get audios
     *
     * @return array
     */
    protected function getAudios(): array
    {
        $language = $this->getTranslatorLocale();
        $representations = $this->getRepresentations($language);
        return array_filter(array_column($representations, 'audios'));
    }

    /**
     * Get videos
     *
     * @return array
     */
    protected function getVideos(): array
    {
        $language = $this->getTranslatorLocale();
        $representations = $this->getRepresentations($language);
        return array_filter(array_column($representations, 'videos'));
    }

    /**
     * Get documents
     *
     * @return array
     */
    public function getDocuments(): array
    {
        $language = $this->getTranslatorLocale();
        $representations = $this->getRepresentations($language);
        return array_values(
            array_filter(array_column($representations, 'documents'))
        );
    }

    /**
     * Parse given representations and return them in proper
     * associative array
     *
     * @param string $language language to get information
     *
     * @return array
     */
    protected function getRepresentations(string $language): array
    {
        $cacheKey = __FUNCTION__ . "/$language";
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $defaultRights = $this->getImageRights($language, true);

        $imageTypeKeys = array_keys($this->imageTypes);
        $modelTypeKeys = array_keys($this->modelTypes);
        $audioTypeKeys = array_keys($this->audioTypes);
        $videoTypeKeys = array_keys($this->videoTypes);
        $documentTypeKeys = array_keys($this->documentTypes);

        $results = [];
        $addToResults = function (
            array $images = [],
            array $models = [],
            array $audios = [],
            array $videos = [],
            array $documents = []
        ) use (&$results) {
            if ($images) {
                if (!isset($images['urls']['small'])) {
                    $images['urls']['small'] = $images['urls']['medium']
                        ?? $images['urls']['large'];
                }
                if (!isset($images['urls']['medium'])) {
                    $images['urls']['medium'] = $images['urls']['small'];
                }
                if (!isset($images['urls']['large'])) {
                    $images['urls']['large'] = $images['urls']['medium'];
                }
                $images['downloadable'] = $this->allowRecordImageDownload($images);
            }
            $results[] = compact(
                'images',
                'models',
                'audios',
                'videos',
                'documents'
            );
        };

        $xpath = '/lidoWrap/lido/administrativeMetadata/resourceWrap/resourceSet';
        foreach ($this->getXmlRecord()->xpath($xpath) as $resourceSet) {
            // Process rights first since we may need to duplicate them if there
            // are multiple representations in the set (non-standard)
            if (!($rights = $this->getResourceRights($resourceSet, $language))) {
                $rights = $defaultRights;
            }

            $imageUrls = [];
            $modelUrls = [];
            $audioUrls = [];
            $videoUrls = [];
            $documentUrls = [];
            $highResolution = [];

            $descriptions = $this->getResourceDescriptions($resourceSet, $language);
            foreach ($resourceSet->resourceRepresentation as $representation) {
                $linkResource = $representation->linkResource;
                $url = trim((string)$linkResource);
                if (!$url || !$this->isUrlLoadable($url, $this->getUniqueID())) {
                    continue;
                }
                $format = (string)($linkResource['formatResource'] ?? '');

                // Representation without a type is handled as a single image
                if (!($type = (string)($representation['type'] ?? ''))) {
                    // We already have URL's, store them in the
                    // final results first. This shouldn't
                    // happen unless there are multiple
                    // images without type in the same set.
                    if ($imageUrls) {
                        $addToResults(
                            [
                                'urls' => $imageUrls,
                                'description' => '',
                                'rights' => $rights,
                            ]
                        );
                        $imageUrls = [];
                    }
                    $imageUrls['small'] = $imageUrls['medium']
                        = $imageUrls['large'] = $url;
                    continue;
                }

                // If there is a description set for the representation
                // Try to find one with correct language, else get the first
                $description = '';
                if ($key = $this->descriptionTypeMappings[$type] ?? '') {
                    if ($foundDescriptions = $descriptions[$key] ?? []) {
                        $description
                            = $foundDescriptions[$language]
                                ?? reset($foundDescriptions);
                    }
                }

                // Representation is an image
                if (in_array($type, $imageTypeKeys)) {
                    $image = $this->getImage(
                        $url,
                        $type,
                        $language,
                        $resourceSet->resourceID,
                        $format,
                        $representation->resourceMeasurementsSet
                    );
                    if ($image) {
                        if (!empty($image['displayImage'])) {
                            $imageUrls
                                = array_merge($imageUrls, $image['displayImage']);
                        }
                        // Does image have a highresolution for download?
                        if (!empty($image['highResolution'])) {
                            $highResolution = array_merge(
                                $highResolution,
                                $image['highResolution']
                            );
                        }
                    }
                    continue;
                }

                // Representation is a 3d model
                if (in_array($type, $modelTypeKeys)) {
                    if ($model = $this->getModel($url, $format, $type)) {
                        $modelUrls[] = $model;
                    }
                    continue;
                }
                // Representation is an audio
                if (in_array($type, $audioTypeKeys)) {
                    if ($audio = $this->getAudio($url, $format, $description)) {
                        $audioUrls = array_merge($audioUrls, $audio);
                    }
                    continue;
                }
                // Representation is a video
                if (in_array($type, $videoTypeKeys)) {
                    if ($video = $this->getVideo($url, $format, $description)) {
                        $videoUrls = array_merge($videoUrls, $video);
                    }
                    continue;
                }
                // Representation is a document
                if (in_array($type, $documentTypeKeys)) {
                    if ($document = $this->getDocument($url, $format, $description)) {
                        $documentUrls = array_merge($documentUrls, $document);
                    }
                }
            }
            // Save all the found results here as a new object
            // If current set has no links, continue to next one
            if (!$imageUrls && !$modelUrls && !$audioUrls && !$videoUrls && !$documentUrls) {
                continue;
            }
            $imageResult = [];
            // Process found image urls
            if ($imageUrls) {
                $imageResult = [
                    'urls' => $imageUrls,
                    'description' => '',
                    'rights' => $rights,
                    'highResolution' => $highResolution,
                ];

                if ($extraDetails = $this->getExtraDetails($resourceSet, $language)) {
                    $imageResult = array_merge($imageResult, $extraDetails);
                }

                // Trim resulting strings
                array_walk_recursive(
                    $imageResult,
                    function (&$current) {
                        if (is_string($current)) {
                            $current = trim($current);
                        }
                    }
                );
            }
            $modelResult = [];
            if ($modelUrls) {
                $modelResult = [
                    'models' => $modelUrls,
                    'rights' => $rights,
                ];
            }
            $addToResults(
                $imageResult,
                $modelResult,
                $audioUrls,
                $videoUrls,
                $documentUrls
            );
        }

        $this->cache[$cacheKey] = $results;
        return $results;
    }

    /**
     * Return description as associative array
     * - type Type of the description and text as the value
     *
     * @param SimpleXmlElement $resourceSet To get description from
     * @param string           $language    Language to get
     *
     * @return array
     */
    protected function getResourceDescriptions(
        \SimpleXmlElement $resourceSet,
        string $language
    ): array {
        $results = [];
        foreach ($resourceSet->resourceDescription as $description) {
            $text = trim((string)$description);
            if ($type = (string)$description['type']) {
                if ($lang = (string)$description['lang']) {
                    $results[$type][$lang] = $description;
                } else {
                    $results[$type][] = $description;
                }
            }
        }
        return $results;
    }

    /**
     * Returns associative array for images extra details
     * - identifier    resourceset id
     * - type          language specific type
     * - relationTypes language specific relation types
     * - description   language specific description
     * - dateTaken     date taken
     * - perspectives  language specific perspectives
     *
     * @param SimpleXmlElement $resourceSet Current resource set
     * @param string           $language    Language to information
     *
     * @return array
     */
    protected function getExtraDetails(
        \SimpleXmlElement $resourceSet,
        string $language
    ): array {
        $result = [];
        if (!empty($resourceSet->resourceID)) {
            $result['identifier'] = (string)$resourceSet->resourceID;
        }
        if (!empty($resourceSet->resourceType->term)) {
            $result['type'] = (string)$this->getLanguageSpecificItem(
                $resourceSet->resourceType->term,
                $language
            );
        }
        foreach ($resourceSet->resourceRelType ?? [] as $relType) {
            if (!empty($relType->term)) {
                $result['relationTypes'][]
                    = (string)$this->getLanguageSpecificItem(
                        $relType->term,
                        $language
                    );
            }
        }
        if (!empty($resourceSet->resourceDescription)) {
            $description
                = $this->getLanguageSpecificItem(
                    $resourceSet->resourceDescription,
                    $language
                );
            if ($descriptionTrimmed = trim((string)$description)) {
                $type = trim((string)$description->attributes()->type);
                if ($type === 'displayLink') {
                    $result['resourceName'] = $descriptionTrimmed;
                } else {
                    $result['resourceDescription'] = $descriptionTrimmed;
                }
            }
        }
        if (!empty($resourceSet->resourceDateTaken->displayDate)) {
            $result['dateTaken']
                = (string)$resourceSet->resourceDateTaken->displayDate;
        }
        foreach ($resourceSet->resourcePerspective ?? [] as $perspective) {
            if (!empty($perspective->term)) {
                $result['perspectives'][]
                    = (string)$this->getLanguageSpecificItem(
                        $perspective->term,
                        $language
                    );
            }
        }
        return $result;
    }

    /**
     * Function to return model as associative array
     * - format Model format as key
     *   - type Model type preview_3d or provided_3d as key
     *          url to model as value
     *
     * @param string $url    Model url
     * @param string $format Model format
     * @param string $type   Model type
     *
     * @return array
     */
    protected function getModel(
        string $url,
        string $format,
        string $type,
    ): array {
        $type = $this->modelTypes[$type];
        $format = strtolower($format);
        if ('preview' === $type && !in_array($format, $this->displayableModelFormats)) {
            // If we can not display the file, then tag it as a provided 3D model
            $type = 'provided';
        }
        return [
            'url' => $url,
            'format' => $format,
            'type' => $type,
        ];
    }

    /**
     * Function to return image in associative array
     * - displayImage
     *  - small                 Image size with url as value
     *  - medium                Image size with url as value
     *  - large                 Image size with url as value
     * - highResolution
     *  - size                  Image size master or original
     *      - format            Image format as key
     *          - data          Contains data like measurements
     *          - resourceID    ID to which resource belongs to
     *          - url           Url of the image
     *
     * @param string            $url          Url of the resourceset
     * @param string            $type         Type of the image
     * @param string            $language     Language to get information
     * @param string            $id           ID of the resourceset
     * @param string            $format       Format of the image
     * @param \SimpleXmlElement $measurements Measurements SimpleXmlElement
     *
     * @return array
     */
    protected function getImage(
        string $url,
        string $type,
        string $language,
        string $id = '',
        string $format = '',
        \SimpleXmlElement $measurements = null
    ): array {
        // Check if the image is really an image
        // Original images can be any type and are not displayed
        if ('image_original' !== $type && $this->isUndisplayableFormat($format)) {
            return [];
        }

        $size = $this->imageTypes[$type];
        $displayImage = [];
        if ($size !== 'original') {
            $displayImage[$size] = $url;
        }
        $highResolution = [];
        if (in_array($size, ['master', 'original'])) {
            $currentHiRes = [
                'data' => $this->formatImageMeasurements(
                    $measurements
                ),
                'url' => $url,
                'format' => $format ?: 'jpg',
            ];
            if ($id) {
                $currentHiRes['resourceID'] = $id;
            }
            $highResolution[$size][] = $currentHiRes;
        }

        return compact('displayImage', 'highResolution');
    }

    /**
     * Function to return an audio in associative array
     * - desc   Default is false
     * - url    Url to audio file
     * - codec  Codec type of the audio
     * - type   Type what type is the audio file
     * - embed  Type of embed is audio
     *
     * @param string $url         Url of the audio
     * @param string $format      Format of the audio
     * @param string $description Description of the audio
     *
     * @return array
     */
    protected function getAudio(
        string $url,
        string $format,
        string $description
    ): array {
        if ($codec = $this->supportedAudioFormats[$format] ?? false) {
            return [
                'desc' => $description ?: false,
                'url' => $url,
                'codec' => $format,
                'type' => 'audio',
                'embed' => 'audio',
            ];
        }
        return [];
    }

    /**
     * Function to return a video in associative array
     * - desc           Default is false
     * - url            Video url
     * - embed          Video embed is video
     * - videosources
     *  - src           Different sources for the video
     *  - type          Codec type
     *
     * @param string $url         Url of the video
     * @param string $format      Format of the video
     * @param string $description Description of the video
     *
     * @return array
     */
    protected function getVideo(
        string $url,
        string $format,
        string $description
    ): array {
        if ($codec = $this->supportedVideoFormats[$format] ?? false) {
            return [
                'desc' => $description ?: false,
                'url' => $url,
                'embed' => 'video',
                'format' => $format,
                'videoSources' => [
                    'src' => $url,
                    'type' => $codec,
                ],
            ];
        }
        return [];
    }

    /**
     * Function to return document in associative array
     *
     * @param string $url         Url of the document
     * @param string $format      Format of the document
     * @param string $description Description of the document
     *
     * @return array
     */
    protected function getDocument(
        string $url,
        string $format,
        string $description
    ): array {
        return [
            'description' => $description ?: false,
            'url' => $url,
            'format' => strtolower($format),
        ];
    }

    /**
     * Get rights from the given resourceSet
     *
     * @param \SimpleXmlElement $resourceSet Given resourceSet from lido
     * @param string            $language    Language to look for
     *
     * @return array
     */
    protected function getResourceRights(
        \SimpleXmlElement $resourceSet,
        string $language
    ): array {
        $defaultRights = $this->getImageRights($language, true);
        $rights = [];
        foreach ($resourceSet->rightsResource ?? [] as $rightsResource) {
            if (!empty($rightsResource->rightsType->conceptID)) {
                $conceptID = $rightsResource->rightsType->conceptID;
                $type = strtolower((string)$conceptID->attributes()->type);
                if ($type === 'copyright' && trim((string)$conceptID)) {
                    $rights['copyright']
                        = $this->getMappedRights((string)$conceptID);
                    $link
                        = $this->getRightsLink($rights['copyright'], $language);
                    if ($link) {
                        $rights['link'] = $link;
                    }
                }
            }

            foreach ($rightsResource->rightsHolder ?? [] as $holder) {
                if (empty($holder->legalBodyName->appellationValue)) {
                    continue;
                }
                $rightsHolder = [
                    'name' => (string)$holder->legalBodyName->appellationValue,
                ];

                if (!empty($holder->legalBodyWeblink)) {
                    $rightsHolder['link']
                        = (string)$holder->legalBodyWeblink;
                }
                $rights['rightsHolders'][] = $rightsHolder;
            }

            if (!empty($rightsResource->creditLine)) {
                $rights['creditLine'] = (string)$this->getLanguageSpecificItem(
                    $rightsResource->creditLine,
                    $language
                );
            }
        }

        if (!empty($resourceSet->rightsResource->rightsType->term)) {
            $term = (string)$this->getLanguageSpecificItem(
                $resourceSet->rightsResource->rightsType->term,
                $language
            );
            if (!isset($rights['copyright']) || $rights['copyright'] !== $term) {
                $rights['description'][] = $term;
            }
        }

        if (!is_array($defaultRights)) {
            $defaultRights = [];
        }

        return $rights ?: $defaultRights;
    }

    /**
     * Return model settings from config
     *
     * @return array settings
     */
    public function getModelSettings(): array
    {
        $settings = [];
        if ($iniData = $this->recordConfig->Models ?? []) {
            $settings = [
                'debug' => boolval($iniData->debug ?? 0),
                'previewImages' => $this->allowModelPreviewImages(),
            ];
        }

        return $settings;
    }

    /**
     * Can model preview images be shown
     *
     * @return bool
     */
    public function allowModelPreviewImages(): bool
    {
        $datasource = $this->getDataSource();
        return !empty($this->mainConfig->Models->previewImages[$datasource]);
    }

    /**
     * Get an array of alternative titles for the record.
     *
     * @return array
     */
    public function getAlternativeTitles()
    {
        return $this->fields['title_alt'] ?? [];
    }

    /**
     * Get an array of related publications for the record.
     *
     * @return array
     */
    public function getRelatedPublications()
    {
        $results = [];
        $publicationTypes = ['kirjallisuus', 'lähteet', 'julkaisu'];
        $xpath = 'lido/descriptiveMetadata/objectRelationWrap/relatedWorksWrap/'
            . 'relatedWorkSet';
        foreach ($this->getXmlRecord()->xpath($xpath) as $node) {
            if (!empty($node->relatedWork->displayObject)) {
                $title = trim((string)$node->relatedWork->displayObject);
                $attributes = $node->relatedWork->displayObject->attributes();
                $label = !empty($attributes->label)
                    ? (string)$attributes->label : '';
                $term = !empty($node->relatedWorkRelType->term)
                    ? (string)$node->relatedWorkRelType->term : '';
                $termLC = mb_strtolower($term, 'UTF-8');
                if ($title && in_array($termLC, $publicationTypes)) {
                    $term = $termLC != 'julkaisu' ? $term : '';
                    $results[] = [
                      'title' => $title,
                      'label' => $label ?: $term,
                      'url' => '',
                    ];
                }
            }
        }
        return $results;
    }

    /**
     * Get an array of classifications for the record.
     *
     * @return array
     */
    public function getOtherClassifications()
    {
        $preferredLanguages = $this->getPreferredLanguageCodes();
        $preferredLangResults = $allResults = [];
        $xpath = 'lido/descriptiveMetadata/objectClassificationWrap/classificationWrap/'
            . 'classification';
        foreach ($this->getXmlRecord()->xpath($xpath) as $node) {
            $type = trim((string)$node->attributes()->type);
            if (in_array($type, $this->excludedClassifications)) {
                continue;
            }
            if (isset($node->term)) {
                $term = trim((string)$node->term);
                if ('' !== $term) {
                    $attributes = $node->term->attributes();
                    $label = (string)($attributes->label ?? '');
                    $data = $label ? compact('term', 'label') : $term;
                    $allResults[] = $data;
                    $termLanguage = trim((string)$attributes->lang)
                        ?: trim((string)$node->attributes()->lang);
                    if (in_array($termLanguage, $preferredLanguages)) {
                        $preferredLangResults[] = $data;
                    }
                }
            }
        }
        return $preferredLangResults ?: $allResults;
    }

    /**
     * Get the collections of the current record.
     *
     * @return array
     */
    public function getCollections()
    {
        $results = [];
        $allowedTypes = ['Kokoelma', 'kuuluu kokoelmaan', 'kokoelma', 'Alakokoelma',
            'Erityiskokoelma'];
        $xpath = 'lido/descriptiveMetadata/objectRelationWrap/relatedWorksWrap/'
            . 'relatedWorkSet';
        foreach ($this->getXmlRecord()->xpath($xpath) as $node) {
            $term = $node->relatedWorkRelType->term ?? '';
            $collection = trim((string)$node->relatedWork->displayObject ?? '');
            if ($collection && in_array($term, $allowedTypes)) {
                $results[] = $collection;
            }
        }
        return $results;
    }

    /**
     * Get an array of events for the record.
     *
     * @return array
     */
    public function getEvents()
    {
        $events = [];
        $language = $this->getLocale();
        foreach (
            $this->getXmlRecord()->xpath('/lidoWrap/lido/descriptiveMetadata/eventWrap/eventSet/event') as $node
        ) {
            $name = (string)($node->eventName->appellationValue ?? '');
            $type = isset($node->eventType->term)
                ? mb_strtolower((string)$node->eventType->term, 'UTF-8') : '';
            if (!empty($node->eventDate->displayDate)) {
                $date = (string)($this->getLanguageSpecificItem(
                    $node->eventDate->displayDate,
                    $language
                ));
            } else {
                $date = '';
            }
            if (!$date && !empty($node->eventDate->date)) {
                $startDate
                    = trim((string)($node->eventDate->date->earliestDate ?? ''));
                $endDate = trim((string)($node->eventDate->date->latestDate ?? ''));
                if (strlen($startDate) == 4 && strlen($endDate) == 4) {
                    $date = "$startDate-$endDate";
                } else {
                    $startDateType = 'Y-m-d';
                    $endDateType = 'Y-m-d';
                    if (strlen($startDate) == 7) {
                        $startDateType = 'Y-m';
                    }
                    if (strlen($endDate) == 7) {
                        $endDateType = 'Y-m';
                    }

                    $date = $this->dateConverter && $startDate
                        ? $this->dateConverter->convertToDisplayDate(
                            $startDateType,
                            $startDate
                        )
                        : $startDate;

                    if ($endDate && $startDate != $endDate) {
                        $date .= '-' . ($this->dateConverter
                            ? $this->dateConverter->convertToDisplayDate(
                                $endDateType,
                                $endDate
                            )
                            : $endDate);
                    }
                }
            }
            if ($type == 'valmistus') {
                $confParam = 'lido_augment_display_date_with_period';
                if ($this->getDataSourceConfigurationValue($confParam)) {
                    if ($period = ($node->periodName->term ?? '')) {
                        if ($date) {
                            $date = $period . ', ' . $date;
                        } else {
                            $date = $period;
                        }
                    }
                }
            }
            $methods = [];
            foreach ($node->eventMethod ?? [] as $eventMethod) {
                foreach ($eventMethod->term ?? [] as $term) {
                    if ($method = trim((string)$term)) {
                        $methods[] = $method;
                    }
                }
            }
            $materials = [];

            if (isset($node->eventMaterialsTech->displayMaterialsTech)) {
                // Use displayMaterialTech (default)
                $materials[] = (string)$node->eventMaterialsTech
                    ->displayMaterialsTech;
            } elseif (isset($node->eventMaterialsTech->materialsTech)) {
                // display label not defined, build from materialsTech
                $materials = [];
                foreach ($node->xpath('eventMaterialsTech/materialsTech') as $materialsTech) {
                    if ($terms = $materialsTech->xpath('termMaterialsTech/term')) {
                        foreach ($terms as $term) {
                            $label = null;
                            $attributes = $term->attributes();
                            if (isset($attributes->label)) {
                                // Musketti
                                $label = $attributes->label;
                            } elseif (isset($materialsTech->extentMaterialsTech)) {
                                // Siiri
                                $label = $materialsTech->extentMaterialsTech;
                            }
                            if ($label) {
                                $term = "$term ($label)";
                            }
                            $materials[] = $term;
                        }
                    }
                }
            }

            $places = [];
            foreach ($node->eventPlace ?? [] as $placenode) {
                $place = trim((string)$placenode->displayPlace ?? '');
                if (!$place) {
                    $eventPlace = [];
                    foreach ($placenode->place->namePlaceSet ?? [] as $nameSet) {
                        $value = trim((string)$nameSet->appellationValue ?? '');
                        if ($value) {
                            $eventPlace[] = $value;
                        }
                    }
                    if ($eventPlace) {
                        $places[] = implode(', ', $eventPlace);
                    }
                    foreach ($placenode->place->partOfPlace ?? [] as $part) {
                        $partOfPlaceName = [];
                        while ($part->namePlaceSet ?? false) {
                            $appellationValue = trim(
                                (string)$part->namePlaceSet->appellationValue ?? ''
                            );
                            if ($appellationValue) {
                                $partOfPlaceName[] = $appellationValue;
                            }
                            $part = $part->partOfPlace;
                        }
                        if ($partOfPlaceName) {
                            $places[] = implode(', ', $partOfPlaceName);
                        }
                    }
                } else {
                    $places[] = $place;
                }
            }
            $actors = [];
            if (isset($node->eventActor)) {
                foreach ($node->eventActor as $actor) {
                    $appellationValue = trim(
                        $actor->actorInRole->actor->nameActorSet->appellationValue
                        ?? ''
                    );
                    if ($appellationValue !== '') {
                        $role = (string)($actor->actorInRole->roleActor->term ?? '');
                        $earliestDate = (string)($actor->actorInRole->actor
                            ->vitalDatesActor->earliestDate ?? '');
                        $latestDate = (string)($actor->actorInRole->actor
                            ->vitalDatesActor->latestDate ?? '');
                        $actors[] = [
                            'name' => $appellationValue,
                            'role' => $role,
                            'birth' => $earliestDate,
                            'death' => $latestDate,
                        ];
                    }
                }
            }
            $culture = (string)($node->culture->term ?? '');
            $descriptions = [];
            foreach ($node->eventDescriptionSet ?? [] as $set) {
                if ($note = trim((string)($set->descriptiveNoteValue ?? ''))) {
                    $descriptions[] = $note;
                }
            }

            $event = [
                'type' => $type,
                'name' => $name,
                'date' => $date,
                'methods' => $methods,
                'materials' => $materials,
                'places' => $places,
                'actors' => $actors,
                'culture' => $culture,
                'descriptions' => $descriptions,
                // For backward compatibility
                'description' => $descriptions[0] ?? '',
            ];
            // Only add the event if it has content
            foreach ($event as $key => $field) {
                if ('type' !== $key && !empty($field)) {
                    $events[$type][] = $event;
                    break;
                }
            }
        }
        return $events;
    }

    /**
     * Get an array of format classifications for the record.
     *
     * @return array
     */
    public function getFormatClassifications()
    {
        $results = [];
        foreach (
            $this->getXmlRecord()->lido->descriptiveMetadata
            ->objectClassificationWrap ?? [] as $node
        ) {
            $workTypeTerm = trim(
                (string)($node->objectWorkTypeWrap->objectWorkType->term ?? '')
            );
            foreach ($node->classificationWrap->classification ?? [] as $classification) {
                $type = trim((string)$classification->attributes()->type);
                if (in_array($type, $this->excludedClassifications)) {
                    continue;
                }
                $getDisplayString = function (string $term, string $extra) {
                    return $extra ? "$term ($extra)" : $term;
                };
                foreach ($classification->term as $term) {
                    $termString = trim((string)$term);
                    $termType = trim((string)$term->attributes()->type);
                    $termLabel = trim((string)$term->attributes()->label);

                    switch ($workTypeTerm) {
                        case 'rakennetun ympäristön kohde':
                            $results[] = $getDisplayString($termString, $termType);
                            break 2;
                        case 'arkeologinen kohde':
                            $results[] = $getDisplayString($termString, $termLabel);
                            break;
                    }
                }
            }
        }
        return $results;
    }

    /**
     * Get identifier
     *
     * @return array
     */
    public function getIdentifier()
    {
        foreach ($this->getIdentifiersByType(true) as $identifier) {
            // Take only the first identifier here
            // since others are included in getLocalIdentifiers
            return [$identifier];
        }
        return [];
    }

    /**
     * Return image rights.
     *
     * @param string $language       Language
     * @param bool   $skipImageCheck Whether to check that images exist
     *
     * @return mixed array with keys:
     *   'copyright'  Copyright (e.g. 'CC BY 4.0') (optional)
     *   'description Human readable description (array)
     *   'link'       Link to copyright info
     *   or false if the record contains no images
     */
    public function getImageRights($language, $skipImageCheck = false)
    {
        if (!$skipImageCheck && !$this->getAllImages()) {
            return false;
        }

        $rights = [];

        if ($type = $this->getAccessRestrictionsType($language)) {
            $rights['copyright'] = $type['copyright'];
            if (isset($type['link'])) {
                $rights['link'] = $type['link'];
            }
        }

        $desc = $this->getAccessRestrictions($language);
        if ($desc && count($desc)) {
            $description = [];
            foreach ($desc as $p) {
                $description[] = (string)$p;
            }
            $rights['description'] = $description;
        }

        return !empty($rights['copyright']) || !empty($rights['description'])
            ? $rights : false
        ;
    }

    /**
     * Get an array of inscriptions for the record.
     *
     * @return array
     */
    public function getInscriptions()
    {
        $results = [];
        $xpath = 'lido/descriptiveMetadata/objectIdentificationWrap/inscriptionsWrap/'
            . 'inscriptions';
        foreach ($this->getXmlRecord()->xpath($xpath) as $inscriptions) {
            $group = [];
            foreach ($inscriptions->inscriptionDescription as $node) {
                $content = trim((string)$node->descriptiveNoteValue ?? '');
                $type = $node->attributes()->type ?? '';
                $label = $node->descriptiveNoteValue->attributes()->label ?? '';
                if ($content) {
                    $group[] = compact('type', 'label', 'content');
                }
            }
            if ($group) {
                $results[] = $group;
            }
        }
        return $results;
    }

    /**
     * Get an array of local identifiers for the record.
     *
     * @return array
     */
    public function getLocalIdentifiers()
    {
        $results = [];
        $identifiers = $this->getIdentifiersByType(true, [], ['isbn', 'issn']);
        $primaryID = $this->getIdentifier();
        foreach ($identifiers as $identifier) {
            if (!in_array($identifier, $primaryID)) {
                $results[] = $identifier;
            }
        }
        return $results;
    }

    /**
     * Get an array of ISBNs for the record.
     *
     * @return array
     */
    public function getISBNs()
    {
        return $this->getIdentifiersByType(false, ['isbn']);
    }

    /**
     * Get an array of ISBNs for the record.
     *
     * @return array
     */
    public function getISSNs()
    {
        return $this->getIdentifiersByType(false, ['issn']);
    }

    /**
     * Get the main format.
     *
     * @return array
     */
    public function getMainFormat()
    {
        if (!isset($this->fields['format'])) {
            return 'Other';
        }
        $formats = $this->fields['format'];
        $format = reset($formats);
        $format = preg_replace('/^\d+\/([^\/]+)\/.*/', '\1', $format);
        return $format;
    }

    /**
     * Get measurements.
     *
     * @return array
     */
    public function getMeasurements(): array
    {
        return $this->getMeasurementsByType();
    }

    /**
     * Get extent.
     *
     * @return array
     */
    public function getPhysicalDescriptions(): array
    {
        return $this->getMeasurementsByType(['extent']);
    }

    /**
     * Get measurements by type.
     *
     * @param array $include Measurement types to include, otherwise all but
     * excluded types
     *
     * @return array
     */
    public function getMeasurementsByType(array $include = []): array
    {
        $results = [];
        $exclude = $include ? [] : $this->excludedMeasurements;
        foreach (
            $this->getXmlRecord()->lido->descriptiveMetadata
            ->objectIdentificationWrap->objectMeasurementsWrap
            ->objectMeasurementsSet ?? [] as $set
        ) {
            $setExtents = [];
            foreach ($set->objectMeasurements->extentMeasurements ?? [] as $extent) {
                if ($value = trim((string)$extent)) {
                    $setExtents[] = $value;
                }
            }
            $setExtents = implode(', ', $setExtents);
            // Use allowed display elements
            $displayFound = false;
            foreach ($set->displayObjectMeasurements as $measurements) {
                if ($value = trim((string)$measurements)) {
                    $displayFound = true;
                    $label = $measurements->attributes()->label ?? '';
                    if (
                        ($include && !in_array($label, $include))
                        || ($exclude && in_array($label, $exclude))
                    ) {
                        continue;
                    }
                    if ($setExtents) {
                        $value .= " ($setExtents)";
                    }
                    $results[] = $value;
                }
            }
            // Use measurementsSet only if no display elements exist
            if (!$displayFound) {
                foreach ($set->objectMeasurements->measurementsSet ?? [] as $measurements) {
                    $type = trim(
                        (string)($measurements->measurementType->term ?? '')
                    );
                    if (
                        ($include && !in_array($type, $include))
                        || ($exclude && in_array($type, $exclude))
                    ) {
                        continue;
                    }
                    $parts = [];
                    if ($type = trim((string)($measurements->measurementType ?? ''))) {
                        $parts[] = $type;
                    }
                    if ($val = trim((string)($measurements->measurementValue ?? ''))) {
                        $parts[] = $val;
                    }
                    if ($unit = trim((string)($measurements->measurementUnit ?? ''))) {
                        $parts[] = $unit;
                    }
                    if ($parts) {
                        if ($setExtents) {
                            $parts[] = "($setExtents)";
                        }
                        $results[] = implode(' ', $parts);
                    }
                }
            }
        }
        return $results;
    }

    /**
     * Get all authors apart from presenters
     *
     * @return array
     */
    public function getNonPresenterAuthors()
    {
        $authors = [];
        $index = 0;
        foreach ($this->getXmlRecord()->lido->descriptiveMetadata->eventWrap->eventSet ?? [] as $set) {
            if (!($event = $set->event ?? '')) {
                continue;
            }
            $eventType = (string)($event->eventType->term ?? '');
            $priority = $this->authorEvents[$eventType] ?? null;
            if (null === $priority) {
                continue;
            }
            foreach ($event->eventActor ?? [] as $actor) {
                $name
                    = trim(
                        (string)($actor->actorInRole->actor->nameActorSet
                            ->appellationValue
                        ?? '')
                    );
                if ($name) {
                    $role = (string)($actor->actorInRole->roleActor->term ?? '');
                    $key = $priority * 1000 + $index++;
                    $authors[$key] = compact(
                        'name',
                        'role'
                    );
                }
            }
        }
        ksort($authors, SORT_NUMERIC);
        return array_values($authors);
    }

    /**
     * Get hierarchy parent archives
     *
     * @return array
     */
    public function getParentArchives()
    {
        return $this->getParentLinksByType('archive');
    }

    /**
     * Get hierarchy parent collections
     *
     * @return array
     */
    public function getParentCollections()
    {
        return $this->getParentLinksByType('collection');
    }

    /**
     * Get hierarchy parent subcollections
     *
     * @return array
     */
    public function getParentSubcollections()
    {
        return $this->getParentLinksByType('subcollection');
    }

    /**
     * Get hierarchy parent series
     *
     * @return array
     */
    public function getParentSeries()
    {
        return $this->getParentLinksByType('series');
    }

    /**
     * Get hierarchy parent purchase batches
     *
     * @return array
     *
     * @deprecated No longer used
     */
    public function getParentPurchaseBatches()
    {
        return [];
    }

    /**
     * Get hierarchy parent unclassified entities
     *
     * Returns entities not belonging to any of the separately handled classes.
     *
     * @return array
     */
    public function getParentUnclassifiedEntities()
    {
        return $this->getParentLinksByType('');
    }

    /**
     * Get hierarchy parent works
     *
     * @return array
     */
    public function getParentWorks()
    {
        return $this->getParentLinksByType('work');
    }

    /**
     * Get an array of dates for results list display
     *
     * @return ?array Array of one or two dates or null if not available.
     * If date range is still continuing end year will be an empty string.
     */
    public function getResultDateRange()
    {
        return $this->getDateRange('creation');
    }

    /**
     * Get subject actors
     *
     * @return array
     */
    public function getSubjectActors()
    {
        $results = [];
        $xpath = 'lido/descriptiveMetadata/objectRelationWrap/subjectWrap/'
            . 'subjectSet/subject/subjectActor/actor/nameActorSet/appellationValue';
        foreach ($this->getXmlRecord()->xpath($xpath) as $node) {
            if ($actor = trim((string)$node)) {
                $results[] = $actor;
            }
        }
        return $results;
    }

    /**
     * Get subject dates
     *
     * @return array
     */
    public function getSubjectDates()
    {
        $results = [];
        $language = $this->getLocale();
        $xpath = 'lido/descriptiveMetadata/objectRelationWrap/subjectWrap/'
            . 'subjectSet/subject';
        foreach ($this->getXmlRecord()->xpath($xpath) as $node) {
            if (!empty($node->subjectDate->displayDate)) {
                $term = (string)($this->getLanguageSpecificItem(
                    $node->subjectDate->displayDate,
                    $language
                ));
                if ($term) {
                    $results[] = $term;
                }
            }
        }
        return $results;
    }

    /**
     * Get subject details
     *
     * @return array
     */
    public function getSubjectDetails()
    {
        $results = [];
        $xpath = 'lido/descriptiveMetadata/objectIdentificationWrap/titleWrap/titleSet/'
            . "appellationValue[@label='aiheen tarkenne']";
        foreach ($this->getXmlRecord()->xpath($xpath) as $node) {
            $results[] = (string)$node;
        }
        return $results;
    }

    /**
     * Get all subject headings associated with this record apart from geographic
     * places.  Each heading is returned as an array of chunks, increasing from least
     * specific to most specific.
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
    public function getAllSubjectHeadingsWithoutPlaces(bool $extended = false): array
    {
        $headings = [];
        $language = $this->getLocale();
        foreach (['topic', 'genre'] as $field) {
            if (isset($this->fields[$field])) {
                $headings = array_merge($headings, (array)$this->fields[$field]);
            }
        }
        // Include all display dates from events except creation date
        foreach ($this->getXmlRecord()->lido->descriptiveMetadata->eventWrap->eventSet ?? [] as $node) {
            $type = isset($node->event->eventType->term)
                ? mb_strtolower((string)$node->event->eventType->term, 'UTF-8') : '';
            if ($type !== 'valmistus') {
                $displayDate = $node->event->eventDate->displayDate;
                if (!empty($displayDate)) {
                    $date = (string)($this->getLanguageSpecificItem(
                        $displayDate,
                        $language
                    ));
                    $headings[] = $date;
                }
            }
        }

        // The default index schema doesn't currently store subject headings in a
        // broken-down format, so we'll just send each value as a single chunk.
        // Other record drivers (i.e. SolrMarc) can offer this data in a more
        // granular format.
        $callback = function ($i) use ($extended) {
            return $extended
                ? ['heading' => [$i], 'type' => '', 'source' => '']
                : [$i];
        };
        return array_map($callback, array_unique($headings));
    }

    /**
     * Get subject places
     *
     * @param bool $extended Whether to return a keyed array with the following
     * keys:
     * - heading: the actual subject heading chunks
     * - type: heading type
     * - detail: addition details
     * - source: source vocabulary
     * - id: authority id (if defined)
     * - ids: multiple authority ids (if defined)
     * - authType: authority type (if id is defined)
     *
     * @return array
     */
    public function getSubjectPlaces(bool $extended = false)
    {
        $results = [];
        $xpath = 'lido/descriptiveMetadata/objectRelationWrap/subjectWrap/'
            . 'subjectSet/subject/subjectPlace';
        foreach ($this->getXmlRecord()->xpath($xpath) as $subjectPlace) {
            if (!($displayPlace = (string)($subjectPlace->displayPlace ?? ''))) {
                continue;
            }
            if ($extended) {
                $place = [
                    'heading' => [$displayPlace],
                ];
                // Collect all ids but use only the first for type etc:
                $details = [];
                foreach ($subjectPlace->place->placeID ?? [] as $placeId) {
                    $id = (string)$placeId;
                    $type = (string)($placeId->attributes()->type ?? '');
                    if ($type) {
                        $id = "($type)$id";
                    }
                    $typeDesc = $this->translate('place_id_type_' . $type, [], '');
                    if ($typeDesc) {
                        $details[] = $typeDesc;
                    }
                    if (isset($place['type'])) {
                        $place['ids'][] = $id;
                        continue;
                    }
                    $place['type'] = $type;
                    $place['id'] = $id;
                    $place['ids'][] = $id;
                }
                if ($details) {
                    $place['detail'] = implode(', ', $details);
                }
                $results[] = $place;
            } else {
                $results[] = $displayPlace;
            }
        }
        return $results;
    }

    /**
     * Get extended subject places
     *
     * @return array
     */
    public function getSubjectPlacesExtended(): array
    {
        return $this->getSubjectPlaces(true);
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
        $urls = [];
        foreach (parent::getURLs() as $url) {
            if (!$this->urlBlocked($url['url'] ?? '', $url['desc'] ?? '')) {
                $urls[] = $url;
            }
        }
        $urls = $this->resolveUrlTypes($urls);
        $urls = array_merge($urls, $this->getAudios(), $this->getVideos());
        return $urls;
    }

    /**
     * Get the web resource links from the record.
     *
     * @return array
     */
    public function getWebResources(): array
    {
        $relatedWorks = $this->getXmlRecord()->xpath(
            'lido/descriptiveMetadata/objectRelationWrap/relatedWorksWrap/'
            . 'relatedWorkSet/relatedWork'
        );
        $data = [];
        $language = $this->getLocale();
        foreach ($relatedWorks as $work) {
            if (!empty($work->object->objectWebResource)) {
                $tmp = [];
                $url = trim(
                    (string)$this->getLanguageSpecificItem(
                        $work->object->objectWebResource,
                        $language
                    )
                );
                if ($this->urlBlocked($url)) {
                    continue;
                }
                $tmp['url'] = $url;
                if (!empty($work->displayObject)) {
                    $tmp['desc'] = trim(
                        (string)$this->getLanguageSpecificItem(
                            $work->displayObject,
                            $language
                        )
                    );
                }
                if (!empty($work->object->objectID)) {
                    $tmp['info'] = trim((string)$work->object->objectID);
                    $objectAttrs = $work->object->objectID->attributes();
                    if (!empty($objectAttrs->label)) {
                        $tmp['label'] = trim(
                            (string)$this->getLanguageSpecificItem(
                                $objectAttrs->label,
                                $language
                            )
                        );
                    }
                }
                $data[] = $tmp;
            }
        }
        return $data;
    }

    /**
     * Set raw data to initialize the object.
     *
     * @param mixed $data Raw data representing the record; Record Model
     * objects are normally constructed by Record Driver objects using data
     * passed in from a Search Results object.  The exact nature of the data may
     * vary depending on the data source -- the important thing is that the
     * Record Driver + Search Results objects work together correctly.
     *
     * @return void
     */
    public function setRawData($data)
    {
        parent::setRawData($data);
    }

    /**
     * Is social media sharing allowed
     *
     * @return boolean
     */
    public function socialMediaSharingAllowed()
    {
        $rights = $this->getXmlRecord()->xpath(
            'lido/administrativeMetadata/resourceWrap/resourceSet/rightsResource/'
            . 'rightsType/conceptID[@type="Social media links"]'
        );
        return empty($rights) || (string)$rights[0] != 'no';
    }

    /**
     * Does a record come from a source that has given data
     * source specific configuration set as true?
     *
     * @param string $confParam string of configuration parameter name
     *
     * @return bool
     */
    protected function getDataSourceConfigurationValue($confParam)
    {
        $datasource = $this->getDataSource();
        return isset($this->recordConfig->$confParam)
            && isset($this->recordConfig->$confParam[$datasource])
            ? $this->recordConfig->$confParam[$datasource] : null;
    }

    /**
     * Get identifiers by type
     *
     * @param bool  $includeType Whether to include identifier type in parenthesis
     * @param array $include     Type attributes to include
     * @param array $exclude     Type attributes to exclude
     *
     * @return array
     */
    protected function getIdentifiersByType(
        bool $includeType,
        array $include = [],
        array $exclude = []
    ): array {
        $results = [];
        foreach (
            $this->getXmlRecord()->lido->descriptiveMetadata
            ->objectIdentificationWrap->repositoryWrap
            ->repositorySet ?? [] as $repository
        ) {
            foreach ($repository->workID ?? [] as $node) {
                $type = $node->attributes()->type ?? '';
                if (
                    ($include && !in_array($type, $include))
                    || ($exclude && in_array($type, $exclude))
                ) {
                    continue;
                }
                if ($identifier = trim((string)$node ?? '')) {
                    if ($type && $includeType) {
                        $identifier .= " ($type)";
                    }
                    $results[] = $identifier;
                }
            }
        }
        return $results;
    }

    /**
     * Get physical locations
     *
     * @return array
     */
    public function getPhysicalLocations(): array
    {
        $results = [];
        foreach (
            $this->getXmlRecord()->lido->descriptiveMetadata->objectIdentificationWrap
            ->repositoryWrap->repositorySet ?? [] as $repository
        ) {
            $type = (string)($repository->attributes()->type ?? '');
            if ($type !== 'Current location') {
                continue;
            }
            $locations = [];
            foreach ($repository->repositoryLocation->namePlaceSet ?? [] as $nameSet) {
                if ($name = trim((string)$nameSet->appellationValue ?? '')) {
                    $locations[] = $name;
                }
            }
            foreach ($repository->repositoryLocation->partOfPlace ?? [] as $part) {
                while ($part->namePlaceSet ?? false) {
                    if ($partName = trim((string)$part->namePlaceSet->appellationValue ?? '')) {
                        $locations[] = $partName;
                    }
                    $part = $part->partOfPlace;
                }
            }
            if ($locations) {
                $results[] = implode(', ', $locations);
            }
            $lang = $this->getLocale();
            if (
                $display = trim(
                    (string)($this->getLanguageSpecificItem($repository->displayRepository, $lang))
                    ?? ''
                )
            ) {
                $results[] = $display;
            }
        }
        return $results;
    }

    /**
     * Get a language-specific item from an element array
     *
     * @param SimpleXMLElement $element  Element to use
     * @param string           $language Language to look for
     *
     * @return SimpleXMLElement
     */
    protected function getLanguageSpecificItem($element, $language)
    {
        $languages = [];
        if ($language) {
            $languages[] = $language;
            // Add region-less language if needed:
            $parts = explode('-', $language, 2);
            if (isset($parts[1])) {
                $languages[] = $parts[0];
            }
        }
        foreach ($languages as $lng) {
            foreach ($element as $item) {
                $attrs = $item->attributes();
                if (!empty($attrs->lang) && (string)$attrs->lang == $lng) {
                    if ('' !== trim((string)$item)) {
                        return $item;
                    }
                }
            }
        }
        // Return first non-empty item if available
        foreach ($element as $item) {
            if ('' !== trim((string)$item)) {
                return $item;
            }
        }
        return $element;
    }

    /**
     * Get all fitting language-specific items from an element array
     *
     * @param array  $elements Array of elements to use
     * @param string $language Language to look for
     *
     * @return array
     */
    protected function getAllLanguageSpecificItems(
        array $elements,
        string $language
    ): array {
        $languages = [];
        $items = [];
        $allItems = [];
        if ($language) {
            $languages[] = $language;
            if (strlen($language) > 2) {
                $languages[] = substr($language, 0, 2);
            }
        }
        foreach ($elements as $item) {
            if ('' !== trim((string)$item)) {
                $allItems[] = $item;
                $attrs = $item->attributes();
                if (
                    !empty($attrs->lang)
                    && in_array((string)$attrs->lang, $languages)
                ) {
                    $items[] = $item;
                }
            }
        }
        // Return either language specific results or all given items.
        return $items ?: $allItems;
    }

    /**
     * Compare the title of current object with items from given array as titles
     *
     * @param array $compare An array of items to compare
     *
     * @return array
     */
    protected function compareWithTitle(array $compare): array
    {
        $compareDone = [];
        $title = str_replace([',', ';'], '', $this->getTitle());
        $compareFull = str_replace([',', ';'], '', implode(' ', $compare));
        if ($compareFull != $title) {
            foreach ($compare as $item) {
                $checkTitle = str_replace([',', ';'], ' ', (string)$item) != $title;
                if ($checkTitle) {
                    $compareDone[] = (string)$item;
                }
            }
        }
        return array_unique($compareDone);
    }

    /**
     * Get the displaysubject and description info to summary
     *
     * @return array $results with summary from displaySubject or description field
     */
    public function getSummary()
    {
        $collected = [];
        $descriptions = [];
        $descriptionsTyped = [];
        $descriptionsUntyped = [];
        $subjectsLabeled = [];
        $subjectsUnlabeled = [];
        $titleValues = [];
        $language = $this->getLocale();
        //Collect all fitting description objects
        foreach (
            $this->getXmlRecord()->lido->descriptiveMetadata
            ->objectIdentificationWrap->objectDescriptionWrap->objectDescriptionSet
            ?? [] as $node
        ) {
            $type = $node->attributes()->type;
            //Descriptions divided to typed and untyped
            foreach ($node->descriptiveNoteValue ?? [] as $desc) {
                if ($type == 'description') {
                    $descriptionsTyped[] = $desc;
                } elseif (empty($type)) {
                    $descriptionsUntyped[] = $desc;
                }
            }
        }
        //Collect all fitting subject objects
        foreach (
            $this->getXmlRecord()->lido->descriptiveMetadata
            ->objectRelationWrap->subjectWrap->subjectSet ?? [] as $node
        ) {
            foreach ($node->displaySubject ?? [] as $subj) {
                $label = $subj->attributes()->label;
                //Subjects divided to labeled and unlabeled
                if ($label == 'aihe') {
                    $subjectsLabeled[] = $subj;
                } elseif ($label == '') {
                    $subjectsUnlabeled[] = $subj;
                }
            }
        }
        //Check for matching language variations
        if ($descriptionsTyped ?: $descriptionsUntyped) {
            $collectedDesc = $this->getAllLanguageSpecificItems(
                $descriptionsTyped ?: $descriptionsUntyped,
                $language
            );
            //Discard values matching the object title
            $collected = $this->compareWithTitle($collectedDesc);
            foreach ($collected as $item) {
                $descriptions[] = (string)$item;
            }
        }
        if ($subjectsLabeled ?: $subjectsUnlabeled) {
            $collectedSubj = $this->getAllLanguageSpecificItems(
                $subjectsLabeled ?: $subjectsUnlabeled,
                $language
            );
            $collected = $this->compareWithTitle($collectedSubj);
            foreach ($collected as $item) {
                $descriptions[] = (string)$item;
            }
        }

        //Collect all titles to be checked
        $displayTitle = $this->getTitle();
        foreach (
            $this->getXmlRecord()->lido->descriptiveMetadata
            ->objectIdentificationWrap->titleWrap->titleSet
            ?? [] as $node
        ) {
            foreach ($node->appellationValue ?? [] as $title) {
                $pref = (string)($title->attributes()->pref ?? '');
                $titleValues[] = $title;
                if ($pref === 'preferred' || $pref === 'alternate') {
                    $titlesNotInDesc[] = $title;
                }
            }
        }
        //Get language specific titles
        if ($titleValues) {
            $titleValues = $this->getAllLanguageSpecificItems(
                $titleValues,
                $language
            );
            //Discard values matching the object title
            $titleValues = $this->compareWithTitle($titleValues);
            //Discard values matching the alternate titles
            if (isset($titlesNotInDesc)) {
                $titleValues = array_diff($titleValues, $titlesNotInDesc);
            }
            //Check for partial titles
            $checkWord = preg_replace('/[^a-zA-Z0-9]/', '', $displayTitle);
            $checkLength = strlen($checkWord);
            foreach ($titleValues as $title) {
                $checkItem = preg_replace('/[^a-zA-Z0-9]/', '', (string)$title);
                if (
                    strncmp($checkItem, $checkWord, $checkLength) == 0
                    && $checkLength !== strlen($checkItem)
                ) {
                    $title = ltrim(substr($title, strlen($displayTitle)), ' .,;:!?');
                    $collectedTitles[] = (string)$title;
                } elseif ($displayTitle !== $title) {
                    $collectedTitles[] = (string)$title;
                }
            }
            //Add titles to the beginning of descriptions
            if (isset($collectedTitles)) {
                $descriptions = array_merge($collectedTitles, $descriptions);
            }
        }

        return $descriptions;
    }

    /**
     * Get introduction.
     *
     * @return array
     */
    public function getIntroduction()
    {
        $results = [];
        $preferredLanguages = $this->getPreferredLanguageCodes();
        $xpath = 'lido/descriptiveMetadata/objectIdentificationWrap/objectDescriptionWrap'
            . '/objectDescriptionSet[@type="introduction"]/descriptiveNoteValue';
        foreach ($this->getXmlRecord()->xpath($xpath) as $node) {
            if (in_array((string)$node->attributes()->lang, $preferredLanguages)) {
                if ($term = trim((string)$node)) {
                    $results[] = $term;
                }
            }
        }
        return $results;
    }

    /**
     * Return an XML representation of the record using the specified format.
     * Return false if the format is unsupported.
     *
     * @param string     $format     Name of format to use (corresponds with OAI-PMH
     * metadataPrefix parameter).
     * @param string     $baseUrl    Base URL of host containing VuFind (optional;
     * may be used to inject record URLs into XML when appropriate).
     * @param RecordLink $recordLink Record link helper (optional; may be used to
     * inject record URLs into XML when appropriate).
     *
     * @return mixed         XML, or false if format unsupported.
     */
    public function getXML($format, $baseUrl = null, $recordLink = null)
    {
        if ('oai_lido' === $format) {
            return $this->fields['fullrecord'];
        }
        return parent::getXML($format, $baseUrl, $recordLink);
    }

    /**
     * Get LIDO language codes that correpond user preferred language.
     *
     * @return array
     */
    protected function getPreferredLanguageCodes()
    {
        return self::LANGUAGE_CODES[$this->preferredLanguage]
            ?? self::LANGUAGE_CODES['fi'];
    }

    /**
     * Get the display edition of the current record.
     *
     * @return array
     */
    public function getEditions()
    {
        $results = [];
        foreach (
            $this->getXmlRecord()->lido->descriptiveMetadata
            ->objectIdentificationWrap->displayStateEditionWrap
            ->displayEdition ?? [] as $edition
        ) {
            $results[] = (string)$edition;
        }
        return $results;
    }

    /**
     * Get hierarchy parent links by type
     *
     * @param string $relatedWorkType Related work type to include, empty string for
     * "others"
     *
     * @return array
     */
    protected function getParentLinksByType(string $relatedWorkType): array
    {
        $allowedTypes = $relatedWorkType
            ? $this->relatedWorkTypeMap[$relatedWorkType] : [];
        $disallowedTypes = $allowedTypes
            ? [] : call_user_func_array(
                'array_merge',
                array_values($this->relatedWorkTypeMap)
            );
        $sets = $this->getXmlRecord()->lido->descriptiveMetadata->objectRelationWrap
            ->relatedWorksWrap->relatedWorkSet ?? [];
        $sourceId = $this->getSource();
        $result = [];
        foreach ($sets as $set) {
            if ('is part of' !== (string)($set->relatedWorkRelType->term ?? '')) {
                continue;
            }
            $workType = '';
            foreach ($set->relatedWork->object->objectNote ?? [] as $note) {
                if ('objectWorkType' === (string)($note['type'] ?? '')) {
                    $workType = mb_strtolower(trim((string)$note), 'UTF-8');
                    break;
                }
            }
            if (
                ($allowedTypes && !in_array($workType, $allowedTypes))
                || ($disallowedTypes && in_array($workType, $disallowedTypes))
            ) {
                continue;
            }
            if ($id = (string)($set->relatedWork->object->objectID ?? '')) {
                $id = "$sourceId.$id";
            }
            $title = (string)($set->relatedWork->displayObject ?? '');
            if (
                $id
                && $title
                && $id !== $this->getUniqueID()
                && !in_array($id, array_column($result, 'id'))
            ) {
                $result[] = compact('id', 'title');
            }
        }

        return $result;
    }
}
