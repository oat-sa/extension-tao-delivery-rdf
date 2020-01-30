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

namespace oat\taoDeliveryRdf\model\assembly;

use InvalidArgumentException;
use oat\oatbox\service\ConfigurableService;
use oat\taoQtiTest\models\CompilationDataService;
use oat\taoQtiTest\models\PhpCodeCompilationDataService;
use oat\taoQtiTest\models\PhpSerializationCompilationDataService;
use oat\taoQtiTest\models\XmlCompilationDataService;

class CompiledTestConverterFactory extends ConfigurableService
{
    const COMPILED_TEST_FORMAT_XML = 'xml';
    const COMPILED_TEST_FORMAT_PHP = 'php';
    const COMPILED_TEST_FORMAT_PHP_SERIALIZED = 'php_serialized';
    /**
         * @param $outputTestFormat
         * @return CompiledTestConverterService
         * @throws UnsupportedCompiledTestFormatException
         */
    public function createConverter($outputTestFormat)
    {
        if (!is_string($outputTestFormat)) {
            throw new InvalidArgumentException('Output compiled test type parameter must be a string.');
        }

        $outputCompilationService = $this->getOutputCompilationService($outputTestFormat);
        $systemCompilationService = $this->getServiceLocator()->get(CompilationDataService::SERVICE_ID);
        return new CompiledTestConverterService($systemCompilationService, $outputCompilationService);
    }

    /**
     * @param $outputTestFormat
     * @return CompilationDataService
     * @throws UnsupportedCompiledTestFormatException
     */
    private function getOutputCompilationService($outputTestFormat)
    {
        $outputTestFormat = strtolower(trim($outputTestFormat));
        switch ($outputTestFormat) {
            case self::COMPILED_TEST_FORMAT_PHP:
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             $outputCompilationService = new PhpCodeCompilationDataService();

                break;
            case self::COMPILED_TEST_FORMAT_PHP_SERIALIZED:
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         $outputCompilationService = new PhpSerializationCompilationDataService();

                break;
            case self::COMPILED_TEST_FORMAT_XML:
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         $outputCompilationService = new XmlCompilationDataService();

                break;
            default:
                throw new UnsupportedCompiledTestFormatException("Unsupported compiled test format provided: {$outputTestFormat}");
        }

        return $this->propagate($outputCompilationService);
    }
}
