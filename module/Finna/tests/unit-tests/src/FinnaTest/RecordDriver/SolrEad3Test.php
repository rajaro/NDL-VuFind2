<?php

/**
 * SolrEad3 Test Class
 *
 * PHP version 8
 *
 * Copyright (C) The National Library of Finland 2023.
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
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */

namespace FinnaTest\RecordDriver;

use Finna\RecordDriver\SolrEad3;

/**
 * SolrEad3 Record Driver Test Class
 *
 * @category VuFind
 * @package  Tests
 * @author   Jaro Ravila <jaro.ravila@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
class SolrEad3Test extends \PHPUnit\Framework\TestCase
{
    use \VuFindTest\Feature\FixtureTrait;
    use \VuFindTest\Feature\TranslatorTrait;

    /**
     * Get unit dates
     *
     * @return void
     */
    public function testGetUnitDates()
    {
        $driver = $this->getDriver('ead3_test.xml');
        $ndash = html_entity_decode('&#x2013;', ENT_NOQUOTES, 'UTF-8');
        $dates = [
            [
                'data' => "1600{$ndash}",
                'detail' => 'Ajallinen kattavuus',
            ],
            [
                'data' => '1661',
                'detail' => 'Ajallinen kattavuus',
            ],
            [
                'data' => '1660-luku',
                'detail' => 'Ajallinen kattavuus',
            ],
            [
                'data' => "01.01.1600{$ndash}01.01.1610",
                'detail' => 'Ajallinen kattavuus',
            ],
            [
                'data' => '1923',
                'detail' => '',
            ],
            [
                'data' => "1925{$ndash}",
                'detail' => '',
            ],
        ];
        $this->assertEquals($dates, $driver->getUnitDates());
    }

    /**
     * Function to get expected other related material data
     *
     * @return array
     */
    public static function getOtherRelatedMaterialData(): array
    {
        return [
            [
                'fi',
                [
                    [
                        'text' => 'Wikipedia-artikkeli',
                        'url' => 'https://fi.wikipedia.org/',
                    ],
                    [
                        'text' => 'Joku muu liittyvä aineisto',
                        'url' => '',
                    ],
                ],
            ],
            [
                'en-gb',
                [
                    [
                        'text' => 'Some related material',
                        'url' => '',
                    ],
                ],
            ],
            [
                'sv',
                [
                    [
                        'text' => 'https://sv.wikipedia.org/',
                        'url' => 'https://sv.wikipedia.org/',
                    ],
                ],
            ],
        ];
    }

    /**
     * Test getOtherRelatedMaterial
     *
     * @param string $language Language
     * @param array  $expected Result to be expected
     *
     * @dataProvider getOtherRelatedMaterialData
     *
     * @return void
     */
    public function testGetOtherRelatedMaterial(
        string $language,
        array $expected
    ): void {
        $driver = $this->getDriver('ead3_test.xml');
        $driver->setPreferredLanguage($language);
        $this->assertEquals(
            $expected,
            $driver->getOtherRelatedMaterial()
        );
    }

    /**
     * Function to get expected author data
     *
     * @return array
     */
    public static function getAuthorData(): array
    {
        return [
            [
                'getNonPresenterAuthors',
                [
                    'ead3_test.xml' => [
                        [
                            'id' => 'EAC_102476374',
                            'role' => 'rda:collector',
                            'name' => 'Suomalaisen Kirjallisuuden Seura ry',
                        ],
                        [
                            'id' => 'EAC_123456',
                            'role' => 'ive',
                            'name' => 'Harri Haastateltava',
                        ],
                        [
                            'id' => '',
                            'role' => '',
                            'name' => 'Rolle Rooliton',
                        ],
                        [
                            'id' => '',
                            'role' => 'tuntematon rooli',
                            'name' => 'Tuovi Tuntematon',
                        ],
                        [
                            'id' => '',
                            'role' => 'rda:former-owner',
                            'name' => 'Lucifer Luovuttaja',
                        ],
                    ],
                    'ead3_test2.xml' => [
                        [
                            'id' => 'EAC_76543',
                            'role' => 'tuntematon rooli',
                            'name' => 'Tuukka Tuntematon',
                        ],
                        [
                            'id' => '',
                            'role' => '',
                            'name' => 'Roope Rooliton',
                        ],
                    ],
                ],
            ],
            [
                'getAuthorsWithoutRoleHeadings',
                [
                    'ead3_test.xml' => [],
                    'ead3_test2.xml' => [
                        [
                            'id' => 'EAC_76543',
                            'role' => 'tuntematon rooli',
                            'name' => 'Tuukka Tuntematon',
                        ],
                        [
                            'id' => '',
                            'role' => '',
                            'name' => 'Roope Rooliton',
                        ],
                    ],
                ],
            ],
            [
                'getAuthorsWithRoleHeadings',
                [
                    'ead3_test.xml' => [
                        [
                            'id' => 'EAC_102476374',
                            'role' => 'rda:collector',
                            'name' => 'Suomalaisen Kirjallisuuden Seura ry',
                        ],
                        [
                            'id' => 'EAC_123456',
                            'role' => 'ive',
                            'name' => 'Harri Haastateltava',
                        ],
                        [
                            'id' => '',
                            'role' => 'rda:former-owner',
                            'name' => 'Lucifer Luovuttaja',
                        ],
                    ],
                    'ead3_test2.xml' => [],
                ],
            ],
            [
                'getOtherAuthors',
                [
                    'ead3_test.xml' => [
                        [
                            'id' => '',
                            'role' => '',
                            'name' => 'Rolle Rooliton',
                        ],
                        [
                            'id' => '',
                            'role' => 'tuntematon rooli',
                            'name' => 'Tuovi Tuntematon',
                        ],
                    ],
                    'ead3_test2.xml' => [],
                ],
            ],
            [
                'getSubjectActors',
                [
                    'ead3_test.xml' => [
                        'Anssi Aihe',
                        'Aino Aihe',
                    ],
                    'ead3_test2.xml' => [
                        'Aino Aihe',
                    ],
                ],
            ],
        ];
    }

    /**
     * Test authors
     *
     * @param string $function Function of the driver to test
     * @param array  $expected Result to be expected
     *
     * @dataProvider getAuthorData
     *
     * @return void
     */
    public function testAuthors(
        string $function,
        array $expected
    ): void {
        foreach ($expected as $file => $result) {
            $driver = $this->getDriver($file);
            $this->assertEquals(
                $result,
                $driver->$function()
            );
        }
    }

    /**
     * Function to get expected subject headings data
     *
     * @return array
     */
    public static function getAllSubjectHeadingsExtendedData(): array
    {
        return [
            [
                'fi',
                [
                    'ead3_test.xml' => [
                        [
                            'id' => 'EAC_102486375',
                            'source' => '',
                            'detail' => 'aihe',
                            'heading' => ['Manner, Eeva-Liisa'],
                            'type' => 'topic',
                            'authType' => 'Unknown Name',
                        ],
                        [
                            'id' => 'http://www.yso.fi/onto/yso/p900',
                            'source' => 'YSO',
                            'detail' => 'aihe',
                            'heading' => ['fysiikka'],
                            'type' => 'topic',
                            'authType' => null,

                        ],
                        [
                            'source' => '',
                            'detail' => '',
                            'type' => 'topic',
                            'heading' => ['Elintarviketeollisuus, Myllytuotteiden valmistus'],
                        ],
                    ],
                    'ead3_test2.xml' => [
                        [
                            'id' => 'http://www.yso.fi/onto/koko/p9492',
                            'source' => 'KOKO',
                            'detail' => 'asiasana',
                            'heading' => ['kirjoituskilpailut'],
                            'type' => 'topic',
                            'authType' => null,
                        ],
                    ],
                ],
                'sv',
                [
                    'ead3_test.xml' => [
                        [
                            'id' => 'EAC_102486375',
                            'source' => '',
                            'detail' => 'aihe',
                            'heading' => ['Manner, Eeva-Liisa'],
                            'type' => 'topic',
                            'authType' => 'Unknown Name',
                        ],
                        [
                            'id' => 'http://www.yso.fi/onto/yso/p900',
                            'source' => 'YSO',
                            'detail' => 'aihe',
                            'heading' => ['fysiikka'],
                            'type' => 'topic',
                            'authType' => null,

                        ],
                        [
                            'source' => '',
                            'detail' => '',
                            'type' => 'topic',
                            'heading' => ['Elintarviketeollisuus, Myllytuotteiden valmistus'],
                        ],
                    ],
                    'ead3_test2.xml' => [
                        [
                            'id' => 'http://www.yso.fi/onto/koko/p9492',
                            'source' => 'KOKO',
                            'detail' => 'asiasana',
                            'heading' => ['skrivartävlingar'],
                            'type' => 'topic',
                            'authType' => null,
                        ],
                    ],
                ],
                'en',
                [
                    'ead3_test.xml' => [
                        [
                            'id' => 'http://www.yso.fi/onto/koko/p9492',
                            'source' => 'KOKO',
                            'detail' => 'asiasana',
                            'heading' => ['writing contests'],
                            'type' => 'topic',
                            'authType' => null,
                        ],
                    ],
                    'ead3_test2.xml' => [
                        [
                            'id' => 'http://www.yso.fi/onto/koko/p9492',
                            'source' => 'KOKO',
                            'detail' => 'asiasana',
                            'heading' => ['writing contests'],
                            'type' => 'topic',
                            'authType' => null,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Test getAllSubjectHeadingsExtended
     *
     * @param string $language Language
     * @param array  $expected Result to be expected
     *
     * @dataProvider getAllSubjectHeadingsExtendedData
     *
     * @return void
     */
    public function testAllSubjectHeadingsExtended(
        string $language,
        array $expected
    ): void {
        foreach ($expected as $file => $result) {
            $driver = $this->getDriver($file);
            $driver->setPreferredLanguage($language);
            $this->assertEquals(
                $result,
                $driver->getAllSubjectHeadingsExtended()
            );
        }
    }

    /**
     * Function to get expected physical descriptions data
     *
     * @return array
     */
    public static function getPhysicalDescriptionsData(): array
    {
        return [
            [
                'fi',
                [
                    'ead3_test.xml' => [
                        'Hyllymetriä järjestetty 0.96 hm',
                        'Koteloita 5',
                    ],
                    'ead3_test2.xml' => [
                        '9 koteloa',
                    ],
                ],
                'sv',
                [
                    'ead3_test.xml' => [
                        'Hyllmeter ordnat 0.96 hm',
                    ],
                    'ead3_test2.xml' => [
                        '9 mappar',
                    ],
                ],
                'en',
                [
                    'ead3_test.xml' => [
                        'Hyllymetriä järjestetty 0.96 hm',
                        'Koteloita 5',
                    ],
                    'ead3_test2.xml' => [
                        '9 koteloa',
                    ],
                ],
            ],
        ];
    }

    /**
     * Test getPhysicalDescriptions
     *
     * @param string $language Language
     * @param array  $expected Result to be expected
     *
     * @dataProvider getPhysicalDescriptionsData
     *
     * @return void
     */
    public function testPhysicalDescriptions(
        string $language,
        array $expected
    ): void {
        foreach ($expected as $file => $result) {
            $driver = $this->getDriver($file);
            $driver->setPreferredLanguage($language);
            $this->assertEquals(
                $result,
                $driver->getPhysicalDescriptions()
            );
        }
    }

    /**
     * Get a record driver with fake data.
     *
     * @param string $recordXml    Xml record to use for the test
     * @param array  $overrides    Fixture fields to override.
     * @param array  $searchConfig Search configuration.
     *
     * @return SolrEad3
     */
    protected function getDriver(string $recordXml, $overrides = [], $searchConfig = []): SolrEad3
    {
        $fixture = $this->getFixture("ead3/$recordXml", 'Finna');
        $record = new SolrEad3(
            null,
            null,
            new \Laminas\Config\Config($searchConfig)
        );
        $record->setTranslator(
            $this->getMockTranslator(
                ['default' => ['year_decade_or_century' => '%%year%%-luku']]
            )
        );
        $record->setRawData(['fullrecord' => $fixture]);
        return $record;
    }
}
