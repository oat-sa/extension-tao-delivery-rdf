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


use ArrayIterator;
use common_Exception;
use common_ext_ExtensionsManager;
use core_kernel_classes_Container;
use core_kernel_classes_EmptyProperty;
use Exception;
use oat\generis\model\kernel\uri\UriProvider;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\filesystem\Directory;
use oat\oatbox\filesystem\File;
use oat\taoDeliveryRdf\model\import\assemblerDataProviders\assemblerFileReaders\AssemblerFileReader;
use oat\taoDeliveryRdf\model\import\assemblerDataProviders\serviceCallConverters\ServiceCallConverterInterface;
use tao_helpers_File;
use tao_models_classes_export_RdfExporter;
use tao_models_classes_service_ServiceCall;
use tao_models_classes_service_StorageDirectory;
use ZipArchive;
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
use oat\generis\model\OntologyRdf;
use oat\tao\model\service\ServiceFileStorage;

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
    use OntologyAwareTrait;

    const MANIFEST_FILE = 'manifest.json';
    
    const RDF_FILE = 'delivery.rdf';

    const OPTION_FILESYSTEM_ID = 'filesystemId';

    /**
     * Getting file content
     */
    const OPTION_FILE_READER = 'fileReader';

    /**
     * Transform runtime to the different formats
     */
    const OPTION_SERVICE_CALL_CONVERTER = 'serviceCallConverter';

    /**
     * Exporter (example new tao_models_classes_export_RdfExporter())
     */
    const OPTION_RDF_EXPORTER = 'rdfExporter';

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
            $tmpImportFolder = tao_helpers_File::createTempDir();
            $zip = new ZipArchive();
            if ($zip->open($archiveFile) !== true) {
                return  common_report_Report::createFailure(__('Unable to import Archive'));
            }
            $zip->extractTo($tmpImportFolder);
            $zip->close();

            $this->importDeliveryFiles($tmpImportFolder);

            $deliveryUri = $this->getDeliveryUri($useOriginalUri, $tmpImportFolder);
            $delivery = $this->importDeliveryResource($deliveryClass, $deliveryUri, $tmpImportFolder);

            $report = common_report_Report::createSuccess(__('Delivery "%s" successfully imported',$delivery->getUri()), $delivery);

            return $report;
        } catch (AssemblyImportFailedException $e) {
            return common_report_Report::createFailure($e->getMessage());
        } catch (Exception $e) {
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
     * @param FileIterator $rdfIterator
     * @return array
     */
    protected function getAdditionalProperties(FileIterator $rdfIterator)
    {
        $properties = [];
        $blacklist = array(OntologyRdf::RDF_TYPE);
        foreach ($rdfIterator as $triple) {
            if (!in_array($triple->predicate, $blacklist, true)) {
                if (!isset($properties[$triple->predicate])) {
                    $properties[$triple->predicate] = array();
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
     * Getting configured ServiceCall converter
     * @return ServiceCallConverterInterface
     * @throws AssemblyImportFailedException
     */
    private function getServiceCallConverter()
    {
        $converter = $this->getOption(self::OPTION_SERVICE_CALL_CONVERTER);
        if (!is_a($converter, ServiceCallConverterInterface::class)) {
            throw new AssemblyImportFailedException(self::OPTION_SERVICE_CALL_CONVERTER. ' option is not configured properly');
        }
        return $converter;
    }

    /**
     * @param string $runtime
     * @return tao_models_classes_service_ServiceCall
     * @throws AssemblyImportFailedException
     */
    protected function getRuntimeFromString($runtime)
    {
        return $this->getServiceCallConverter()->getServiceCallFromString($runtime);
    }

    /**
     * @param tao_models_classes_service_ServiceCall $serviceCall
     * @return string
     * @throws AssemblyImportFailedException
     */
    protected function getRuntime(tao_models_classes_service_ServiceCall $serviceCall)
    {
        return $this->getServiceCallConverter()->convertServiceCallToString($serviceCall);
    }

    /**
     * @param core_kernel_classes_Resource|core_kernel_classes_Container $resource
     * @return tao_models_classes_service_ServiceCall
     * @throws AssemblyImportFailedException
     */
    protected function getServiceCallFromResource(core_kernel_classes_Resource $resource)
    {
        return $this->getServiceCallConverter()->getServiceCallFromResource($resource);
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
        $serviceCall    = $this->getRuntimeFromString($manifest['runtime']);

        $properties = $this->getAdditionalProperties($this->getRdfResourceIterator($tmpImportFolder));
        $properties = array_merge($properties, array(
            OntologyRdfs::RDFS_LABEL                          => $label,
            DeliveryAssemblyService::PROPERTY_DELIVERY_DIRECTORY => array_keys($dirs),
            DeliveryAssemblyService::PROPERTY_DELIVERY_TIME      => time(),
            DeliveryAssemblyService::PROPERTY_DELIVERY_RUNTIME   => $serviceCall->toOntology(),
        ));

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
            return $this->getServiceLocator()->get(UriProvider::SERVICE_ID)->provide();
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
     * @throws common_Exception
     */
    protected function importDeliveryFiles($tmpImportFolder)
    {
        $manifest = $this->getDeliveryManifest($tmpImportFolder);
        $dirs     = $manifest['dir'];
        foreach ($dirs as $id => $relPath) {
            $this->getServiceLocator()->get(ServiceFileStorage::SERVICE_ID)->import($id, $tmpImportFolder . $relPath);
        }
    }

    /**
     * export a compiled delivery into an archive
     *
     * @param core_kernel_classes_Resource $compiledDelivery
     * @param string $fsExportPath
     * @throws Exception
     * @return string
     */
    public function exportCompiledDelivery(core_kernel_classes_Resource $compiledDelivery, $fsExportPath = '')
    {
        $this->logDebug("Exporting Delivery Assembly '" . $compiledDelivery->getUri() . "'...");

        $fileName = \tao_helpers_Display::textCleaner($compiledDelivery->getLabel()).'.zip';
        $path = tao_helpers_File::concat(array(\tao_helpers_Export::getExportPath(), $fileName));
        if (!tao_helpers_File::securityCheck($path, true)) {
            throw new Exception('Unauthorized file name');
        }

        // If such a target zip file exists, remove it from local filesystem. It prevents some synchronicity issues
        // to occur while dealing with ZIP Archives (not explained yet).
        if (file_exists($path)) {
            unlink($path);
        }

        $zipArchive = new ZipArchive();
        if ($zipArchive->open($path, ZipArchive::CREATE) !== true) {
            throw new Exception('Unable to create archive at '.$path);
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
     * Adding files from the directory to the archive
     * @param tao_models_classes_service_StorageDirectory $directory
     * @param ZipArchive $toArchive
     */
    protected function addFilesToZip(tao_models_classes_service_StorageDirectory $directory, ZipArchive $toArchive)
    {
        /** @var AssemblerFileReader $reader */
        $reader = $this->getOption(self::OPTION_FILE_READER);
        $this->propagate($reader);
        /** @var ArrayIterator $iterator */
        $iterator = $directory->getFlyIterator(Directory::ITERATOR_FILE | Directory::ITERATOR_RECURSIVE);
        while ($iterator->valid()) {
            /** @var File $file */
            $file = $iterator->current();
            tao_helpers_File::addFilesToZip($toArchive, $reader->getFileStream($file, $directory), $directory->getPrefix() .'/'. $file->getBasename());
            $reader->clean();
            $iterator->next();
        }
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
     * @param ZipArchive $zipArchive
     * @throws common_Exception
     * @throws core_kernel_classes_EmptyProperty
     */
    protected function doExportCompiledDelivery($path, core_kernel_classes_Resource $compiledDelivery, ZipArchive $zipArchive)
    {
        $taoDeliveryVersion = $this->getServiceLocator()
            ->get(common_ext_ExtensionsManager::SERVICE_ID)->getInstalledVersion('taoDelivery');

        $data = array(
            'dir' => array(),
            'label' => $compiledDelivery->getLabel(),
            'version' => $taoDeliveryVersion
        );
        $directories = $compiledDelivery->getPropertyValues(new core_kernel_classes_Property(DeliveryAssemblyService::PROPERTY_DELIVERY_DIRECTORY));
        foreach ($directories as $id) {
            $directory = $this->getServiceLocator()->get(ServiceFileStorage::SERVICE_ID)->getDirectoryById($id);
            $this->addFilesToZip($directory, $zipArchive);
            $data['dir'][$id] = $directory->getRelativePath();
        }

        $runtime = $compiledDelivery->getUniquePropertyValue(new core_kernel_classes_Property(DeliveryAssemblyService::PROPERTY_DELIVERY_RUNTIME));
        $serviceCall = $this->getServiceCallFromResource($runtime);
        $data['runtime'] = $this->getRuntime($serviceCall);

        $rdfExporter = $this->getRdfExporter();
        $rdfdata = $rdfExporter->getRdfString(array($compiledDelivery));
        if (!$zipArchive->addFromString('delivery.rdf', $rdfdata)) {
            throw new common_Exception('Unable to add metadata to exported delivery assembly');
        }
        $data['meta'] = 'delivery.rdf';

        $content = json_encode($data);
        if (!$zipArchive->addFromString(self::MANIFEST_FILE, $content)) {
            $zipArchive->close();
            unlink($path);
            throw new common_Exception('Unable to add manifest to exported delivery assembly');
        }
    }

    /**
     * @return mixed
     * @throws AssemblyImportFailedException
     */
    protected function getRdfExporter()
    {
        $exporter = $this->getOption(self::OPTION_RDF_EXPORTER);
        if (!is_a($exporter, tao_models_classes_export_RdfExporter::class)) {
            throw new AssemblyImportFailedException(self::OPTION_RDF_EXPORTER . ' option does not configured properly');
        }
        return $exporter;
    }
}
