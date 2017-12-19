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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoDeliveryRdf\model;

use common_Logger;
use core_kernel_classes_Property;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\filesystem\Directory;
use oat\oatbox\filesystem\File;
use oat\oatbox\filesystem\FileSystem;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\service\ConfigurableService;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoDeliveryRdf\model\event\DeliveryRemovedEvent;

class DeliveryArchiveService extends ConfigurableService implements \oat\taoDelivery\model\DeliveryArchiveService
{
    use OntologyAwareTrait;

    /** @var string */
    protected $tmpDir;

    /**
     * @param DeliveryCreatedEvent $event
     */
    public function catchDeliveryCreated(DeliveryCreatedEvent $event)
    {
        $compiledDelivery = $this->getResource($event->getDeliveryUri());

        try {
            $this->archive($compiledDelivery);
        } catch (DeliverArchiveExistingException $e) {
            common_Logger::i($e->getMessage());
        }
    }

    /**
     * @param DeliveryRemovedEvent $event
     */
    public function catchDeliveryRemoved(DeliveryRemovedEvent $event)
    {
        $compiledDelivery = $this->getResource($event->getDeliveryUri());

        $this->deleteArchive($compiledDelivery);
    }

    /**
     * @param string $compiledDelivery
     * @param bool $force
     * @return string
     * @throws DeliverArchiveExistingException
     */
    public function archive($compiledDelivery, $force = false)
    {
        $fileName = $this->getArchiveFileName($compiledDelivery);

        if (!$force && $this->getArchiveFileSystem()->has($fileName)) {
            throw new DeliverArchiveExistingException('Delivery archive already created: ' . $compiledDelivery->getUri());
        }

        $this->generateNewTmpPath($fileName);
        $fileName = $this->getArchiveFileName($compiledDelivery);
        $localZipName = $this->getLocalZipPathName($fileName);

        $zip = new \ZipArchive();
        $zip->open($localZipName, \ZipArchive::CREATE);

        $directories = $compiledDelivery->getPropertyValues(
            new core_kernel_classes_Property(DeliveryAssemblyService::PROPERTY_DELIVERY_DIRECTORY)
        );
        foreach ($directories as $directoryId) {
            $directory = \tao_models_classes_service_FileStorage::singleton()->getDirectoryById($directoryId);
            $directories = $directory->getFlyIterator(Directory::ITERATOR_FILE | Directory::ITERATOR_RECURSIVE);
            /** @var File $item */
            foreach ($directories as $item) {
                $zip->addFromString($item->getFileSystemId() . '/' . $item->getPrefix(), $item->read());
            }
        }

        $zip = $this->refreshArchiveProcessed($zip);
        $zip->close();

        $fileName = $this->uploadZip($compiledDelivery);

        $this->deleteTmpFile($localZipName);

        return $fileName;
    }

    /**
     * @param $compiledDelivery
     * @param bool $force
     * @return string
     * @throws DeliveryArchiveNotExistingException
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    public function unArchive($compiledDelivery, $force = false)
    {
        $fileName = $this->getArchiveFileName($compiledDelivery);

        if (!$this->getArchiveFileSystem()->has($fileName)) {
            throw new DeliveryArchiveNotExistingException('Delivery archive not exist please generate: ' . $compiledDelivery->getUri());
        }

        $this->generateNewTmpPath($fileName);
        $zipPath = $this->download($compiledDelivery);

        $zip = new \ZipArchive();
        $zip->open($zipPath);

        if ($force || !$this->isArchivedProcessed($zip, $fileName)){
            $this->copyFromZip($zip);
            $this->setArchiveProcessed($zip, $fileName);
            $zip->close();

            $fileName = $this->uploadZip($compiledDelivery);
        } else {
            $zip->close();
        }

        $this->deleteTmpFile($zipPath);

        return $fileName;
    }

    /**
     * @param $compiledDelivery
     * @return string
     */
    public function deleteArchive($compiledDelivery)
    {
        $fileName = $this->getArchiveFileName($compiledDelivery);
        if ($this->getArchiveFileSystem()->has($fileName)) {
            $this->getArchiveFileSystem()->delete($fileName);
        }

        return $fileName;
    }

    /**
     * @param $zip \ZipArchive
     * @return bool
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    private function copyFromZip($zip)
    {
        /** @var FileSystemService $fileSystem */
        $fileSystem = $this->getServiceManager()->get(FileSystemService::SERVICE_ID);

        for ($index = 0; $index < $zip->numFiles; ++$index)
        {
            $zipEntryName = $zip->getNameIndex($index);
            if (!$this->isZipDirectory($zipEntryName)) {
                $parts = explode('/', $zipEntryName);
                $bucketDestination = $parts[0];
                unset($parts[0]);
                if (in_array($bucketDestination, ['public', 'private',])) {
                    $entryName = implode('/', $parts);
                    $stream = $zip->getStream($zipEntryName);
                    if (is_resource($stream)) {
                        $fileSystem->getFileSystem($bucketDestination)->updateStream($entryName, $stream);
                    }
                }
            }
        }
        return true;
    }

    /**
     * @param $compiledDelivery
     * @return string
     */
    private function uploadZip($compiledDelivery)
    {
        $fileName = $this->getArchiveFileName($compiledDelivery);
        $zipPath = $this->getLocalZipPathName($fileName);

        if (!$this->getArchiveFileSystem()->has($fileName)) {
            $this->getArchiveFileSystem()->write($fileName, file_get_contents($zipPath));
        } else {
            $this->getArchiveFileSystem()->update($fileName, file_get_contents($zipPath));
        }

        return $fileName;
    }

    /**
     * @return FileSystem
     */
    private function getArchiveFileSystem()
    {
        return $this->getServiceManager()->get(FileSystemService::SERVICE_ID)->getFileSystem(static::BUCKET_DIRECTORY);
    }

    /**
     * @param $compiledDelivery
     * @return string
     */
    private function download($compiledDelivery)
    {
        $fileName = $this->getArchiveFileName($compiledDelivery);
        $zipPath = $this->getLocalZipPathName($fileName);

        file_put_contents($zipPath, $this->getArchiveFileSystem()->read($fileName));

        return $zipPath;
    }

    /**
     * @param $compiledDelivery
     * @return string
     */
    private function getArchiveFileName($compiledDelivery)
    {
        return md5($compiledDelivery->getUri()) . '.zip';
    }

    /**
     * @param $fileName
     * @return string
     */
    private function getLocalZipPathName($fileName)
    {
        return $this->getTmpPath() . $fileName;
    }

    /**
     * @return mixed
     */
    private function getTmpPath()
    {
        return $this->tmpDir;
    }


    /**
     * generate unique tmp folder based on delivery.
     * @param $fileName
     */
    private function generateNewTmpPath($fileName)
    {
        $folder = sys_get_temp_dir().DIRECTORY_SEPARATOR."tmp".md5($fileName. uniqid('', true)).DIRECTORY_SEPARATOR;

        if (!file_exists($folder)) {
            mkdir($folder);
        }

        $this->tmpDir = $folder;
    }

    /**
     * @param $tmpZipPath
     */
    private function deleteTmpFile($tmpZipPath)
    {
        unlink($tmpZipPath);
        if (\helpers_File::emptyDirectory($this->tmpDir)) {
            rmdir($this->tmpDir);
        }
    }

    /**
     * @param $zipEntryName
     * @return bool
     */
    private function isZipDirectory($zipEntryName)
    {
        return substr($zipEntryName, -1) ===  '/';
    }

    /**
     * @param $fileName
     * @return string
     */
    private function getUniqueProcessedName($fileName)
    {
        return md5(gethostname()).'s2' . $fileName;
    }

    /**
     * @param \ZipArchive $zip
     * @param $fileName
     * @return \ZipArchive
     */
    private function setArchiveProcessed($zip, $fileName)
    {
        $stats = json_decode($zip->getArchiveComment(), true);
        if (is_null($stats)) {
            $stats = ['processed' => []];
        }

        $stats['processed'][] = $this->getUniqueProcessedName($fileName);
        $zip->setArchiveComment(json_encode($stats));

        return $zip;
    }

    /**
     * @param \ZipArchive $zip
     * @return \ZipArchive
     */
    private function refreshArchiveProcessed($zip)
    {
        $stats = ['processed' => []];
        $zip->setArchiveComment(json_encode($stats));

        return $zip;
    }

    /**
     * @param \ZipArchive $zip
     * @return bool
     */
    private function isArchivedProcessed($zip, $fileName)
    {
        $stats = json_decode($zip->getArchiveComment(), true);
        if (is_null($stats) || !isset($stats['processed'])) {
            $stats = ['processed' => []];
        }

        return in_array($this->getUniqueProcessedName($fileName), $stats['processed']);
    }
}