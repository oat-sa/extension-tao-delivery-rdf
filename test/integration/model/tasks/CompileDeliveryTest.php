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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */
namespace oat\taoDeliveryRdf\test\integration\model\tasks;

use oat\generis\test\GenerisTestCase;
use oat\taoDeliveryRdf\model\DeliveryFactory;
use oat\generis\model\data\Ontology;
use oat\oatbox\filesystem\FileSystemService;
use oat\taoDeliveryRdf\model\tasks\CompileDelivery;
use Prophecy\Argument;
use oat\taoQtiTest\models\TestModelService;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoQtiItem\model\ValidationService;
use oat\oatbox\log\LoggerService;
use oat\taoQtiItem\model\qti\ImportService;
use oat\oatbox\session\SessionService;
use oat\oatbox\user\UserLanguageServiceInterface;
use oat\generis\model\kernel\persistence\file\FileIterator;
use oat\oatbox\config\ConfigurationService;
use oat\generis\model\fileReference\FileReferenceSerializer;
use oat\generis\model\fileReference\UrlFileSerializer;
use oat\oatbox\event\EventManager;
use oat\taoQtiItem\model\qti\metadata\MetadataService;
use oat\taoQtiItem\model\qti\metadata\importer\MetadataImporter;
use oat\taoQtiTest\models\cat\CatService;
use oat\taoQtiItem\model\AuthoringService;

class CompileDeliveryTest extends GenerisTestCase
{
    private function buildDeliveryFactory() {
        return new DeliveryFactory([
            DeliveryFactory::OPTION_PROPERTIES => []
            
        ]);
    }
    
    /**
     * @return \common_report_Report
     */
    private function importTest($serviceLocator) {
        $qtiService = $serviceLocator->get(\taoQtiTest_models_classes_QtiTestService::class);
        $deliveryService = $serviceLocator->get(DeliveryAssemblyService::class);
        $package = __DIR__.'/../../samples/package/package-basic.zip';
        return $qtiService->importMultipleTests($deliveryService->getRootClass(), $package);
    }
    /**
     * @return CompileDelivery
     */
    private function buildCompileDelivery() {
        // preparing services
        $onto = $this->getOntologyMock();
        $iterator = new FileIterator(__DIR__.'/../../../../../taoQtiTest/models/ontology/qtitest.rdf', 123);
        foreach ($iterator as $triple) {
            $onto->getRdfInterface()->add($triple);
        }
        
        $fs = $this->getFileSystemMock(['DeliveryFactoryTest']);
        $df = $this->buildDeliveryFactory();
        $testModel = new TestModelService();
        $testService = new \taoTests_models_classes_TestsService();
        $qtiTestService = new \taoQtiTest_models_classes_QtiTestService();
        $fileReferencer = new UrlFileSerializer();
        $meta = new MetadataService([
            MetadataService::IMPORTER_KEY => new MetadataImporter()
        ]);
        $importService = new ImportService();
        $itemService = new \taoItems_models_classes_ItemsService();
        $deliveryService = new DeliveryAssemblyService();
        
        $extQtiTest = $this->prophesize(\common_ext_Extension::class);
        $extQtiTest->getDir()->willReturn(__DIR__.'/../../../../../taoQtiTest/');
        $extQtiTest->getConfig('qtiTestFolder')->willReturn('DeliveryFactoryTest');
        
        $extTao = $this->prophesize(\common_ext_Extension::class);
        $extTao->getConstant('TAO_VERSION')->willReturn('test_version');
        $extMan = $this->prophesize(\common_ext_ExtensionsManager::class);
        $extMan->getExtensionById('taoQtiTest')->willReturn($extQtiTest->reveal());
        $extMan->getExtensionById('tao')->willReturn($extTao->reveal());
        $session = $this->prophesize(\common_session_AnonymousSession::class);
        $session->getUser()->willReturn(null);
        $session->getDataLanguage()->willReturn('test-TEST');
//        $fss = new \tao_models_classes_service_FileStorage();
//        $storage = $fss->getDirectoryById('abc');
//        $compiler = new \taoQtiTest_models_classes_QtiTestCompiler($resource, $storage);
//        $tsProphet->getCompiler(Argument::any(), Argument::any())->willReturn($compiler);
        
        // preparing locator
        $serviceLocator = $this->getServiceLocatorMock([
            Ontology::SERVICE_ID => $onto,
            FileSystemService::SERVICE_ID => $fs,
            DeliveryFactory::SERVICE_ID => $df,
            \taoTests_models_classes_TestsService::class => $testService,
            ValidationService::SERVICE_ID => new ValidationService(),
            LoggerService::SERVICE_ID => new LoggerService(),
            ImportService::SERVICE_ID => $importService,
            SessionService::SERVICE_ID => $this->getSessionServiceMock($session->reveal()),
            TestModelService::SERVICE_ID => $testModel,
            \taoQtiTest_models_classes_QtiTestService::class => $qtiTestService,
            \common_ext_ExtensionsManager::SERVICE_ID => $extMan->reveal(),
            FileReferenceSerializer::SERVICE_ID => $fileReferencer,
            EventManager::SERVICE_ID => new EventManager(),
            MetadataService::SERVICE_ID => $meta,
            CatService::SERVICE_ID => new CatService(),
            \taoItems_models_classes_ItemsService::class => $itemService,
            DeliveryAssemblyService::class => $deliveryService,
            AuthoringService::SERVICE_ID => new AuthoringService()
        ]);
        $deliveryService->setServiceLocator($serviceLocator);
        $itemService->setServiceLocator($serviceLocator);
        $importService->setServiceLocator($serviceLocator);
        $meta->setServiceLocator($serviceLocator);
        $fileReferencer->setServiceLocator($serviceLocator);
        $fs->setServiceLocator($serviceLocator);
        $df->setServiceLocator($serviceLocator);
        $session->setServiceLocator($serviceLocator);
        $testService->setServiceLocator($serviceLocator);
        $testModel->setServiceLocator($serviceLocator);
        $qtiTestService->setServiceLocator($serviceLocator);

        // create task
        $task = new CompileDelivery();
        $task->setServiceLocator($serviceLocator);
        $task->setModel($onto);
        return $task;
    }
    
    public function testCompilation() {
        $task = $this->buildCompileDelivery();
        $report = $this->importTest($task->getServiceLocator());
        echo \helpers_Report::renderToCommandLine($report);
        $this->assertEquals(\common_report_Report::TYPE_SUCCESS, $report->getType());
        
        $report = $task(['test' => 'http://sampletest']);
    }
}
