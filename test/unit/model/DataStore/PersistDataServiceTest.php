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

use oat\generis\test\ServiceManagerMockTrait;
use PHPUnit\Framework\TestCase;
use oat\oatbox\filesystem\FileSystem;
use oat\oatbox\filesystem\FileSystemService;
use oat\tao\helpers\FileHelperService;
use oat\taoDeliveryRdf\model\DataStore\PersistDataService;
use taoQtiTest_models_classes_export_TestExport22;

class PersistDataServiceTest extends TestCase
{
    use ServiceManagerMockTrait;

    private FileSystemService $filesystemService;
    private FileHelperService $filesystemHelper;
    private taoQtiTest_models_classes_export_TestExport22 $exporterHelper;
    private FileSystem $fileSystem;
    private PersistDataService $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystemService = $this->createMock(FileSystemService::class);
        $this->filesystemHelper = $this->createMock(FileHelperService::class);
        $this->exporterHelper = $this->createMock(taoQtiTest_models_classes_export_TestExport22::class);
        $this->fileSystem = $this->createMock(FileSystem::class);

        $serviceLocator = $this->getServiceManagerMock([
            FileSystemService::SERVICE_ID => $this->filesystemService,
            FileHelperService::class => $this->filesystemHelper,
        ]);

        $this->subject = new PersistDataService(
            [PersistDataService::OPTION_EXPORTER_SERVICE => $this->exporterHelper]
        );

        $this->subject->setServiceLocator($serviceLocator);
    }

    /**
     * @dataProvider provideDataForPersist
     */
    public function testPersist($params): void
    {
        $this->filesystemHelper->expects($this->once())->method('createTempDir')
            ->willReturn('bogusTestDirLocation');

        $this->exporterHelper->expects($this->once())->method('export')->willReturn(true);

        $this->subject->persist($params);
    }

    public function provideDataForPersist(): array
    {
        return [
            [
                [
                    'deliveryId' => 'bogus',
                    'testUri' => 'testBogus',
                    'deliveryMetaData' => [],
                    'testMetaData' => [],
                    'itemMetaData' => [],

                ], ''
            ]
        ];
    }
}
