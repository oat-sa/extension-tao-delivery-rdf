<?php


namespace oat\taoDeliveryRdf\model\export;

use ZipArchive;
use Exception;
use common_Exception;
use core_kernel_classes_EmptyProperty;
use tao_helpers_Display;
use tao_helpers_Export;
use tao_helpers_File;
use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use common_ext_ExtensionsManager;
use tao_models_classes_service_FileStorage;
use tao_models_classes_service_ServiceCall;
use tao_models_classes_export_RdfExporter;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;

class AssemblyExporterService extends ConfigurableService
{
    use LoggerAwareTrait;
    use OntologyAwareTrait;

    const MANIFEST_FILE = 'manifest.json';

    /**
     * Export Compiled Delivery
     *
     * Exports a delivery into its compiled form. In case of the $fsExportPath argument is set,
     * the compiled delivery will be stored in the 'taoDelivery' shared file system, at $fsExportPath location.
     *
     * @param core_kernel_classes_Resource $compiledDelivery
     * @return string The path to the compiled delivery on the local file system OR the 'taoDelivery' shared file system, depending on whether $fsExportPath is set.
     *
     * @throws common_Exception
     * @throws core_kernel_classes_EmptyProperty
     */
    public function exportCompiledDelivery(core_kernel_classes_Resource $compiledDelivery)
    {
        $this->logDebug("Exporting Delivery Assembly '" . $compiledDelivery->getUri() . "'...");

        $fileName = tao_helpers_Display::textCleaner($compiledDelivery->getLabel()).'.zip';
        $path = tao_helpers_File::concat(array(tao_helpers_Export::getExportPath(), $fileName));
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
     * @param ZipArchive $zipArchive
     * @throws common_Exception
     * @throws core_kernel_classes_EmptyProperty
     */
    protected function doExportCompiledDelivery($path, core_kernel_classes_Resource $compiledDelivery, ZipArchive $zipArchive)
    {
        $taoDeliveryVersion = common_ext_ExtensionsManager::singleton()->getInstalledVersion('taoDelivery');

        $data = array(
            'dir' => array(),
            'label' => $compiledDelivery->getLabel(),
            'version' => $taoDeliveryVersion
        );
        $directories = $compiledDelivery->getPropertyValues(new core_kernel_classes_Property(DeliveryAssemblyService::PROPERTY_DELIVERY_DIRECTORY));
        foreach ($directories as $id) {
            $directory = tao_models_classes_service_FileStorage::singleton()->getDirectoryById($id);
            $files = $directory->getIterator();
            foreach ($files as $file) {
                $source = $this->getFileSource($directory, $file);
                tao_helpers_File::addFilesToZip($zipArchive, $source, $directory->getRelativePath() . $file);
            }
            $data['dir'][$id] = $directory->getRelativePath();
        }

        $runtime = $compiledDelivery->getUniquePropertyValue(new core_kernel_classes_Property(DeliveryAssemblyService::PROPERTY_DELIVERY_RUNTIME));
        $serviceCall = tao_models_classes_service_ServiceCall::fromResource($runtime);
        $data['runtime'] = base64_encode($serviceCall->serializeToString());

        $rdfExporter = new tao_models_classes_export_RdfExporter();
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
     * @param \tao_models_classes_service_StorageDirectory $directory
     * @param string $file
     * @return \Psr\Http\Message\StreamInterface
     */
    protected function getFileSource(\tao_models_classes_service_StorageDirectory $directory, $file)
    {
        return $directory->readPsrStream($file);
    }
}