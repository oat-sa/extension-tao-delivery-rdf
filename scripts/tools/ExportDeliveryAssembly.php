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

namespace oat\taoDeliveryRdf\scripts\tools;

use Exception;
use common_exception_Error;
use oat\taoDeliveryRdf\model\assembly\CompiledTestConverterFactory;
use oat\taoDeliveryRdf\model\export\AssemblyExporterService;
use tao_helpers_File;
use common_report_Report as Report;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\extension\script\ScriptAction;

/**
 * php index.php "oat\taoDeliveryRdf\scripts\tools\ExportDeliveryAssembly" -uri deliveryUri -out ~/path.zip
 *
 * Class ExportDeliveryAssembly
 * @package oat\taoDeliveryRdf\scripts\tools
 */
class ExportDeliveryAssembly extends ScriptAction
{
    use OntologyAwareTrait;


    const OPTION_DELIVERY_URI = 'delivery-uri';
    const OPTION_OUTPUT_TEST_FORMAT = 'output-test-format';
    const OPTION_OUTPUT_FILEPATH = 'output-filepath';
    /**
         * @var Report
         */
    private $report;
    /**
         * @return array
         */
    protected function provideOptions()
    {
        return [
            self::OPTION_DELIVERY_URI => [
                'prefix' => 'uri',
                'required' => true,
                'longPrefix' => self::OPTION_DELIVERY_URI,
                'cast' => 'string',
                'description' => 'Compiled delivery RDF resource URI.'
            ],
            self::OPTION_OUTPUT_TEST_FORMAT => [
                'prefix' => 'format',
                'required' => false,
                'defaultValue' => CompiledTestConverterFactory::COMPILED_TEST_FORMAT_XML,
                'longPrefix' => self::OPTION_OUTPUT_TEST_FORMAT,
                'description' => 'Output format for compiled test data file.',
            ],
            self::OPTION_OUTPUT_FILEPATH => [
                'prefix' => 'out',
                'longPrefix' => self::OPTION_OUTPUT_FILEPATH,
                'required' => true,
                'description' => 'Filepath for compiled assembly package.',
            ],
        ];
    }

    /**
     * @return string
     */
    protected function provideDescription()
    {
        return 'Export compiled delivery assembly package with possibility to convert compiled test file into one of supported formats.';
    }

    /**
     * @return array
     */
    protected function provideUsage()
    {
        return [
            'prefix' => 'h',
            'longPrefix' => 'help',
            'description' => 'Prints a help statement'
        ];
    }

    /**
     * @return Report
     * @throws common_exception_Error
     */
    protected function run()
    {
        $this->report = Report::createInfo('Delivery assembly export started');
        try {
            $deliveryUri = $this->getOption(self::OPTION_DELIVERY_URI);
            $delivery = $this->getResource($deliveryUri);
            if (!$delivery->exists()) {
                $this->report->add(Report::createFailure(__('Delivery \'%s\' not found', $deliveryUri)));
                return $this->report;
            }

            $outputCompiledTestFormat = $this->getOption(self::OPTION_OUTPUT_TEST_FORMAT);
            $outputFile = $this->getOption(self::OPTION_OUTPUT_FILEPATH);
            /** @var AssemblyExporterService $assemblyExporter */
            $assemblyExporter = $this->getServiceLocator()->get(AssemblyExporterService::SERVICE_ID);
            $tmpFile = $assemblyExporter->exportCompiledDelivery($delivery, $outputCompiledTestFormat);
            tao_helpers_File::move($tmpFile, $outputFile);
            $this->report->add(Report::createSuccess(__('Exported %1$s to %2$s', $delivery->getLabel(), $outputFile)));
        } catch (Exception $e) {
            $this->report->add(Report::createFailure("Export failed: " . $e->getMessage()));
        }

        return $this->report;
    }
}
