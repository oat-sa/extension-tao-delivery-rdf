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
 *
 * @author Oleksandr Zagovorychev <zagovorichev@gmail.com>
 */

namespace oat\taoDeliveryRdf\test\integration\model\import;


use ArrayIterator;
use common_ext_ExtensionsManager;
use common_report_Report;
use core_kernel_classes_Class;
use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use Exception;
use GuzzleHttp\Psr7\Stream;
use oat\generis\model\data\Ontology;
use oat\generis\model\kernel\uri\UriProvider;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\oatbox\filesystem\File;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\log\LoggerService;
use oat\tao\model\service\ServiceFileStorage;
use oat\taoDeliveryRdf\model\import\assemblerDataProviders\serviceCallConverters\AssemblerFileReaderInterface;
use oat\taoDeliveryRdf\model\import\assemblerDataProviders\serviceCallConverters\ServiceCallConverterInterface;
use oat\taoDeliveryRdf\model\import\AssemblerService;
use org\bovigo\vfs\vfsStream;
use tao_models_classes_export_RdfExporter;
use tao_models_classes_service_ServiceCall;
use tao_models_classes_service_StorageDirectory;

class AssemblerServiceTest extends TestCase
{

    private $root;

    public function setUp()
    {
        if (!class_exists('org\bovigo\vfs\vfsStream')) {
            $this->markTestSkipped(
                'AssemblerServiceTest requires mikey179/vfsStream'
            );
        }
        $this->root = vfsStream::setup('data');
    }

    /**
     * @throws Exception
     */
    public function testExportCompiledDelivery()
    {
        /** @var core_kernel_classes_Resource|MockObject $mockedDelivery */
        $mockedDelivery = $this->createMock(core_kernel_classes_Resource::class);
        $mockedDelivery->method('getUri')->willReturn('deliveryUri');
        $mockedDelivery->method('getPropertyValues')->willReturnCallback(
            static function ($params) {
                if ($params instanceof core_kernel_classes_Property
                    && $params->getUri() === 'http://www.tao.lu/Ontologies/TAODelivery.rdf#AssembledDeliveryCompilationDirectory') {
                    // directories ids
                    return [1, 2];
                }
                return [];
            });
        $mockedDelivery->method('getLabel')->willReturn('deliveryLabel');
        $runtimeResourceMock = $this->createMock(core_kernel_classes_Resource::class);
        $mockedDelivery->method('getUniquePropertyValue')->willReturn($runtimeResourceMock);
        $loggerServiceMock = $this->createMock(LoggerService::class);
        $extensionsManagerMock = $this->createMock(common_ext_ExtensionsManager::class);
        $serviceFileStorage = $this->createMock(ServiceFileStorage::class);
        $self = $this;
        $serviceFileStorage->method('getDirectoryById')->willReturnCallback(static function($id) use ($self) {
            $fileMock = $self->getMock(File::class, [], [], '', false);
            $fileGetBaseNameMethod = $fileMock->method('getBaseName');
            if ($id === 1) {
                // file in directory 1
                $fileGetBaseNameMethod->willReturn('fileFromDir1');
            } else {
                // file in directory not 1
                $fileGetBaseNameMethod->willReturn('fileFromDir2');
            }

            // returns directory data
            $arrayIterator = new ArrayIterator([$fileMock]);
            // storage directory
            $directoryMock = $self->getMock(tao_models_classes_service_StorageDirectory::class, [], [], '', false);
            $directoryMock->method('getFlyIterator')->willReturn($arrayIterator);
            $directoryMock->method('getRelativePath')->willReturn('relative/path');

            return $directoryMock;
        });

        $directoryForExportMock = $this->createMock(tao_models_classes_service_StorageDirectory::class);
        $exportFileMock = $this->createMock(File::class);
        $exportFileMock->method('put');
        $directoryForExportMock->method('getFile')->willReturn($exportFileMock);

        /** @var FileSystemService|MockObject $fileSystemMock */
        $fileSystemMock = $this->createMock(FileSystemService::class);
        $fileSystemMock->method('getDirectory')->willReturn($directoryForExportMock);

        $serviceLocator = $this->getServiceLocatorMock([
            LoggerService::SERVICE_ID => $loggerServiceMock,
            common_ext_ExtensionsManager::SERVICE_ID => $extensionsManagerMock,
            ServiceFileStorage::SERVICE_ID => $serviceFileStorage,
            FileSystemService::SERVICE_ID => $fileSystemMock,
        ]);

        $serviceCallMock = $this->createMock(tao_models_classes_service_ServiceCall::class);

        $serviceCallConverterMock = $this->getMock(ServiceCallConverterInterface::class);
        $serviceCallConverterMock->method('getServiceCallFromResource')->willReturn($serviceCallMock);

        /** @var AssemblerFileReaderInterface|MockObject $fileReaderMock */
        $fileReaderMock = $this->getMock(AssemblerFileReaderInterface::class, ['getFileStream', 'clean']);
        $streamMock = $this->getMock(Stream::class, [], [], '', false);
        $fileReaderMock->method('getFileStream')->willReturn($streamMock);

        $rdfExporterMock = $this->createMock(tao_models_classes_export_RdfExporter::class);

        $assemblerService = new AssemblerService([
            AssemblerService::OPTION_SERVICE_CALL_CONVERTER => $serviceCallConverterMock,
            AssemblerService::OPTION_FILE_READER => $fileReaderMock,
            AssemblerService::OPTION_RDF_EXPORTER => $rdfExporterMock,
        ]);
        $assemblerService->setServiceLocator($serviceLocator);
        $path = $assemblerService->exportCompiledDelivery($mockedDelivery, vfsStream::url('data'));
        $this->assertStringEndsWith('deliveryLabel.zip', $path);
    }

    public function testImportDelivery()
    {
        $packagePath  = __DIR__ . '/samples/Archive.zip';

        $serviceFileStorage = $this->createMock(ServiceFileStorage::class);
        $serviceFileStorage->method('import');
        $loggerServiceMock = $this->createMock(LoggerService::class);
        $uriProviderMock = $this->createMock(UriProvider::class);

        $serviceLocator = $this->getServiceLocatorMock([
            ServiceFileStorage::SERVICE_ID => $serviceFileStorage,
            LoggerService::SERVICE_ID => $loggerServiceMock,
            UriProvider::SERVICE_ID => $uriProviderMock,
        ]);

        $serviceCallMock = $this->createMock(tao_models_classes_service_ServiceCall::class);
        $serviceCallMock->method('toOntology')->willReturn('abc');

        $serviceCallConverterMock = $this->getMock(ServiceCallConverterInterface::class);
        $serviceCallConverterMock->method('getServiceCallFromString')->willReturn($serviceCallMock);

        $assemblerService = new AssemblerService([
            AssemblerService::OPTION_SERVICE_CALL_CONVERTER => $serviceCallConverterMock,
        ]);
        $assemblerService->setServiceLocator($serviceLocator);

        /** @var Ontology|MockObject $ontologyMock */
        $ontologyMock = $this->createMock(Ontology::class);
        $deliveryResourceMock = $this->createMock(core_kernel_classes_Resource::class);
        $deliveryResourceMock->method('exists')->willReturn(false);
        $deliveryResourceMock->method('setType');
        $deliveryResourceMock->method('setPropertiesValues');
        $deliveryResourceMock->method('getUri')->willReturn('deliveryUri');
        $ontologyMock->method('getResource')->willReturn($deliveryResourceMock);
        $assemblerService->setModel($ontologyMock);
        $report = $assemblerService->importDelivery(new core_kernel_classes_Class('deliveryClassUri'), $packagePath);
        $this->assertInstanceOf(common_report_Report::class, $report);
        $this->assertCount(0, $report->getErrors());
        $this->assertSame([], $report->getSuccesses());
        $this->assertSame('Delivery "deliveryUri" successfully imported', $report->getMessage());
    }
}
