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
use tao_models_classes_export_ExportHandler as ExporterInterface;
use taoQtiTest_models_classes_export_TestExport22;
use Throwable;
use ZipArchive;

class PersistDataService extends ConfigurableService
{
    private const DATA_STORE = 'dataStore';
    private const DELIVERY_META_DATA_JSON = 'deliveryMetaData.json';
    private const TEST_META_DATA_JSON = 'testMetaData.json';
    private const ITEM_META_DATA_JSON = 'itemMetaData.json';
    private const PACKAGE_FILENAME = 'QTIPackage';
    private const ZIP_EXTENSION = '.zip';
    public const  OPTION_EXPORTER_SERVICE = 'exporter_service';

    /**
     * @throws Throwable
     * @throws common_Exception
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    public function persist(array $params): void
    {
        $this->persistArchive($params['deliveryId'], $params);
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

    /**
     * @throws Throwable
     * @throws common_Exception
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    private function persistArchive(string $deliveryId, array $params): void
    {
        /** @var FileHelperService $tempDir */
        $tempDir = $this->getServiceLocator()->get(FileHelperService::class);
        $folder = $tempDir->createTempDir();

        try {
            $this->getTestExporter()->export(
                [
                    'filename' => self::PACKAGE_FILENAME,
                    'instances' => $params['testUri'],
                    'uri' => $params['testUri']
                ],
                $folder
            );

            $this->moveExportedZipTest($folder, $deliveryId, $params);
        } finally {
            $tempDir->removeDirectory($folder);
        }
    }

    /**
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    private function moveExportedZipTest(string $folder, string $deliveryId, array $params): void
    {
        $zipFiles = glob(
            sprintf('%s%s*%s', $folder, self::PACKAGE_FILENAME, self::ZIP_EXTENSION)
        );

        if (!empty($zipFiles)) {
            foreach ($zipFiles as $zipFile) {
                $zipFileName = $this->getZipFileName($deliveryId);
                $this->addMetaDataToArchive($zipFile, $params);
                $contents = file_get_contents($zipFile);

                if ($this->getDataStoreFilesystem()->has($zipFileName)) {
                    $this->getDataStoreFilesystem()->update(
                        $zipFileName,
                        $contents
                    );
                } else {
                    $this->getDataStoreFilesystem()->write(
                        $zipFileName,
                        $contents
                    );
                }
            }
        }
    }

    private function getTestExporter(): ExporterInterface
    {
        $exporter = $this->getOption(self::OPTION_EXPORTER_SERVICE);

        if ($exporter) {
            return $exporter;
        }

        return new taoQtiTest_models_classes_export_TestExport22();
    }

    private function getFileSystemManager(): FileSystemService
    {
        return $this->getServiceLocator()->get(FileSystemService::SERVICE_ID);
    }

    private function addMetaDataFile(ZipArchive $zipFile, string $fileNameToAdd, string $content): bool
    {
        return $zipFile->addFromString($fileNameToAdd, $content);
    }

    private function addMetaDataToArchive(string $zipFile, array $metaData): void
    {
        $zipArchive = new ZipArchive();

        $zipArchive->open($zipFile);

        $this->addMetaDataFile($zipArchive, self::DELIVERY_META_DATA_JSON, json_encode($metaData['deliveryMetaData']));
        $this->addMetaDataFile($zipArchive, self::TEST_META_DATA_JSON, json_encode($metaData['testMetaData']));
        $this->addMetaDataFile($zipArchive, self::ITEM_META_DATA_JSON, json_encode($metaData['itemMetaData']));

        $zipArchive->close();
    }

    private function getZipFileName(string $deliveryId): string
    {
        return sprintf(
            '%s%s%s%s',
            $this->getFolderName($deliveryId),
            DIRECTORY_SEPARATOR,
            self::PACKAGE_FILENAME,
            self::ZIP_EXTENSION
        );
    }
}
