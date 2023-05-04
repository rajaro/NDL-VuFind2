<?php

/**
 * SolrQdc Institutional Repository Test Class
 *
 * PHP version 8
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

use Finna\RecordDriver\SolrQdc;

/**
 * SolrQdc Institutional Repository Test Class
 *
 * @category VuFind
 * @package  Tests
 * @author   Juha Luoma <juha.luoma@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:testing:unit_tests Wiki
 */
class SolrQdcInstitutionalRepositoryTest extends \PHPUnit\Framework\TestCase
{
    use \VuFindTest\Feature\FixtureTrait;

    /**
     * Function to get expected function data
     *
     * @return array
     */
    public function getTestFunctionsData(): array
    {
        return [
            [
                'getAllImages',
                [
                    0 => [
                        'urls' => [
                            'large' => 'https://www.animals.of.earth.fi/duck.pdf',
                            'small' => 'https://www.animals.of.earth.fi/duck.pdf',
                            'medium' => 'https://www.animals.of.earth.fi/duck.pdf',
                        ],
                        'description' => '',
                        'rights' => [
                            'copyright' => 'CC BY 4.0',
                            'link' => 'http://creativecommons.org/licenses/by/4.0/deed.fi',
                        ],
                        'pdf' => true,
                        'downloadable' => true,
                    ],
                ],
            ],
            [
                'getAllRecordLinks',
                [
                    0 => [
                        'value' => 'Animals of Earth',
                        'link' => [
                            'value' => 'Animals of Earth',
                            'type' => 'allFields',
                        ],
                    ],
                ],
            ],
            [
                'getSeries',
                [],
            ],
            [
                'getIdentifier',
                [],
            ],
            [
                'getKeywords',
                [],
            ],
            [
                'getISBNs',
                [],
            ],
            [
                'getOtherIdentifiers',
                [
                    0 => [
                        'data' => '123-4-245-6',
                        'detail' => '',
                    ],
                ],
            ],
            [
                'getURLs',
                [],
            ],
            [
                'getEducationPrograms',
                [],
            ],
            [
                'getPhysicalDescriptions',
                [],
            ],
            [
                'getPhysicalMediums',
                [],
            ],
            [
                'getDescriptions',
                [],
            ],
            [
                'getAbstracts',
                [],
            ],
            [
                'getDescriptionURL',
                false,
            ],
        ];
    }

    /**
     * Test functions
     *
     * @param string $function Function of the driver to test
     * @param mixed  $expected Result to be expected
     *
     * @dataProvider getTestFunctionsData
     *
     * @return void
     */
    public function testFunctions(
        string $function,
        $expected
    ): void {
        $driver = $this->getDriver();
        $this->assertTrue(is_callable([$driver, $function], true));
        $this->assertEquals(
            $expected,
            $driver->$function()
        );
    }

    /**
     * Get a record driver with fake data
     *
     * @param array $overrides    Fixture fields to override
     * @param array $searchConfig Search configuration
     *
     * @return SolrQdc
     */
    protected function getDriver($overrides = [], $searchConfig = []): SolrQdc
    {
        $fixture = $this->getFixture('qdc/qdc_ir_test.xml', 'Finna');
        $config = [
            'Content' => [
                'pdfCoverImageDownload' => '0/Painting',
            ],
            'Record' => [
                'allowed_external_hosts_mode' => 'disable',
            ],
            'ImageRights' => [
                'fi' => [
                    'CC BY 4.0' => 'http://creativecommons.org/licenses/by/4.0/deed.fi',
                ],
                'en-gb' => [
                    'CC BY 4.0' => 'http://creativecommons.org/licenses/by/4.0/deed.en',
                ],
                'sv' => [
                    'CC BY 4.0' => 'http://creativecommons.org/licenses/by/4.0/deed.sv',
                ],
            ],
            'FileDownload' => [
                'excludeRights' => [
                    'InC',
                ],
            ],
        ];
        $config = new \Laminas\Config\Config($config);
        $record = new SolrQdc(
            $config,
            $config,
            new \Laminas\Config\Config($searchConfig)
        );
        $record->setRawData(
            [
                'id' => 'knp-247394',
                'fullrecord' => $fixture,
                'usage_rights_str_mv' => [
                    'usage_A',
                ],
                'format' => '0/Painting',
            ]
        );
        return $record;
    }
}
