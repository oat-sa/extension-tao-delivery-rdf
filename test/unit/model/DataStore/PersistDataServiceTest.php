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

use oat\generis\test\TestCase;
use oat\oatbox\filesystem\FileSystem;
use oat\oatbox\filesystem\FileSystemService;
use oat\tao\helpers\FileHelperService;
use oat\taoDeliveryRdf\model\DataStore\PersistDataService;
use taoQtiTest_models_classes_export_TestExport22;

class PersistDataServiceTest extends TestCase
{
    /** @var PersistDataService */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $filesystemService = $this->createMock(FileSystemService::class);
        $filesystemHelper = $this->createMock(FileHelperService::class);
        $filesystemHelper->method('createTempDir')
            ->willReturn('bogusTestDirLocation');
        $exporterHelper = $this->createMock(taoQtiTest_models_classes_export_TestExport22::class);
        $exporterHelper->method('export')->willReturn(true);


        $fileSystem = $this->createMock(FileSystem::class);
        $fileSystem->method('has')->willReturn(true);
        $fileSystem->method('write')->willReturn(true);

        $filesystemService->method('getFileSystem')
            ->willReturn($fileSystem);

        $serviceLocator = $this->getServiceLocatorMock([
            FileSystemService::SERVICE_ID => $filesystemService,
            FileHelperService::class => $filesystemHelper,
        ]);

        $this->subject = new PersistDataService(
            [],
            $exporterHelper
        );

        $this->subject->setServiceLocator($serviceLocator);
    }

    /**
     * @dataProvider provideDataForPersist
     *
     */
    public function testPersist($params): void
    {
        $this->subject->persist($params);
        $this->assertTrue(true);
    }


    /**
     * @return array[]
     */
    public function provideDataForPersist(): array
    {
        return [
            [
                ['deliveryId' => 'bogus', 'testUri' => 'testBogus'], ''
            ]
        ];
    }
}
