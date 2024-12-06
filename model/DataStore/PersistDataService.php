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

    /**
     * @throws Throwable
     * @throws common_Exception
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    public function persist(ResourceSyncDTO $resourceSyncDTO): void
    {
        $this->persistArchive($resourceSyncDTO);
    }

    /**
     * @throws common_exception_NotFound
     * @throws common_exception_Error
     */
    public function remove(ResourceSyncDTO $resourceSyncDTO): void
    {
        $this->removeArchive(
            $resourceSyncDTO->getResourceId(),
            $resourceSyncDTO->getFileSystemId(),
            $this->getTenantId($resourceSyncDTO),
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

    private function getFolderName(string $resourceId): string
    {
        return tao_helpers_Uri::encode($resourceId);
    }

    /**
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    private function removeArchive(string $resourceId, string $fileSystemId, string $tenantId): void
    {
        // There is a bug for gcp storage adapter - when deleting a dir with only one file exception is thrown
        // This is why the file itself is removed first
        $zipFileName = $this->getZipFileName($resourceId, $tenantId);
        if ($this->getDataStoreFilesystem($fileSystemId)->has($zipFileName)) {
            $this->getDataStoreFilesystem($fileSystemId)->delete($zipFileName);
        }

        $directoryPath = $this->getZipFileDirectory($resourceId, $tenantId);
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
    private function persistArchive(ResourceSyncDTO $resourceSyncDTO): void
    {
        /** @var FileHelperService $tempDir */
        $tempDir = $this->getServiceLocator()->get(FileHelperService::class);
        $folder = $tempDir->createTempDir();

        try {
            $this->getTestExporter()->export(
                [
                    'filename' => self::PACKAGE_FILENAME,
                    'instances' => $resourceSyncDTO->getTestUri(),
                    'uri' => $resourceSyncDTO->getTestUri()
                ],
                $folder
            );

            $this->moveExportedZipTest($folder, $resourceSyncDTO->getResourceId(), $resourceSyncDTO);
        } finally {
            $tempDir->removeDirectory($folder);
        }
    }

    /**
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    private function moveExportedZipTest(
        string $folder,
        string $resourceId,
        ResourceSyncDTO $resourceSyncDTO
    ): void {
        $zipFiles = glob(
            sprintf('%s%s*%s', $folder, self::PACKAGE_FILENAME, self::ZIP_EXTENSION)
        );

        $fileSystemId = $resourceSyncDTO->getFileSystemId();

        if (!empty($zipFiles)) {
            foreach ($zipFiles as $zipFile) {
                $zipFileName = $this->getZipFileName($resourceId, $this->getTenantId($resourceSyncDTO));
                $this->addMetadataToZipFile($zipFile, $resourceSyncDTO);

                $contents = file_get_contents($zipFile);

                $this->getDataStoreFilesystem($fileSystemId)->write(
                    $zipFileName,
                    $contents
                );
            }
        }
    }

    private function getTenantId(ResourceSyncDTO $resourceSyncDTO): string
    {
        if (!empty($resourceSyncDTO->getTenantId())) {
            return $resourceSyncDTO->getTenantId();
        }

        if (!empty($resourceSyncDTO->getFirstTenantId())) {
            return $resourceSyncDTO->getFirstTenantId();
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

    private function getZipFileName(string $resourceId, string $tenantId): string
    {
        return sprintf(
            '%s%s%s',
            $this->getZipFileDirectory($resourceId, $tenantId),
            self::PACKAGE_FILENAME,
            self::ZIP_EXTENSION
        );
    }

    private function getZipFileDirectory(string $resourceId, string $tenantId): string
    {
        return sprintf(
            '%s-%s%s',
            $this->getFolderName($resourceId),
            $tenantId,
            DIRECTORY_SEPARATOR,
        );
    }

    private function addMetadataToZipFile(string $zipFile, ResourceSyncDTO $resourceSyncDTO): void
    {
        $zipArchive = $this->getZipArchive();

        $zipArchive->open($zipFile);

        foreach ($resourceSyncDTO->getMetadata() as $metadataName => $metadata) {
            if (!empty($metadata)) {
                $this->saveMetaData($zipArchive, $metadataName . '.json', json_encode($metadata));
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
