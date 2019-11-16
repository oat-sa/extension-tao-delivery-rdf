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


use common_exception_Error;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\oatbox\filesystem\File;
use oat\taoDeliveryRdf\model\import\assemblerDataProviders\assemblerFileReaders\AssemblerFileReaderCollection;
use oat\taoDeliveryRdf\model\import\assemblerDataProviders\assemblerFileReaders\AssemblerFileReaderInterface;
use tao_models_classes_service_StorageDirectory;

class AssemblerFileReaderCollectionTest extends TestCase
{
    /**
     * @expectedExceptionMessage Readers are not configured for the AssemblerFileReaderCollection
     * @expectedException common_exception_Error
     * @throws common_exception_Error
     */
    public function testNoConfiguration()
    {
        $reader = new AssemblerFileReaderCollection();
        $reader->getReaders();
    }

    /**
     * @expectedException common_exception_Error
     * @throws common_exception_Error
     */
    public function testWrongConfiguration()
    {
        $reader = new AssemblerFileReaderCollection([
            AssemblerFileReaderCollection::OPTION_FILE_READERS => ['reader']
        ]);
        $reader->getReaders();
    }

    /**
     * @throws common_exception_Error
     */
    public function testStream()
    {
        $reader1 = $this->createMock(AssemblerFileReaderInterface::class);
        $reader1->method('getFileStream')->willReturn('stream1');

        $file1 = $this->createMock(File::class);
        $file1->method('getBasename')->willReturn('1');
        $reader1->method('getFile')->willReturn($file1);

        $reader2 = $this->createMock(AssemblerFileReaderInterface::class);
        $reader2->method('getFileStream')->willReturn('stream2');

        $file2 = $this->createMock(File::class);
        $file2->method('getBasename')->willReturn('2');
        $reader2->method('getFile')->willReturn($file2);

        $reader = new AssemblerFileReaderCollection([
            AssemblerFileReaderCollection::OPTION_FILE_READERS => [$reader1, $reader2]
        ]);
        /** @var File|MockObject $file */
        $file = $this->createMock(File::class);
        $file->method('readPsrStream')->willReturn('stream');
        $file->method('getBasename')->willReturn('0');
        /** @var tao_models_classes_service_StorageDirectory|MockObject $directory */
        $directory = $this->createMock(tao_models_classes_service_StorageDirectory::class);

        $stream = $reader->getFileStream($file, $directory);
        $this->assertSame('stream2', $stream);
        $this->assertSame('2', $reader->getFile()->getBasename());
    }

    /**
     * @throws common_exception_Error
     */
    public function testClean()
    {
        $reader1 = $this->createMock(AssemblerFileReaderInterface::class);
        $reader1->expects($this->once())->method('clean');
        $reader2 = $this->createMock(AssemblerFileReaderInterface::class);
        $reader2->expects($this->once())->method('clean');
        $reader3 = $this->createMock(AssemblerFileReaderInterface::class);
        $reader3->expects($this->once())->method('clean');

        $reader = new AssemblerFileReaderCollection([
            AssemblerFileReaderCollection::OPTION_FILE_READERS => [$reader1, $reader2, $reader3]
        ]);
        $reader->clean();
    }
}
