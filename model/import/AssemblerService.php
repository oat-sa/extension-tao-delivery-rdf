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

use common_report_Report;
use core_kernel_classes_Resource;
use core_kernel_classes_Class;
use core_kernel_classes_Property;
use oat\generis\model\kernel\persistence\file\FileIterator;
use oat\generis\model\OntologyRdfs;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\taoDeliveryRdf\model\AssemblerServiceInterface;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoDeliveryRdf\model\DeliveryContainerService;
use oat\generis\model\OntologyRdf;

/**
 * AssemblerService Class.
 *
 * Im- and export a compiled delivery 
 *
 * @access public
 * @author Joel Bout, <joel@taotesting.com>
 * @package taoDelivery
 
 */
class AssemblerService extends ConfigurableService implements AssemblerServiceInterface
{
    use LoggerAwareTrait;

    const MANIFEST_FILE = 'manifest.json';
    
    const RDF_FILE = 'delivery.rdf';

    const OPTION_FILESYSTEM_ID = 'filesystemId';

    /**
     * @param core_kernel_classes_Class $deliveryClass
     * @param string $archiveFile
     * @return common_report_Report
     */
    public function importDelivery(core_kernel_classes_Class $deliveryClass, $archiveFile)
    {
        
        $folder = \tao_helpers_File::createTempDir();
        $zip = new \ZipArchive();
        if ($zip->open($archiveFile) !== true) {
            return  common_report_Report::createFailure(__('Unable to import Archive'));
        }
        $zip->extractTo($folder);
        $zip->close();

        $manifestPath = $folder.self::MANIFEST_FILE;
        if (!file_exists($manifestPath)) {
            return common_report_Report::createFailure(__('Manifest not found in assembly'));
        }
        $manifest = json_decode(file_get_contents($manifestPath), true);

        try {
            
            $this->importDeliveryFiles($deliveryClass, $manifest, $folder);
            
            $properties = $this->getAdditionalProperties($folder);
            $delivery = $this->importDeliveryResource($deliveryClass, $manifest, $properties);
            
            $report = common_report_Report::createSuccess(__('Delivery "%s" successfully imported',$delivery->getUri()), $delivery);
        } catch (\Exception $e) {
            \common_Logger::w($e->getMessage());
            if (isset($delivery) && $delivery instanceof core_kernel_classes_Resource) {
                $delivery->delete();
            }
            $report = common_report_Report::createFailure(__('Unknown error during import'));
        }
        return $report;
    }

    /**
     * @return \oat\oatbox\filesystem\Directory
     */
    public function getExportDirectory()
    {
        /** @var FileSystemService $fileSystemService */
        $fileSystemService = $this->getServiceLocator()->get(FileSystemService::SERVICE_ID);
        $fileSystem = $fileSystemService->getDirectory($this->getOption(self::OPTION_FILESYSTEM_ID));

        return $fileSystem;
    }

    /**
     * @param $folder
     * @return array
     */
    protected function getAdditionalProperties($folder)
    {
        $rdfPath = $folder.self::RDF_FILE;
        
        $properties = array();
        if (file_exists($rdfPath)) {
            $blacklist = array(OntologyRdf::RDF_TYPE);
            $rdfIterator = new FileIterator($rdfPath, 1);
            foreach ($rdfIterator as $triple) {
                if (!in_array($triple->predicate, $blacklist)) {
                    if (!isset($properties[$triple->predicate])) {
                        $properties[$triple->predicate] = array();
                    }
                    $properties[$triple->predicate][] = $triple->object;
                }
            }
        }
        return $properties;
    }

    /**
     * @param core_kernel_classes_Class $deliveryClass
     * @param $manifest
     * @param array $properties
     * @return core_kernel_classes_Resource
     */
    protected function importDeliveryResource(core_kernel_classes_Class $deliveryClass, $manifest, $properties = array())
    {
        $label          = $manifest['label'];
        $dirs           = $manifest['dir'];
        $serviceCall    = \tao_models_classes_service_ServiceCall::fromString(base64_decode($manifest['runtime']));
        $resultServer   = \taoResultServer_models_classes_ResultServerAuthoringService::singleton()->getDefaultResultServer();
        
        $properties = array_merge($properties, array(
            OntologyRdfs::RDFS_LABEL                          => $label,
            DeliveryAssemblyService::PROPERTY_DELIVERY_DIRECTORY => array_keys($dirs),
            DeliveryAssemblyService::PROPERTY_DELIVERY_TIME      => time(),
            DeliveryAssemblyService::PROPERTY_DELIVERY_RUNTIME   => $serviceCall->toOntology(),
            DeliveryContainerService::PROPERTY_RESULT_SERVER      => $resultServer
        ));
        $delivery = $deliveryClass->createInstanceWithProperties($properties);
        
        return $delivery;
    }

    /**
     * @param core_kernel_classes_Class $deliveryClass
     * @param $manifest
     * @param $directory
     * @throws \common_Exception
     */
    protected function importDeliveryFiles(core_kernel_classes_Class $deliveryClass, $manifest, $directory)
    {
        $dirs           = $manifest['dir'];
        foreach ($dirs as $id => $relPath) {
            \tao_models_classes_service_FileStorage::singleton()->import($id, $directory.$relPath);
        }
        
    }
    
    /**
     * export a compiled delivery into an archive
     * 
     * @param core_kernel_classes_Resource $compiledDelivery
     * @param string $fsExportPath
     * @throws \Exception
     * @return string
     */
    public function exportCompiledDelivery(core_kernel_classes_Resource $compiledDelivery, $fsExportPath = '') {

        $this->logDebug("Exporting Delivery Assembly '" . $compiledDelivery->getUri() . "'...");

        $fileName = \tao_helpers_Display::textCleaner($compiledDelivery->getLabel()).'.zip';
        $path = \tao_helpers_File::concat(array(\tao_helpers_Export::getExportPath(), $fileName));
        if (!\tao_helpers_File::securityCheck($path, true)) {
            throw new \Exception('Unauthorized file name');
        }

        // If such a target zip file exists, remove it from local filesystem. It prevents some synchronicity issues
        // to occur while dealing with ZIP Archives (not explained yet).
        if (file_exists($path)) {
            unlink($path);
        }
        
        $zipArchive = new \ZipArchive();
        if ($zipArchive->open($path, \ZipArchive::CREATE) !== true) {
            throw new \Exception('Unable to create archive at '.$path);
        }

        $this->doExportCompiledDelivery($path, $compiledDelivery, $zipArchive);
        $zipArchive->close();

        if (!empty($fsExportPath)) {
            $this->logDebug("Writing Delivery Assembly '" . $compiledDelivery->getUri() . "' into shared file system at location '${fsExportPath}'...");
            $fsExportPath = trim($fsExportPath);
            $fsExportPath = ltrim($fsExportPath,"/\\");

            $zipArchiveHandler = fopen($path, 'r');
            $this->getExportDirectory()->getFile($fsExportPath)->put($zipArchiveHandler);
            fclose($zipArchiveHandler);
        }

        return $path;
    }

    /**
     * Do Export Compiled Delivery
     *
     * Method containing the main behavior of exporting a compiled delivery into a ZIP archive.
     *
     * For developers wanting to override this method, the following information has to be taken into account:
     *
     * - The value of the $zipArgive argument is an already open ZipArchive object.
     * - The method must keep the archive open after its execution (calling code will take care of it).
     *
     * @param $path
     * @param core_kernel_classes_Resource $compiledDelivery
     * @param \ZipArchive $zipArchive
     * @throws \common_Exception
     * @throws \core_kernel_classes_EmptyProperty
     */
    protected function doExportCompiledDelivery($path, core_kernel_classes_Resource $compiledDelivery, \ZipArchive $zipArchive)
    {
        $taoDeliveryVersion = \common_ext_ExtensionsManager::singleton()->getInstalledVersion('taoDelivery');

        $data = array(
            'dir' => array(),
            'label' => $compiledDelivery->getLabel(),
            'version' => $taoDeliveryVersion
        );
        $directories = $compiledDelivery->getPropertyValues(new core_kernel_classes_Property(DeliveryAssemblyService::PROPERTY_DELIVERY_DIRECTORY));
        foreach ($directories as $id) {
            $directory = \tao_models_classes_service_FileStorage::singleton()->getDirectoryById($id);
            $files = $directory->getIterator();
            foreach ($files as $file) {
                \tao_helpers_File::addFilesToZip($zipArchive, $directory->readPsrStream($file), $directory->getRelativePath() . $file);
            }
            $data['dir'][$id] = $directory->getRelativePath();
        }

        $runtime = $compiledDelivery->getUniquePropertyValue(new core_kernel_classes_Property(DeliveryAssemblyService::PROPERTY_DELIVERY_RUNTIME));
        $serviceCall = \tao_models_classes_service_ServiceCall::fromResource($runtime);
        $data['runtime'] = base64_encode($serviceCall->serializeToString());

        $rdfExporter = new \tao_models_classes_export_RdfExporter();
        $rdfdata = $rdfExporter->getRdfString(array($compiledDelivery));
        if (!$zipArchive->addFromString('delivery.rdf', $rdfdata)) {
            throw new \common_Exception('Unable to add metadata to exported delivery assembly');
        }
        $data['meta'] = 'delivery.rdf';


        $content = json_encode($data);
        if (!$zipArchive->addFromString(self::MANIFEST_FILE, $content)) {
            $zipArchive->close();
            unlink($path);
            throw new \common_Exception('Unable to add manifest to exported delivery assembly');
        }
    }
}
