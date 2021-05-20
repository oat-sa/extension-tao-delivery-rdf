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

use oat\generis\test\TestCase;
use oat\taoDeliveryRdf\model\DataStore\ProcessDataService;
use PHPUnit\Framework\MockObject\MockObject;
use ZipArchive;

class ProcessDataServiceTest extends TestCase
{
    /** @var MockObject|ZipArchive */
    private $zipArchive;

    /** @var ProcessDataService */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->zipArchive = $this->createMock(ZipArchive::class);

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
