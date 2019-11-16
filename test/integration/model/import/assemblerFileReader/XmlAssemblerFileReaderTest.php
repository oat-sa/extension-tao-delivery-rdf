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
 *
 * @author Oleksandr Zagovorychev <zagovorichev@gmail.com>
 */
namespace oat\taoDeliveryRdf\test\integration\model\import\assemblerFileReader;


use GuzzleHttp\Psr7\Stream;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\oatbox\filesystem\File;
use oat\taoDeliveryRdf\model\import\assemblerDataProviders\assemblerFileReaders\XmlAssemblerFileReader;
use oat\taoQtiTest\models\PhpCodeCompilationDataService;
use oat\taoQtiTest\models\XmlCompilationDataService;
use qtism\data\QtiComponent;
use tao_models_classes_service_StorageDirectory;

class XmlAssemblerFileReaderTest extends TestCase
{
    public function testStream()
    {
        $reader = new XmlAssemblerFileReader();
        /** @var File|MockObject $file */
        $file = $this->createMock(File::class);
        $file->method('readPsrStream')->willReturn('stream');
        /** @var tao_models_classes_service_StorageDirectory|MockObject $directory */
        $directory = $this->createMock(tao_models_classes_service_StorageDirectory::class);

        $stream = $reader->getFileStream($file, $directory);
        $this->assertSame('stream', $stream);
        $this->assertSame($file, $reader->getFile());
    }

    public function testClean()
    {
        $reader = new XmlAssemblerFileReader();
        /** @var File|MockObject $file */
        $file = $this->createMock(File::class);
        $streamMock = $this->createMock(Stream::class);
        $streamMock->expects($this->once())->method('close');
        $file->method('readPsrStream')->willReturn($streamMock);
        /** @var tao_models_classes_service_StorageDirectory|MockObject $directory */
        $directory = $this->createMock(tao_models_classes_service_StorageDirectory::class);

        $reader->getFileStream($file, $directory);
        $this->assertSame($file, $reader->getFile());
        $reader->clean();
        $this->assertNull($reader->getFile());
        $reader->clean();
        $reader->clean();
    }

    public function testStreamWithPhpCompactTest()
    {
        $phpDataServiceMock = $this->createMock(PhpCodeCompilationDataService::class);
        $objectMock = $this->createMock(QtiComponent::class);
        $phpDataServiceMock->expects($this->once())->method('readCompilationData')->willReturn($objectMock);

        $xmlDataServiceMock = $this->createMock(XmlCompilationDataService::class);
        $xmlDataServiceMock->expects($this->once())->method('writeCompilationData');

        $reader = new XmlAssemblerFileReader([
            XmlAssemblerFileReader::OPTION_PHP_CODE_COMPILATION_DATA_SERVICE => $phpDataServiceMock,
            XmlAssemblerFileReader::OPTION_XML_CODE_COMPILATION_DATA_SERVICE => $xmlDataServiceMock,
        ]);
        /** @var File|MockObject $file */
        $file = $this->createMock(File::class);
        $stream1Mock = $this->createMock(Stream::class);
        // don't generate stream, just rewrite the file
        $stream1Mock->expects($this->never())->method('close');
        $file->method('readPsrStream')->willReturn($stream1Mock);
        $file->method('getPrefix')->willReturn('compact-test.php');
        $file->method('getBasename')->willReturn('fileBaseName.php');

        /** @var tao_models_classes_service_StorageDirectory|MockObject $directory */
        $directory = $this->createMock(tao_models_classes_service_StorageDirectory::class);
        $xmlFile = $this->createMock(File::class);
        $xmlFile->expects($this->once())->method('delete');
        $stream2Mock = $this->createMock(Stream::class);
        $stream2Mock->expects($this->once())->method('close');
        $xmlFile->method('readPsrStream')->willReturn($stream2Mock);
        $xmlFile->method('getPrefix')->willReturn('compact-test.xml');

        $directory->method('getFile')->willReturnCallback(static function ($name) use ($xmlFile) {
            $xmlFile->method('getBasename')->willReturn($name);
            return $xmlFile;
        });

        $stream = $reader->getFileStream($file, $directory);
        $this->assertSame($stream2Mock, $stream);
        $this->assertSame('fileBaseName.xml', $reader->getFile()->getBasename());

        $reader->clean();
    }

    /**
     * Don't replace xml when it is xml
     */
    public function testStreamWithXmlCompactTest()
    {
        $phpDataServiceMock = $this->createMock(PhpCodeCompilationDataService::class);
        $objectMock = $this->createMock(QtiComponent::class);
        $phpDataServiceMock->expects($this->never())->method('readCompilationData')->willReturn($objectMock);

        $xmlDataServiceMock = $this->createMock(XmlCompilationDataService::class);
        $xmlDataServiceMock->expects($this->never())->method('writeCompilationData');

        $reader = new XmlAssemblerFileReader([
            XmlAssemblerFileReader::OPTION_PHP_CODE_COMPILATION_DATA_SERVICE => $phpDataServiceMock,
            XmlAssemblerFileReader::OPTION_XML_CODE_COMPILATION_DATA_SERVICE => $xmlDataServiceMock,
        ]);
        /** @var File|MockObject $file */
        $file = $this->createMock(File::class);
        $file->method('readPsrStream')->willReturn('stream');
        $file->method('getPrefix')->willReturn('compact-test.xml');
        $file->method('getBasename')->willReturn('fileBaseName.xml');

        /** @var tao_models_classes_service_StorageDirectory|MockObject $directory */
        $directory = $this->createMock(tao_models_classes_service_StorageDirectory::class);

        $stream = $reader->getFileStream($file, $directory);
        $this->assertSame('stream', $stream);
        $this->assertSame('fileBaseName.xml', $reader->getFile()->getBasename());
    }
}
