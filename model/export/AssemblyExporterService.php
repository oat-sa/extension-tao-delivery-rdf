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
 * Copyright (c) 2019  (original work) Open Assessment Technologies SA;
 */

namespace oat\taoDeliveryRdf\model\export;

use oat\taoDeliveryRdf\model\assembly\CompiledTestConverterFactory;
use ZipArchive;
use Exception;
use InvalidArgumentException;
use common_Exception;
use core_kernel_classes_EmptyProperty;
use tao_helpers_Display;
use tao_helpers_Export;
use tao_helpers_File;
use core_kernel_classes_Resource;
use common_ext_ExtensionsManager;
use tao_models_classes_service_ServiceCall;
use tao_models_classes_export_RdfExporter;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\service\ServiceFileStorage;
use oat\taoDeliveryRdf\model\assembly\AssemblyFilesReaderInterface;
use oat\taoDeliveryRdf\model\assembly\UnsupportedCompiledTestFormatException;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;

class AssemblyExporterService extends ConfigurableService
{
    use LoggerAwareTrait;
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          use OntologyAwareTrait;


    const SERVICE_ID = 'taoDeliveryRdf/AssemblyExporterService';
    const OPTION_ASSEMBLY_FILES_READER = 'assembly_files_reader';
    const OPTION_RDF_EXPORTER = 'rdf_exporter';
    const MANIFEST_FILENAME = 'manifest.json';
    const DELIVERY_RDF_FILENAME = 'delivery.rdf';
    /**
         * @var AssemblyFilesReaderInterface
         */
    private $assemblyFilesReader;
    /**
         * @var tao_models_classes_export_RdfExporter
         */
    private $rdfExporter;
    /**
         * AssemblyExporterService constructor.
         * @param array $options
         */
    public function __construct($options = [])
    {
        parent::__construct($options);
        if (!$this->getOption(self::OPTION_ASSEMBLY_FILES_READER) instanceof AssemblyFilesReaderInterface) {
            throw new InvalidArgumentException(sprintf('%s option value must be an instance of %s', self::OPTION_ASSEMBLY_FILES_READER, AssemblyFilesReaderInterface::class));
        }

        $this->rdfExporter = $this->getOption(self::OPTION_RDF_EXPORTER);
        if (!$this->rdfExporter instanceof tao_models_classes_export_RdfExporter) {
            throw new InvalidArgumentException('%s option value must be an instance of %s', self::OPTION_RDF_EXPORTER, tao_models_classes_export_RdfExporter::class);
        }
    }

    /**
     * Export Compiled Delivery
     *
     * Exports a delivery into its compiled form. In case of the $fsExportPath argument is set,
     * the compiled delivery will be stored in the 'taoDelivery' shared file system, at $fsExportPath location.
     *
     * @param core_kernel_classes_Resource $compiledDelivery
     * @param string $outputTestFormat Format compiled test file in output assembly package.
     *
     * @return string The path to the compiled delivery on the local file system OR the 'taoDelivery' shared file system, depending on whether $fsExportPath is set.
     *
     * @throws common_Exception
     * @throws core_kernel_classes_EmptyProperty
     */
    public function exportCompiledDelivery(core_kernel_classes_Resource $compiledDelivery, $outputTestFormat)
    {
        $this->logDebug("Exporting Delivery Assembly '" . $compiledDelivery->getUri() . "'...");
        $fileName = tao_helpers_Display::textCleaner($compiledDelivery->getLabel()) . '.zip';
        $path = tao_helpers_File::concat([tao_helpers_Export::getExportPath(), $fileName]);
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
            throw new Exception('Unable to create archive at ' . $path);
        }

        $this->setupCompiledTestConverter($outputTestFormat);
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
        $data = [
            'dir' => [],
            'label' => $compiledDelivery->getLabel(),
            'version' => $taoDeliveryVersion
        ];
        $directories = $compiledDelivery->getPropertyValues($this->getProperty(DeliveryAssemblyService::PROPERTY_DELIVERY_DIRECTORY));
        foreach ($directories as $id) {
            $directory = $this->getServiceLocator()->get(ServiceFileStorage::SERVICE_ID)->getDirectoryById($id);
            foreach ($this->assemblyFilesReader->getFiles($directory) as $filePath => $fileStream) {
                tao_helpers_File::addFilesToZip($zipArchive, $fileStream, $filePath);
            }
            $data['dir'][$id] = $directory->getPrefix();
        }

        $runtime = $compiledDelivery->getUniquePropertyValue($this->getProperty(DeliveryAssemblyService::PROPERTY_DELIVERY_RUNTIME));
        $serviceCall = tao_models_classes_service_ServiceCall::fromResource($runtime);
        $data['runtime'] = base64_encode($serviceCall->serializeToString());
        $rdfData = $this->rdfExporter->getRdfString([$compiledDelivery]);
        if (!$zipArchive->addFromString(self::DELIVERY_RDF_FILENAME, $rdfData)) {
            throw new common_Exception('Unable to add metadata to exported delivery assembly');
        }
        $data['meta'] = self::DELIVERY_RDF_FILENAME;
        $content = json_encode($data);
        if (!$zipArchive->addFromString(self::MANIFEST_FILENAME, $content)) {
            $zipArchive->close();
            unlink($path);
            throw new common_Exception('Unable to add manifest to exported delivery assembly');
        }
    }

    /**
     * @param string $outputTestFormat
     * @return void
     *
     * @throws UnsupportedCompiledTestFormatException
     */
    private function setupCompiledTestConverter($outputTestFormat)
    {
        /** @var CompiledTestConverterFactory $compiledTestConverterFactory */
        $compiledTestConverterFactory = $this->getServiceLocator()->get(CompiledTestConverterFactory::class);
        $converter = $compiledTestConverterFactory->createConverter($outputTestFormat);
        $this->getAssemblyFilesReader()->setCompiledTestConverter($converter);
    }

    /**
     * @return AssemblyFilesReaderInterface
     */
    private function getAssemblyFilesReader()
    {
        if (!$this->assemblyFilesReader instanceof AssemblyFilesReaderInterface) {
            $this->assemblyFilesReader = $this->getOption(self::OPTION_ASSEMBLY_FILES_READER);
        }

        return $this->assemblyFilesReader;
    }
}
