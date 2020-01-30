<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2019  (original work) Open Assessment Technologies SA;
 */

namespace oat\taoDeliveryRdf\test\unit\model\export;

use oat\taoDeliveryRdf\model\assembly\AssemblyFilesReader;
use tao_models_classes_export_RdfExporter;
use oat\generis\test\TestCase;
use oat\taoDeliveryRdf\model\export\AssemblyExporterService;

class AssemblyExporterServiceTest extends TestCase
{
    /**
     * @param array $options
     * @dataProvider dataProviderTestConstructorFailsWithoutRequiredOptions
     */
    public function testConstructorFailsWithoutRequiredOptions(array $options)
    {
        $this->expectException(\InvalidArgumentException::class);
        new AssemblyExporterService([$options]);
    }

    /**
     * @return array
     */
    public function dataProviderTestConstructorFailsWithoutRequiredOptions()
    {
        return [
            'Without file reader' => [
                'options' => [
                    'rdf_exporter' => new tao_models_classes_export_RdfExporter(),
                ]
            ],
            'Without RDF exporter' => [
                'options' => [
                    'assembly_files_reader' => new AssemblyFilesReader()
                ]
            ],
            'Invalid file reader' => [
                'options' => [
                    'assembly_files_reader' => new \stdClass(),
                    'rdf_exporter' => new tao_models_classes_export_RdfExporter(),
                ]
            ],
            'Invalid RDF exporter' => [
                'options' => [
                    'assembly_files_reader' => new AssemblyFilesReader(),
                    'rdf_exporter' => new \stdClass(),
                ]
            ],
        ];
    }
}
