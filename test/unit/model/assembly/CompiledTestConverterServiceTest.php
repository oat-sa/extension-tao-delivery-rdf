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

use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\oatbox\filesystem\File;
use oat\taoDeliveryRdf\model\assembly\CompiledTestConverterService;
use oat\taoQtiTest\models\PhpCodeCompilationDataService;
use oat\taoQtiTest\models\XmlCompilationDataService;
use qtism\data\QtiComponent;

class CompiledTestConverterServiceTest extends TestCase
{
    const FILE_BASE_NAME = 'BASE_NAME.EXT';

    /**
     * @var CompiledTestConverterService
     */
    private $object;

    /**
     * @var PhpCodeCompilationDataService|MockObject
     */
    private $phpCompilationServiceMock;

    /**
     * @var XmlCompilationDataService|MockObject
     */
    private $xmlCompilationServiceMock;

    /**
     * @var \tao_models_classes_service_StorageDirectory|MockObject
     */
    private $directoryMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    protected function setUp()
    {
        parent::setUp();
        $this->phpCompilationServiceMock = $this->createMock(PhpCodeCompilationDataService::class);
        $this->phpCompilationServiceMock->method('readCompilationData')
            ->willReturn($this->createMock(QtiComponent::class));

        $this->xmlCompilationServiceMock = $this->createMock(XmlCompilationDataService::class);
        $this->xmlCompilationServiceMock->method('readCompilationData')
            ->willReturn($this->createMock(QtiComponent::class));

        $this->directoryMock = $this->createMock(\tao_models_classes_service_StorageDirectory::class);

        $this->fileMock = $this->createMock(File::class);
        $this->fileMock->method('getBasename')
            ->willReturn(self::FILE_BASE_NAME);

        $this->object = new CompiledTestConverterService([
            'php_compilation_service' => $this->phpCompilationServiceMock,
            'xml_compilation_service' => $this->xmlCompilationServiceMock,
        ]);
    }

    /**
     * @param array $options
     * @dataProvider dataProviderTestConstructorFailsWithoutRequiredOptions
     */
    public function testConstructorFailsWithoutRequiredOptions(array $options)
    {
        $this->expectException(\InvalidArgumentException::class);
        new CompiledTestConverterService([$options]);
    }

    public function testConvertXmlToPhpFileExists()
    {
        $expectedPhpFile = $this->createMock(File::class);
        $expectedPhpFile->method('exists')
            ->willReturn(true);
        $expectedPhpFile->expects($this->once())
            ->method('delete');

        $this->directoryMock->method('getFile')
            ->with('BASE_NAME.php')
            ->willReturn($expectedPhpFile);

        $result = $this->object->convertXmlToPhp($this->fileMock, $this->directoryMock);
        $this->assertSame($expectedPhpFile, $result, 'Method must return test file converted to PHP.');
    }

    public function testConvertXmlToPhpFileDontExist()
    {
        $expectedPhpFile = $this->createMock(File::class);
        $expectedPhpFile->method('exists')
            ->willReturn(false);
        $expectedPhpFile->expects($this->never())
            ->method('delete');

        $this->directoryMock->method('getFile')
            ->with('BASE_NAME.php')
            ->willReturn($expectedPhpFile);

        $result = $this->object->convertXmlToPhp($this->fileMock, $this->directoryMock);
        $this->assertSame($expectedPhpFile, $result, 'Method must return test file converted to PHP.');
    }

    public function testConvertPhpToXmlFileDontExists()
    {
        $expectedXmlFile = $this->createMock(File::class);
        $expectedXmlFile->method('exists')
            ->willReturn(false);
        $expectedXmlFile->expects($this->never())
            ->method('delete');

        $this->directoryMock->method('getFile')
            ->with('BASE_NAME.xml')
            ->willReturn($expectedXmlFile);

        $result = $this->object->convertPhpToXml($this->fileMock, $this->directoryMock);
        $this->assertSame($expectedXmlFile, $result, 'Method must return test file converted to XML.');
    }

    public function testConvertPhpToXmlFileExists()
    {
        $expectedXmlFile = $this->createMock(File::class);
        $expectedXmlFile->method('exists')
            ->willReturn(true);
        $expectedXmlFile->expects($this->once())
            ->method('delete');

        $this->directoryMock->method('getFile')
            ->with('BASE_NAME.xml')
            ->willReturn($expectedXmlFile);

        $result = $this->object->convertPhpToXml($this->fileMock, $this->directoryMock);
        $this->assertSame($expectedXmlFile, $result, 'Method must return test file converted to XML.');
    }

    /**
     * @return array
     */
    public function dataProviderTestConstructorFailsWithoutRequiredOptions()
    {
        return [
            'No PHP compilation service' => [
                'options' => [
                    'xml_compilation_service' => new XmlCompilationDataService(),
                ]
            ],
            'No XML compilation service' => [
                'options' => [
                    'php_compilation_service' => new PhpCodeCompilationDataService()
                ]
            ],
            'Invalid PHP compilation service' => [
                'options' => [
                    'php_compilation_service' => new \stdClass(),
                    'xml_compilation_service' => new XmlCompilationDataService(),
                ]
            ],
            'Invalid XML compilation service' => [
                'options' => [
                    'php_compilation_service' => new PhpCodeCompilationDataService(),
                    'xml_compilation_service' => new \stdClass(),
                ]
            ],
        ];
    }
}
