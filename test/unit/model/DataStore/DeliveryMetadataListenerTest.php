<?php


namespace oat\taoDeliveryRdf\test\unit\model\assembly;


use core_kernel_persistence_smoothsql_SmoothModel;
use oat\generis\test\OntologyMockTrait;
use oat\generis\test\TestCase;
use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\log\LoggerService;
use oat\tao\model\featureFlag\FeatureFlagChecker;
use oat\tao\model\taskQueue\QueueDispatcher;
use oat\taoDeliveryRdf\model\DataStore\DeliveryMetadataListener;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Zend\ServiceManager\ServiceLocatorInterface;

class DeliveryMetadataListenerTest extends TestCase
{
    use OntologyMockTrait;
    use LoggerAwareTrait;

    /** @var ServiceLocatorInterface */
    private $serviceLocator;

    /** @var QueueDispatcher|MockObject */
    private $queueDispatcher;

    /** @var FeatureFlagChecker */
    private $featureFlagChecker;

    /** @var core_kernel_persistence_smoothsql_SmoothModel */
    private $ontology;

    /** @var DeliveryMetadataListener */
    private $subject;

    /** @var FeatureFlagChecker|MockObject */
    private $log;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queueDispatcher = $this->createMock(QueueDispatcher::class);
        $this->featureFlagChecker = $this->createMock(FeatureFlagChecker::class);
        $this->log = $this->createMock(LoggerService::class);
        $this->ontology = $this->getOntologyMock();

        $this->serviceLocator = $this->getServiceLocatorMock([
            QueueDispatcher::SERVICE_ID => $this->queueDispatcher,
            FeatureFlagChecker::class => $this->featureFlagChecker,
            LoggerService::SERVICE_ID => $this->log
        ]);

        $this->subject = new DeliveryMetadataListener();

        $this->subject->setServiceLocator($this->serviceLocator);
    }

    public function testWhenDeliveryIsPublished(): void
    {

        $class = $this->ontology->getClass('http://tao.tld/bogusUri');
        $mockDelivery = $class->createInstance('Bogus');

        $event = $this->createMock(DeliveryCreatedEvent::class);
        $event->expects($this->once())->method('getDeliveryUri');

        $this->featureFlagChecker->method('isEnabled')->willReturn(true);
        $this->queueDispatcher->expects($this->once())->method('createTask')->willReturn(true);

        $this->subject->whenDeliveryIsPublished($event);
    }
}