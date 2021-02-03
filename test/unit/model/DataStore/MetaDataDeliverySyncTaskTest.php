<?php


namespace oat\taoDeliveryRdf\test\unit\model\assembly;


use core_kernel_persistence_smoothsql_SmoothModel;
use oat\generis\model\data\Ontology;
use oat\generis\test\OntologyMockTrait;
use oat\generis\test\TestCase;
use oat\oatbox\reporting\Report;
use oat\tao\model\metadata\compiler\ResourceJsonMetadataCompiler;
use oat\tao\model\taskQueue\QueueDispatcher;
use oat\taoDeliveryRdf\model\DataStore\MetaDataDeliverySyncTask;
use oat\taoDeliveryRdf\model\DataStore\PersistDataService;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use taoQtiTest_models_classes_QtiTestService;

class MetaDataDeliverySyncTaskTest extends TestCase
{
    use OntologyMockTrait;

    /** @var QueueDispatcher|\PHPUnit\Framework\MockObject\MockObject */
    private $queueDispatcher;

    /** @var PersistDataService|\PHPUnit\Framework\MockObject\MockObject */
    private $persistDataService;

    /** @var \PHPUnit\Framework\MockObject\MockObject|taoQtiTest_models_classes_QtiTestService */
    private $qtiTestService;

    /** @var ResourceJsonMetadataCompiler|\PHPUnit\Framework\MockObject\MockObject */
    private $resourceJsonMetadataCompiler;

    /** @var \Zend\ServiceManager\ServiceLocatorInterface */
    private $serviceLocator;

    /** @var core_kernel_persistence_smoothsql_SmoothModel */
    private $ontology;

    /** @var MetaDataDeliverySyncTask */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queueDispatcher = $this->createMock(QueueDispatcher::class);
        $this->persistDataService = $this->createMock(PersistDataService::class);
        $this->qtiTestService = $this->createMock(taoQtiTest_models_classes_QtiTestService::class);
        $this->resourceJsonMetadataCompiler = $this->createMock(ResourceJsonMetadataCompiler::class);
        $this->ontology = $this->getOntologyMock();

        $this->serviceLocator = $this->getServiceLocatorMock([
            QueueDispatcher::SERVICE_ID => $this->queueDispatcher,
            PersistDataService::class => $this->persistDataService,
            taoQtiTest_models_classes_QtiTestService::class => $this->qtiTestService,
            ResourceJsonMetadataCompiler::SERVICE_ID => $this->resourceJsonMetadataCompiler,
            Ontology::SERVICE_ID => $this->ontology
        ]);

        $this->subject = new MetaDataDeliverySyncTask;
    }

    public function testJsonSerialize(): void
    {
        $this->assertEquals(
            MetaDataDeliverySyncTask::class,
            $this->subject->jsonSerialize()
        );
    }

    public function test__invoke()
    {
        $this->persistDataService->method('persist');
        $this->qtiTestService->method('getItems')->willReturn([]);
        $this->queueDispatcher->method('createTask')->willReturn(true);
        $this->resourceJsonMetadataCompiler->method('compile')->willReturn([]);
        $this->subject->setServiceLocator($this->serviceLocator);

        $class = $this->ontology->getClass('http://tao.tld/bogusUri');
        $mockDelivery = $class->createInstance('Bogus');
        $mockTest = $class->createInstance('TestBogus');
        $mockDelivery->setPropertyValue(
            $this->ontology->getProperty(DeliveryAssemblyService::PROPERTY_ORIGIN),
            $mockTest
        );

        $param = [
            'deliveryId' => $mockDelivery->getUri(),
            'max_tries' => 1,
            'count' => 0
        ];
        $subject = $this->subject;
        $response = $subject($param);
        $expected = new Report(Report::TYPE_SUCCESS);
        $expected->setMessage('Success MetaData syncing for delivery: ' . $mockDelivery->getUri());
        $this->assertEquals($expected, $response);
    }
}