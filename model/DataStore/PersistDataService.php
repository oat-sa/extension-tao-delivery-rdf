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
use oat\taoQtiTest\models\export\Formats\Package2p2\TestPackageExport;
use Throwable;
use ZipArchive;

class PersistDataService extends ConfigurableService
{
    public const  OPTION_EXPORTER_SERVICE = 'exporter_service';
    public const  OPTION_ZIP_ARCHIVE_SERVICE = 'zipArchive';

    private const PACKAGE_FILENAME = 'QTIPackage';
    private const ZIP_EXTENSION = '.zip';
    private const METADATA_MAP = [
        'deliveryMetaData',
        'testMetaData',
        'itemMetaData',
    ];

    /**
     * @throws Throwable
     * @throws common_Exception
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    public function persist(array $params): void
    {
        $this->persistArchive($params[ProcessDataService::PARAM_RESOURCE_ID], $params);
    }

    /**
     * @throws common_exception_NotFound
     * @throws common_exception_Error
     */
    public function remove(array $params): void
    {
        $this->removeArchive(
            $params[ProcessDataService::PARAM_RESOURCE_ID],
            $params[ProcessDataService::PARAM_FILE_SYSTEM_ID],
            $this->getTenantId($params),
        );
    }

    /**
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    private function getDataStoreFilesystem(string $fileSystemId): FileSystem
    {
        return $this->getFileSystemManager()->getFileSystem($fileSystemId);
    }

    private function getFolderName(string $deliveryOrTestId): string
    {
        return tao_helpers_Uri::encode($deliveryOrTestId);
    }

    /**
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    private function removeArchive(string $deliveryOrTestId, string $fileSystemId, string $tenantId): void
    {
        // There is a bug for gcp storage adapter - when deleting a dir with only one file exception is thrown
        // This is why the file itself is removed first
        $zipFileName = $this->getZipFileName($deliveryOrTestId, $tenantId);
        if ($this->getDataStoreFilesystem($fileSystemId)->has($zipFileName)) {
            $this->getDataStoreFilesystem($fileSystemId)->delete($zipFileName);
        }

        $directoryPath = $this->getZipFileDirectory($deliveryOrTestId, $tenantId);
        if ($this->getDataStoreFilesystem($fileSystemId)->has($directoryPath)) {
            $this->getDataStoreFilesystem($fileSystemId)->deleteDir($directoryPath);
        }
    }

    /**
     * @throws Throwable
     * @throws common_Exception
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    private function persistArchive(string $deliveryOrTestId, array $params): void
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

            $this->moveExportedZipTest($folder, $deliveryOrTestId, $params);
        } finally {
            $tempDir->removeDirectory($folder);
        }
    }

    /**
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    private function moveExportedZipTest(string $folder, string $deliveryOrTestId, array $params): void
    {
        $zipFiles = glob(
            sprintf('%s%s*%s', $folder, self::PACKAGE_FILENAME, self::ZIP_EXTENSION)
        );

        $fileSystemId = $params[ProcessDataService::PARAM_FILE_SYSTEM_ID];

        if (!empty($zipFiles)) {
            foreach ($zipFiles as $zipFile) {
                $zipFileName = $this->getZipFileName($deliveryOrTestId, $this->getTenantId($params));
                $this->addMetadataToZipFile($zipFile, $params);

                $contents = file_get_contents($zipFile);

                if ($this->getDataStoreFilesystem($fileSystemId)->has($zipFileName)) {
                    $this->getDataStoreFilesystem($fileSystemId)->update(
                        $zipFileName,
                        $contents
                    );
                } else {
                    $this->getDataStoreFilesystem($fileSystemId)->write(
                        $zipFileName,
                        $contents
                    );
                }
            }
        }
    }

    private function getTenantId(array $params): string
    {
        if (!empty($params[ProcessDataService::PARAM_TENANT_ID])) {
            return $params[ProcessDataService::PARAM_TENANT_ID];
        }

        if (!empty($params[ProcessDataService::PARAM_FIRST_TENANT_ID])) {
            return $params[ProcessDataService::PARAM_FIRST_TENANT_ID];
        }

        return "";
    }

    private function getTestExporter(): ExporterInterface
    {
        $exporter = $this->getOption(self::OPTION_EXPORTER_SERVICE);

        if ($exporter) {
            return $exporter;
        }

        return new TestPackageExport();
    }

    private function getFileSystemManager(): FileSystemService
    {
        return $this->getServiceLocator()->get(FileSystemService::SERVICE_ID);
    }

    private function getZipFileName(string $deliveryOrTestId, string $tenantId): string
    {
        return sprintf(
            '%s%s%s',
            $this->getZipFileDirectory($deliveryOrTestId, $tenantId),
            self::PACKAGE_FILENAME,
            self::ZIP_EXTENSION
        );
    }

    private function getZipFileDirectory(string $deliveryOrTestId, string $tenantId): string
    {
        return sprintf(
            '%s-%s%s',
            $this->getFolderName($deliveryOrTestId),
            $tenantId,
            DIRECTORY_SEPARATOR,
        );
    }

    private function addMetadataToZipFile(string $zipFile, array $metaData): void
    {
        $zipArchive = $this->getZipArchive();

        $zipArchive->open($zipFile);

        foreach (self::METADATA_MAP as $metadataName) {
            if (!empty($metaData[$metadataName])) {
                $this->saveMetaData($zipArchive, $metadataName . '.json', json_encode($metaData[$metadataName]));
            }
        }

        $zipArchive->close();
    }

    private function saveMetaData(ZipArchive $zipFile, string $fileNameToAdd, string $content): void
    {
        $zipFile->addFromString($fileNameToAdd, $content);
    }

    private function getZipArchive(): ZipArchive
    {
        $zipArchive = $this->getOption(self::OPTION_ZIP_ARCHIVE_SERVICE);

        return $zipArchive ?? new ZipArchive();
    }
}
