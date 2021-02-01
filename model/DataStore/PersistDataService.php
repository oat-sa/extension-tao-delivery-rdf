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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\model\DataStore;

use common_Exception;
use common_exception_Error;
use common_exception_NotFound;
use oat\oatbox\filesystem\FileSystem;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\service\ConfigurableService;
use oat\tao\helpers\FileHelperService;
use tao_helpers_Uri;
use taoQtiTest_models_classes_export_TestExport22;
use Throwable;

class PersistDataService extends ConfigurableService
{
    private const DATA_STORE = 'dataStore';
    private const DELIVERY_META_DATA_JSON = 'deliveryMetaData.json';
    private const TEST_META_DATA_JSON = 'testMetaData.json';
    private const ITEM_META_DATA_JSON = 'itemMetaData.json';
    private const PACKAGE_FILENAME = 'QTIPackage';
    private const ZIP_EXTENSION = '.zip';

    /**
     * @throws Throwable
     * @throws common_Exception
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    public function persist(array $params): void
    {
        $fileSystem = $this->getDataStoreFilesystem();
        $folder = $this->getFolderName($params['deliveryId']);

        $this->persistData($fileSystem, $folder, self::DELIVERY_META_DATA_JSON, $params['deliveryMetaData']);
        $this->persistData($fileSystem, $folder, self::TEST_META_DATA_JSON, $params['testMetaData']);
        $this->persistData($fileSystem, $folder, self::ITEM_META_DATA_JSON, $params['itemMetaData']);
        $this->persistExportedTest($params['deliveryId'], $params['testUri']);
    }

    /**
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    private function getDataStoreFilesystem(): FileSystem
    {
        return $this->getFileSystemManager()->getFileSystem(self::DATA_STORE);
    }


    private function getFolderName(string $deliveryId): string
    {
        return tao_helpers_Uri::encode($deliveryId);
    }

    private function persistData(FileSystem $fileSystem, string $folder, string $fileName, $params): void
    {
        if (!$fileSystem->has($folder . DIRECTORY_SEPARATOR . $fileName)) {
            $fileSystem->write($folder . DIRECTORY_SEPARATOR . $fileName, json_encode($params));
        }
    }

    /**
     * @throws Throwable
     * @throws common_Exception
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    private function persistExportedTest(string $deliveryId, string $testUri): void
    {
        /** @var FileHelperService $tempDir */
        $tempDir = $this->getServiceLocator()->get(FileHelperService::class);
        $folder = $tempDir->createTempDir();

        $testExporter = $this->getTestExporter();
        try {
            $testExporter->export(
                [
                    'filename' => self::PACKAGE_FILENAME,
                    'instances' => $testUri,
                    'uri' => $testUri
                ],
                $folder
            );

            $this->moveExportedZipTest($folder, $deliveryId);
        } finally {
            $tempDir->removeDirectory($folder);
        }
    }

    /**
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    private function moveExportedZipTest(string $folder, string $deliveryId): void
    {
        $zipFiles = glob(
            sprintf('%s%s*%s', $folder, self::PACKAGE_FILENAME, self::ZIP_EXTENSION)
        );

        if (!empty($zipFiles)) {
            foreach ($zipFiles as $zipFile) {
                $contents = file_get_contents($zipFile);
                $fileName = sprintf(
                    '%s%s%s%s',
                    $this->getFolderName($deliveryId),
                    DIRECTORY_SEPARATOR,
                    self::PACKAGE_FILENAME,
                    self::ZIP_EXTENSION
                );
                if ($this->getDataStoreFilesystem()->has($fileName)) {
                    $this->getDataStoreFilesystem()->update(
                        $fileName,
                        $contents
                    );
                } else {
                    $this->getDataStoreFilesystem()->write(
                        $fileName,
                        $contents
                    );
                }
            }
        }
    }

    private function getTestExporter(): taoQtiTest_models_classes_export_TestExport22
    {
        return new taoQtiTest_models_classes_export_TestExport22();
    }

    private function getFileSystemManager(): FileSystemService
    {
        return $this->getServiceLocator()->get(FileSystemService::SERVICE_ID);
    }
}
