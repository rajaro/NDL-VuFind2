<?php
/**
 * SolrLido Test Class
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2022.
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
 * @package  Tests
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
namespace FinnaTest\RecordDriver;

use Finna\RecordDriver\SolrLido;

/**
 * SolrLido Record Driver Test Class
 *
 * @category VuFind
 * @package  Tests
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
class SolrLidoTest extends \PHPUnit\Framework\TestCase
{
    use \VuFindTest\Feature\FixtureTrait;

    /**
     * Function to get expected representations data
     *
     * @return array
     */
    public function getRepresentationsData(): array
    {
        return [
            [
                'getModels',
                [
                    2 => [
                        'gltf' => [
                            'preview' => 'https://gltfmalli.gltf'
                        ],
                        'glb' => [
                            'preview' => 'https://glbmalli.glb'
                        ]
                    ]
                ]
            ],
            [
                'getAllImages',
                [
                    [
                        'urls' => [
                            'large' => 'https://largekuvanlinkki.com',
                            'original' => 'https://originalKuvanLinkkiTif.com',
                            'small' => 'https://largekuvanlinkki.com',
                            'medium' => 'https://largekuvanlinkki.com'
                        ],
                        'description' => '',
                        'rights' => [
                            'copyright' => 'CC BY 4.0',
                            'description' => [
                                0 => 'Tässä on kuvien copyright.'
                            ]
                        ],
                        'highResolution' => [
                            'original' => [
                                0 => [
                                    'data' => [
                                        'size' => [
                                            'unit' => 'bytes',
                                            'value' => '123'
                                        ],
                                        'width' => [
                                            'unit' => 'pixel',
                                            'value' => '123'
                                        ],
                                        'height' => [
                                            'unit' => 'pixel',
                                            'value' => '123'
                                        ]
                                    ],
                                    'url' => 'https://originalKuvanLinkkiTif.com',
                                    'format' => 'tif',
                                    'resourceID' => '607642'
                                ]
                            ]
                        ],
                        'identifier' => '607642',
                        'downloadable' => true,
                        'resourceDescription' => 'Kuvan selitys'
                    ],
                    [
                        'urls' => [
                            'large' => 'https://largekuvanlinkki2.com',
                            'original' => 'https://originalKuvanLinkkiTif.com',
                            'small' => 'https://thumbkuvanlinkki2.com',
                            'medium' => 'https://thumbkuvanlinkki2.com',
                            'master' => 'https://masterkuvanlinkki2.com'
                        ],
                        'description' => '',
                        'rights' => [
                            'copyright' => 'InC',
                            'description' => [
                                0 => 'Tässä on kuvien copyright.'
                            ]
                        ],
                        'highResolution' => [
                            'original' => [
                                0 => [
                                    'data' => [
                                        'size' => [
                                            'unit' => 'bytes',
                                            'value' => '5'
                                        ],
                                        'width' => [
                                            'unit' => 'pixel',
                                            'value' => '5'
                                        ],
                                        'height' => [
                                            'unit' => 'pixel',
                                            'value' => '5'
                                        ]
                                    ],
                                    'url' => 'https://originalKuvanLinkkiTif.com',
                                    'format' => 'tif',
                                    'resourceID' => '607643'
                                ]
                            ],
                            'master' => [
                                [
                                    'url' => 'https://masterkuvanlinkki2.com',
                                    'data' => false,
                                    'format' => 'jpg',
                                    'resourceID' => '607643'
                                ]
                            ]
                        ],
                        'identifier' => '607643',
                        'downloadable' => false,
                        'resourceName' => 'Kuvan nimi'
                    ],
                    7 => [
                        'urls' => [
                            'large' => 'https://kaikkilinkit.com',
                            'small' => 'https://kaikkilinkit.com',
                            'medium' => 'https://kaikkilinkit.com'
                        ],
                        'description' => '',
                        'rights' => [
                            'copyright' => 'CC BY 4.0',
                            'description' => [
                                0 => 'Tässä on kuvien copyright.',
                                1 => 'Tässä on kuvien copyright.'
                            ]
                        ],
                        'highResolution' => [],
                        'identifier' => '607644',
                        'downloadable' => true
                    ]
                ]
            ],
            [
                'getURLs',
                [
                    [
                        'desc' => 'AudioTesti.mp3',
                        'url' => 'https://linkkiaudioon.fi',
                        'codec' => 'mp3',
                        'type' => 'audio',
                        'embed' => 'audio'
                    ],
                    [
                        'desc' => 'VideoTesti.mp4',
                        'url' => 'https://linkkivideoon.fi',
                        'embed' => 'video',
                        'format' => 'mp4',
                        'videoSources' => [
                            'src' => 'https://linkkivideoon.fi',
                            'type' => 'video/mp4'
                        ]
                    ],
                ]
            ],
            [
                'getDocuments',
                [
                    5 => [
                        'description' => 'PDFTesti.pdf',
                        'url' => 'https://linkkiPDF.fi',
                        'format' => 'pdf'
                    ],
                    6 => [
                        'description' => 'DocxTesti.docx',
                        'url' => 'https://linkkiDocx.fi',
                        'format' => 'docx'
                    ]
                ]
            ],
        ];
    }

    /**
     * Function to get expected format classifications data
     *
     * @return array
     */
    public function getFormatClassificationsData(): array
    {
        return [
            [
                'getFormatClassifications',
                [
                    'lido_test.xml' => [
                        'näkyy (testimittari)'
                    ],
                    'lido_test2.xml' => [
                        'uno (testimittari)',
                        'dos',
                        'one (testimittari)',
                        'two'
                    ]
                ]
            ],
        ];
    }

    /**
     * Function to get expected other classifications data
     *
     * @return array
     */
    public function getOtherClassificationsData(): array
    {
        return [
            [
                'getOtherClassifications',
                [
                    'lido_test.xml' => [
                        'näkyy'
                    ],
                    'lido_test2.xml' => [
                        [
                            'term' => 'uno',
                            'label' => 'testimittari'
                        ],
                        [
                            'term' => 'one',
                            'label' => 'testimittari'
                        ]
                    ]
                ]
            ],
        ];
    }

    /**
     * Test representations
     *
     * @param string $function Function of the driver to test
     * @param array  $expected Result to be expected
     *
     * @dataProvider getRepresentationsData
     *
     * @return void
     */
    public function testRepresentations(
        string $function,
        array $expected
    ): void {
        $driver = $this->getDriver('lido_test.xml');
        $this->assertTrue(is_callable([$driver, $function], true));
        $this->assertEquals(
            $expected,
            $driver->$function()
        );
    }

    /**
     * Test getFormatClassifications
     *
     * @param string $function Function of the driver to test
     * @param array  $expected Result to be expected
     *
     * @dataProvider getFormatClassificationsData
     *
     * @return void
     */
    public function testGetFormatClassifications(
        string $function,
        array $expected
    ): void {
        foreach ($expected as $file => $result) {
            $driver = $this->getDriver($file);
            $this->assertTrue(is_callable([$driver, $function], true));
            $this->assertEquals(
                $result,
                $driver->$function()
            );
        }
    }

    /**
     * Test getOtherClassifications
     *
     * @param string $function Function of the driver to test
     * @param array  $expected Result to be expected
     *
     * @dataProvider getOtherClassificationsData
     *
     * @return void
     */
    public function testGetOtherClassifications(
        string $function,
        array $expected
    ): void {
        foreach ($expected as $file => $result) {
            $driver = $this->getDriver($file);
            $this->assertTrue(is_callable([$driver, $function], true));
            $this->assertEquals(
                $result,
                $driver->$function()
            );
        }
    }

    /**
     * Get a record driver with fake data
     *
     * @param string $recordXml    Xml record to use for the test
     * @param array  $overrides    Fixture fields to override
     * @param array  $searchConfig Search configuration
     *
     * @return SolrLido
     */
    protected function getDriver(
        string $recordXml,
        $overrides = [],
        $searchConfig = []
    ): SolrLido {
        $fixture = $this->getFixture("lido/$recordXml", 'Finna');
        $config = [
            'Record' => [
                'allowed_external_hosts_mode' => 'disable',
            ],
            'FileDownload' => [
                'excludeRights' => [
                    'INC'
                ]
            ]
        ];
        $config = new \Laminas\Config\Config($config);
        $record = new SolrLido(
            $config,
            $config,
            new \Laminas\Config\Config($searchConfig)
        );
        $record->setRawData(
            [
                'id' => 'knp-247394',
                'fullrecord' => $fixture,
                'usage_rights_str_mv' => [
                    'usage_A'
                ]
            ]
        );
        return $record;
    }
}
