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

use tao_models_classes_service_StorageDirectory;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\oatbox\filesystem\File;
use oat\taoDeliveryRdf\model\assembly\CompiledTestConverterService;
use oat\taoQtiTest\models\CompilationDataService;
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
    private $compilationDataReaderMock;
    /**
         * @var XmlCompilationDataService|MockObject
         */
    private $compilationDataWriterMock;
    /**
         * @var \tao_models_classes_service_StorageDirectory|MockObject
         */
    private $directoryMock;
    /**
         * @var File|MockObject
         */
    private $fileMock;
    protected function setUp(): void
    {
        parent::setUp();
        $this->compilationDataReaderMock = $this->createMock(CompilationDataService::class);
        $this->compilationDataReaderMock->method('readCompilationData')
            ->willReturn($this->createMock(QtiComponent::class));
        $this->compilationDataWriterMock = $this->createMock(CompilationDataService::class);
        $this->compilationDataWriterMock->method('readCompilationData')
            ->willReturn($this->createMock(QtiComponent::class));
        $this->directoryMock = $this->createMock(tao_models_classes_service_StorageDirectory::class);
        $this->fileMock = $this->createMock(File::class);
        $this->fileMock->method('getBasename')
            ->willReturn(self::FILE_BASE_NAME);
        $this->object = new CompiledTestConverterService($this->compilationDataReaderMock, $this->compilationDataWriterMock);
    }

    public function testConvertFileExists()
    {
        $expectedNewFileType = 'NEW_EXT';
        $expectedNewFile = $this->createMock(File::class);
        $expectedNewFile->method('exists')
            ->willReturn(true);
        $expectedNewFile->expects($this->once())
            ->method('delete');
        $this->compilationDataWriterMock->method('getOutputFileType')
            ->willReturn($expectedNewFileType);
        $this->directoryMock->method('getFile')
            ->with("BASE_NAME.{$expectedNewFileType}")
            ->willReturn($expectedNewFile);
        $result = $this->object->convert($this->fileMock, $this->directoryMock);
        $this->assertSame($expectedNewFile, $result, 'Method must return test file converted to PHP.');
    }

    public function testConvertFileDontExist()
    {
        $expectedNewFileType = 'NEW_EXT';
        $expectedPhpFile = $this->createMock(File::class);
        $expectedPhpFile->method('exists')
            ->willReturn(false);
        $expectedPhpFile->expects($this->never())
            ->method('delete');
        $this->compilationDataWriterMock->method('getOutputFileType')
            ->willReturn($expectedNewFileType);
        $this->directoryMock->method('getFile')
            ->with("BASE_NAME.{$expectedNewFileType}")
            ->willReturn($expectedPhpFile);
        $result = $this->object->convert($this->fileMock, $this->directoryMock);
        $this->assertSame($expectedPhpFile, $result, 'Method must return test file converted to PHP.');
    }
}
