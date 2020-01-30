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
 * Copyright (c) 2013 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoDeliveryRdf\model\import;

use common_Utils;
use oat\generis\model\OntologyAwareTrait;
use ZipArchive;
use common_report_Report;
use core_kernel_classes_Resource;
use core_kernel_classes_Class;
use oat\generis\model\kernel\persistence\file\FileIterator;
use oat\generis\model\OntologyRdfs;
use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\generis\model\OntologyRdf;

/**
 * AssemblyImporterService Class.
 *
 * Im- and export a compiled delivery
 *
 * @access public
 * @package taoDeliveryRdf
 */
class AssemblyImporterService extends ConfigurableService
{
    use LoggerAwareTrait;
    use OntologyAwareTrait;

    const MANIFEST_FILE = 'manifest.json';
    
    const RDF_FILE = 'delivery.rdf';

    /**
     * @param core_kernel_classes_Class $deliveryClass
     * @param string $archiveFile
     * @param boolean $useOriginalUri Use original delivery URI from assembly package
     *
     * @return common_report_Report
     */
    public function importDelivery(core_kernel_classes_Class $deliveryClass, $archiveFile, $useOriginalUri = false)
    {
        try {
            $tmpImportFolder = \tao_helpers_File::createTempDir();
            $zip = new ZipArchive();
            if ($zip->open($archiveFile) !== true) {
                return  common_report_Report::createFailure(__('Unable to import Archive'));
            }
            $zip->extractTo($tmpImportFolder);
            $zip->close();

            $this->importDeliveryFiles($tmpImportFolder);

            $deliveryUri = $this->getDeliveryUri($useOriginalUri, $tmpImportFolder);
            $delivery = $this->importDeliveryResource($deliveryClass, $deliveryUri, $tmpImportFolder);

            $report = common_report_Report::createSuccess(__('Delivery "%s" successfully imported', $delivery->getUri()), $delivery);

            return $report;
        } catch (AssemblyImportFailedException $e) {
            return common_report_Report::createFailure($e->getMessage());
        } catch (\Exception $e) {
            $this->logError($e->getMessage());

            return common_report_Report::createFailure('Unknown error during import');
        }
    }

    /**
     * @param string $tmpImportFolder
     *
     * @return array
     * @throws AssemblyImportFailedException
     */
    private function getDeliveryManifest($tmpImportFolder)
    {
        $manifestPath = $tmpImportFolder . self::MANIFEST_FILE;
        if (!file_exists($manifestPath)) {
            throw new AssemblyImportFailedException('Manifest not found in assembly.');
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
        if (!is_array($manifest) || json_last_error() !== JSON_ERROR_NONE) {
            throw new AssemblyImportFailedException('Manifest file is not valid.');
        }

        return $manifest;
    }

    /**
     * @param $folder
     * @return array
     */
    protected function getAdditionalProperties(FileIterator $rdfIterator)
    {
        $properties = [];
        $blacklist = [OntologyRdf::RDF_TYPE];
        foreach ($rdfIterator as $triple) {
            if (!in_array($triple->predicate, $blacklist)) {
                if (!isset($properties[$triple->predicate])) {
                    $properties[$triple->predicate] = [];
                }
                $properties[$triple->predicate][] = $triple->object;
            }
        }

        return $properties;
    }

    /**
     * @param $tmpImportFolder
     * @return FileIterator
     *
     * @throws AssemblyImportFailedException
     */
    protected function getRdfResourceIterator($tmpImportFolder)
    {
        $rdfPath = $tmpImportFolder . self::RDF_FILE;
        if (!file_exists($rdfPath)) {
            throw new AssemblyImportFailedException("Delivery rdf file {$rdfPath} does not exist");
        }

        return new FileIterator($rdfPath, 1);
    }

    /**
     * @param core_kernel_classes_Class $deliveryClass
     * @param string $deliveryUri
     * @param string $tmpImportFolder
     *
     * @return core_kernel_classes_Resource
     * @throws AssemblyImportFailedException
     */
    protected function importDeliveryResource(core_kernel_classes_Class $deliveryClass, $deliveryUri, $tmpImportFolder)
    {
        $manifest       = $this->getDeliveryManifest($tmpImportFolder);
        $label          = $manifest['label'];
        $dirs           = $manifest['dir'];
        $serviceCall    = \tao_models_classes_service_ServiceCall::fromString(base64_decode($manifest['runtime']));

        $properties = $this->getAdditionalProperties($this->getRdfResourceIterator($tmpImportFolder));
        $properties = array_merge($properties, [
            OntologyRdfs::RDFS_LABEL                          => $label,
            DeliveryAssemblyService::PROPERTY_DELIVERY_DIRECTORY => array_keys($dirs),
            DeliveryAssemblyService::PROPERTY_DELIVERY_TIME      => time(),
            DeliveryAssemblyService::PROPERTY_DELIVERY_RUNTIME   => $serviceCall->toOntology(),
        ]);

        $delivery = $this->getResource($deliveryUri);
        if ($delivery->exists()) {
            throw new AssemblyImportFailedException("Delivery with this URI already exist: {$deliveryUri}");
        }

        $delivery->setType($deliveryClass);
        $delivery->setPropertiesValues($properties);

        return $delivery;
    }

    /**
     * @param boolean   $useOriginalUri
     * @param string    $tmpImportFolder
     * @return string
     *
     * @throws AssemblyImportFailedException
     */
    private function getDeliveryUri($useOriginalUri, $tmpImportFolder)
    {
        if ($useOriginalUri === false) {
            return common_Utils::getNewUri();
        }

        $deliveryUri = null;
        foreach ($this->getRdfResourceIterator($tmpImportFolder) as $triple) {
            if ($triple->predicate == OntologyRdf::RDF_TYPE && $triple->object == DeliveryAssemblyService::CLASS_URI) {
                $deliveryUri = $triple->subject;
                break;
            }
        }

        if ($deliveryUri === null) {
            throw new AssemblyImportFailedException('Cannot find original delivery uri in delivery rdf file.');
        }

        return $deliveryUri;
    }

    /**
     * @param $tmpImportFolder
     * @throws \common_Exception
     */
    protected function importDeliveryFiles($tmpImportFolder)
    {
        $manifest = $this->getDeliveryManifest($tmpImportFolder);
        $dirs     = $manifest['dir'];
        foreach ($dirs as $id => $relPath) {
            \tao_models_classes_service_FileStorage::singleton()->import($id, $tmpImportFolder . $relPath);
        }
    }
}
