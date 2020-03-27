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

use Generator;
use ArrayIterator;
use oat\oatbox\filesystem\File;
use Psr\Http\Message\StreamInterface;
use tao_models_classes_service_StorageDirectory;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\taoDeliveryRdf\model\assembly\AssemblyFilesReader;
use oat\taoDeliveryRdf\model\assembly\CompiledTestConverterService;

class AssemblyFilesReaderTest extends TestCase
{
    /**
     * @var AssemblyFilesReader
     */
    private $object;
    protected function setUp(): void
    {
        parent::setUp();
        $this->object = new AssemblyFilesReader();
    }

    public function testGetFilesEmptyDirectoryReturnsEmptyGenerator()
    {
        $expectedFilesCount = 0;
        $iterator = $this->getFilesIterator([]);
        $directoryMock = $this->createMock(tao_models_classes_service_StorageDirectory::class);
        $directoryMock->method('getFlyIterator')
            ->willReturn($iterator);
        $result = $this->object->getFiles($directoryMock);
        $this->assertInstanceOf(Generator::class, $result);
        $this->assertEquals($expectedFilesCount, count(iterator_to_array($result)), 'Number of returned files for empty directory must be 0.');
    }

    public function testGetFilesReturnsGeneratorWithFilesStreams()
    {
        $expectedFilePath1 = 'file/prefix1';
        $expectedFilePath2 = 'file/prefix2';
        $iterator = $this->getFilesIterator([$expectedFilePath1, $expectedFilePath2]);
        $directoryMock = $this->createMock(tao_models_classes_service_StorageDirectory::class);
        $directoryMock->method('getFlyIterator')
            ->willReturn($iterator);
        $result = $this->object->getFiles($directoryMock);
        $this->assertInstanceOf(Generator::class, $result, 'Files reader must return an instance type of Generator.');
        $this->assertEquals($expectedFilePath1, $result->key(), 'Returned file path must be as expected.');
        $this->assertInstanceOf(StreamInterface::class, $result->current(), 'Returned iterator value must be an instance of StreamInterface.');
        $result->next();
        $this->assertEquals($expectedFilePath2, $result->key(), 'Returned file path must be as expected.');
        $this->assertInstanceOf(StreamInterface::class, $result->current(), 'Returned iterator value must be an instance of StreamInterface.');
    }

    public function testGetFilesConvertsCompiledTestFile()
    {
        $expectedFilePath = 'compact-test.EXT2';
        $originalTestFileMock = $this->createMock(File::class);
        $originalTestFileMock->method('getBasename')
            ->willReturn('compact-test.EXT1');
        $convertedTestFileMock = $this->createMock(File::class);
        $convertedTestFileMock->method('getPrefix')
            ->willReturn($expectedFilePath);
        $convertedTestFileMock->method('readPsrStream')
            ->willReturn($this->createMock(StreamInterface::class));
        $testConverterMock = $this->createMock(CompiledTestConverterService::class);
        $testConverterMock->method('convert')
            ->willReturn($convertedTestFileMock);
        $this->object->setCompiledTestConverter($testConverterMock);
        $directoryMock = $this->createMock(tao_models_classes_service_StorageDirectory::class);
        $directoryMock->method('getFlyIterator')
            ->willReturn(new ArrayIterator([$originalTestFileMock]));
        $result = $this->object->getFiles($directoryMock);
        $this->assertInstanceOf(Generator::class, $result, 'Files reader must return an instance type of Generator.');
        $this->assertEquals($expectedFilePath, $result->key(), 'Returned converted file path must be as expected.');
        $this->assertInstanceOf(StreamInterface::class, $result->current(), 'Returned iterator value must be an instance of StreamInterface.');
    }

    /**
     * @param array $filesPaths
     * @return ArrayIterator
     */
    private function getFilesIterator(array $filesPaths)
    {
        $files = [];
        foreach ($filesPaths as $filePath) {
            $fileMock = $this->createMock(File::class);
            $fileMock->method('getPrefix')
                ->willReturn($filePath);
            $fileMock->method('readPsrStream')
                ->willReturn($this->createMock(StreamInterface::class));
            $files[] = $fileMock;
        }

        return new ArrayIterator($files);
    }
}
