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

namespace oat\taoDeliveryRdf\test\unit\model\assembly;

use InvalidArgumentException;
use oat\generis\test\TestCase;
use oat\oatbox\log\LoggerService;
use oat\taoDeliveryRdf\model\assembly\CompiledTestConverterFactory;
use oat\taoDeliveryRdf\model\assembly\CompiledTestConverterService;
use oat\taoDeliveryRdf\model\assembly\UnsupportedCompiledTestFormatException;
use oat\taoQtiTest\models\CompilationDataService;
use oat\taoQtiTest\models\PhpCodeCompilationDataService;
use oat\taoQtiTest\models\PhpSerializationCompilationDataService;
use oat\taoQtiTest\models\XmlCompilationDataService;

class CompiledTestConverterFactoryTest extends TestCase
{
    /**
     * @var CompiledTestConverterFactory
     */
    private $object;
    protected function setUp(): void
    {
        parent::setUp();
        $this->object = new CompiledTestConverterFactory();
    }

    /**
     * @param mixed $outputTestFormat
     * @dataProvider dataProviderTestCreateConverterFailsIfParameterNotString
     */
    public function testCreateConverterFailsIfParameterNotString($outputTestFormat)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->object->createConverter($outputTestFormat);
    }

    public function testCreateConverterFailsIfFormatNotSupported()
    {
        $this->expectException(UnsupportedCompiledTestFormatException::class);
        $unsupportedFormat = 'CUSTOM_FORMAT';
        $this->object->createConverter($unsupportedFormat);
    }

    /**
     * @param string $outputTestFormat
     * @param string $expectedCompilationImplementation
     *
     * @dataProvider dataProviderTestCreateConverterReturnsCompiledTestConverter
     */
    public function testCreateConverterReturnsCompiledTestConverter($outputTestFormat, $expectedCompilationImplementation)
    {
        $slMock = $this->getServiceLocatorMock([
            CompilationDataService::SERVICE_ID => $this->createMock(CompilationDataService::class),
            LoggerService::SERVICE_ID => $this->createMock(LoggerService::class),
        ]);
        $this->object->setServiceLocator($slMock);
        $result = $this->object->createConverter($outputTestFormat);
        $this->assertInstanceOf(CompiledTestConverterService::class, $result, 'Factory must create a test converter instance of required type.');
        // Assert that correct implementation of CompilationDataService was initialized by factory based on input parameter.
        $reflectionProperty = new \ReflectionProperty(get_class($result), 'compilationDataWriter');
        $reflectionProperty->setAccessible(true);
        $outputCompilationService = $reflectionProperty->getValue($result);
        $this->assertInstanceOf($expectedCompilationImplementation, $outputCompilationService, 'Factory must create a compiled test converter with correct "compilationDataWriter" implementation.');
    }

    /**
     * @return array
     */
    public function dataProviderTestCreateConverterFailsIfParameterNotString()
    {
        return [
            'Output test format is a NULL' => [
                'outputTestFormat' => null,
            ],
            'Output test format is a boolean' => [
                'outputTestFormat' => true,
            ],
            'Output test format is an integer' => [
                'outputTestFormat' => 111,
            ],
            'Output test format is an array' => [
                'outputTestFormat' => ['test_format'],
            ],
            'Output test format is an object' => [
                'outputTestFormat' => new \stdClass(),
            ],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestCreateConverterReturnsCompiledTestConverter()
    {
        return [
            'PHP format' => [
                'outputTestFormat' => 'php',
                'expectedCompilationImplementation' => PhpCodeCompilationDataService::class,
            ],
            'PHP format capitalized' => [
                'outputTestFormat' => 'PHP',
                'expectedCompilationImplementation' => PhpCodeCompilationDataService::class,
            ],
            'PHP format with whitespaces' => [
                'outputTestFormat' => ' php ',
                'expectedCompilationImplementation' => PhpCodeCompilationDataService::class,
            ],
            'PHP serialized format' => [
                'outputTestFormat' => 'php_serialized',
                'expectedCompilationImplementation' => PhpSerializationCompilationDataService::class,
            ],
            'XML format' => [
                'outputTestFormat' => 'xml',
                'expectedCompilationImplementation' => XmlCompilationDataService::class,
            ],
        ];
    }
}
