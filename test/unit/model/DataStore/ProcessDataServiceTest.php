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
 * Copyright (c) 2021  (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\test\unit\model\DataStore;

use PHPUnit\Framework\TestCase;
use oat\taoDeliveryRdf\model\DataStore\ProcessDataService;
use ZipArchive;

class ProcessDataServiceTest extends TestCase
{
    private ZipArchiveForUnitTest $zipArchive;
    private ProcessDataService $subject;

    protected function setUp(): void
    {
        parent::setUp();

        //Need to remove all methods because of PHPUnit 8.5 doesn`t handles return union types used in PHP8
        $this->zipArchive = $this->getMockBuilder(ZipArchiveForUnitTest::class)
            ->onlyMethods(['open', 'close', 'addFromString'])
            ->getMock();

        $this->subject = new ProcessDataService(
            [ProcessDataService::OPTION_ZIP_ARCHIVE_SERVICE => $this->zipArchive]
        );
    }

    public function testProcess(): void
    {
        $zipFile = 'bogus/zipFile.zip';
        $this->zipArchive->expects($this->once())->method('open')->with($zipFile);
        $this->zipArchive->expects($this->exactly(3))->method('addFromString');
        $this->zipArchive->expects($this->once())->method('close');
        $metaData = [
            'deliveryMetaData' => 'deliveryMetaData',
            'testMetaData' => 'testMetaData',
            'itemMetaData' => 'itemMetaData',
        ];
        $this->subject->process($zipFile, $metaData);
    }
}

/**
 * Class needed to override methods form ZipArchive needed for this test.
 * Method open() in ZipArchive has UnionType return bool|int and therefore cant be mocked by PHPUnit in version lower
 * than 9 (currently 8.5 is installed)
 */
// @codingStandardsIgnoreStart
class ZipArchiveForUnitTest extends ZipArchive
{
    public function open($filename, $flags = null)
    {
    }

    public function close()
    {
    }

    public function addFromString($name, $content, $flags = 8192)
    {
    }
}
// @codingStandardsIgnoreEnd
