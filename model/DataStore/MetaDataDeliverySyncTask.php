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

use common_exception_Error;
use common_exception_NotFound;
use JsonSerializable;
use oat\oatbox\extension\AbstractAction;
use oat\oatbox\filesystem\FileSystem;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\reporting\Report;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\exception\InvalidServiceManagerException;
use oat\tao\helpers\FileHelperService;
use oat\tao\model\taskQueue\QueueDispatcher;
use taoQtiTest_models_classes_export_TestExport22;
use Throwable;

class MetaDataDeliverySyncTask extends AbstractAction implements JsonSerializable
{
    private const MAX_TRIES = 1;

    private const DATA_STORE = 'dataStore';
    private const DELIVERY_META_DATA_JSON = 'deliveryMetaData.json';
    private const TEST_META_DATA_JSON = 'testMetaData.json';
    private const ITEM_META_DATA_JSON = 'itemMetaData.json';
    private const PACKAGE_FILENAME = 'QTIPackage';
    private const ZIP_EXTENSION = '.zip';

    /** @var bool */
    private $error;

    /**
     * @throws InvalidServiceManagerException
     */
    public function __invoke($params)
    {
        $report = new Report(Report::TYPE_SUCCESS);
        $this->error = true;

        if (!$this->error) {
            $report->setMessage('Success MetaData syncing for delivery: ' . $params['deliveryId']);
        }
        if ($this->error && $params['count'] < self::MAX_TRIES) {
            $params['count']++;
            try {
                $this->writeMetaData($params);
                $this->persistExportedTest($params['deliveryId'], $params['testUri']);
                $this->requeueTask($params);
                $report->setType(Report::TYPE_ERROR);
                $report->setMessage('Failing MetaData syncing for delivery: ' . $params['deliveryId']);
                $this->error = false;
            } catch (Throwable $exception) {
                $this->logError(sprintf(
                    'Failing MetaData syncing for delivery: %s with message: %s',
                    $params['deliveryId'],
                    $exception->getMessage()
                ));
            }
        }

        return $report;
    }

    public function jsonSerialize()
    {
        return __CLASS__;
    }

    /**
     * @throws InvalidServiceManagerException
     */
    private function getQueueDispatcher(): ConfigurableService
    {
        return $this->getServiceManager()->get(QueueDispatcher::SERVICE_ID);
    }

    /**
     * @param $params
     * @throws InvalidServiceManagerException
     */
    private function requeueTask($params): void
    {
        /** @var QueueDispatcher $queueDispatcher */
        $queueDispatcher = $this->getQueueDispatcher();
        $queueDispatcher->createTask(
            $this,
            $params,
            __('Continue try to sync GCP of delivery "%s".', $params['deliveryId'])
        );
    }

    private function getFileSystemManager(): FileSystemService
    {
        return $this->getServiceLocator()->get(FileSystemService::SERVICE_ID);
    }

    private function getFolderName(string $deliveryId): string
    {
        return hash('sha1', $deliveryId);
    }

    private function writeMetaData($params): void
    {
        $fileSystem = $this->getDataStoreFilesystem();
        $folder = $this->getFolderName($params['deliveryId']);
        if (!$fileSystem->has($folder . DIRECTORY_SEPARATOR . self::DELIVERY_META_DATA_JSON)) {
            $this->persistData($fileSystem, $folder, self::DELIVERY_META_DATA_JSON, $params['deliveryMetaData']);
        }
        if (!$fileSystem->has($folder . DIRECTORY_SEPARATOR . self::TEST_META_DATA_JSON)) {
            $this->persistData($fileSystem, $folder, self::TEST_META_DATA_JSON, $params['testMetaData']);
        }
        if (!$fileSystem->has($folder . DIRECTORY_SEPARATOR . self::ITEM_META_DATA_JSON)) {
            $this->persistData($fileSystem, $folder, self::ITEM_META_DATA_JSON, $params['itemMetaData']);
        }
    }

    private function persistData(FileSystem $fileSystem, string $folder, string $fileName, $params): void
    {
        $fileSystem->write($folder . DIRECTORY_SEPARATOR . $fileName, json_encode($params));
    }

    private function getTestExporter(): taoQtiTest_models_classes_export_TestExport22
    {
        return new taoQtiTest_models_classes_export_TestExport22();
    }

    private function persistExportedTest(string $deliveryId, string $testUri)
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

            $this->moveExportedZipTest($folder, $deliveryId, $tempDir);
        } catch (Throwable $exception) {
            $this->logError(
                'DataStore: An error has occurred while exporting the qti package ::' .
                $exception->getMessage()
            );
        }
    }

    /**
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    private function getDataStoreFilesystem(): FileSystem
    {
        return $this->getFileSystemManager()->getFileSystem(self::DATA_STORE);
    }

    /**
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     */
    private function moveExportedZipTest(string $folder, string $deliveryId, FileHelperService $tempDir): void
    {
        $zipFiles = glob(
            sprintf('%s%s*%s', $folder, self::PACKAGE_FILENAME, self::ZIP_EXTENSION)
        );

        if (!empty($zipFiles)) {
            foreach ($zipFiles as $zipFile) {
                $this->logDebug('Started to copy zip file: ' . $zipFile);
                $contents = file_get_contents($zipFile);
                $this->getDataStoreFilesystem()->write(
                    sprintf(
                        '%s%s%s%s',
                        $this->getFolderName($deliveryId),
                        DIRECTORY_SEPARATOR,
                        self::PACKAGE_FILENAME,
                        self::ZIP_EXTENSION
                    ),
                    $contents
                );
                $tempDir->removeDirectory($folder);
                $this->logDebug('Temporary extraction folder has been removed! Folder: ' . $folder);
            }
        }
    }
}
