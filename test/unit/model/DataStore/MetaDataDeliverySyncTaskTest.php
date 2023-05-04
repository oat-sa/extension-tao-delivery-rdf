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
 * Copyright (c) 2021  (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\test\unit\model\DataStore;

use core_kernel_persistence_smoothsql_SmoothModel;
use oat\generis\model\data\Ontology;
use oat\generis\test\OntologyMockTrait;
use oat\generis\test\TestCase;
use oat\oatbox\reporting\Report;
use oat\tao\model\featureFlag\FeatureFlagChecker;
use oat\tao\model\featureFlag\FeatureFlagCheckerInterface;
use oat\tao\model\metadata\compiler\ResourceJsonMetadataCompiler;
use oat\tao\model\taskQueue\QueueDispatcher;
use oat\taoDeliveryRdf\model\DataStore\MetaDataDeliverySyncTask;
use oat\taoDeliveryRdf\model\DataStore\PersistDataService;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use PHPUnit\Framework\MockObject\MockObject;
use taoQtiTest_models_classes_QtiTestService;

class MetaDataDeliverySyncTaskTest extends TestCase
{
    use OntologyMockTrait;

    /** @var QueueDispatcher|MockObject */
    private $queueDispatcher;

    /** @var PersistDataService|MockObject */
    private $persistDataService;

    /** @var MockObject|taoQtiTest_models_classes_QtiTestService */
    private $qtiTestService;

    /** @var ResourceJsonMetadataCompiler|MockObject */
    private $resourceJsonMetadataCompiler;

    /** @var \Zend\ServiceManager\ServiceLocatorInterface */
    private $serviceLocator;

    /** @var core_kernel_persistence_smoothsql_SmoothModel */
    private $ontology;

    /** @var MetaDataDeliverySyncTask */
    private $subject;

    /** @var FeatureFlagCheckerInterface|MockObject */
    private $featureFlagChecker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queueDispatcher = $this->createMock(QueueDispatcher::class);
        $this->persistDataService = $this->createMock(PersistDataService::class);
        $this->qtiTestService = $this->createMock(taoQtiTest_models_classes_QtiTestService::class);
        $this->resourceJsonMetadataCompiler = $this->createMock(ResourceJsonMetadataCompiler::class);
        $this->featureFlagChecker = $this->createMock(FeatureFlagCheckerInterface::class);
        $this->ontology = $this->getOntologyMock();
        $this->serviceLocator = $this->getServiceLocatorMock(
            [
                QueueDispatcher::SERVICE_ID => $this->queueDispatcher,
                PersistDataService::class => $this->persistDataService,
                taoQtiTest_models_classes_QtiTestService::class => $this->qtiTestService,
                ResourceJsonMetadataCompiler::SERVICE_ID => $this->resourceJsonMetadataCompiler,
                Ontology::SERVICE_ID => $this->ontology,
                FeatureFlagChecker::class => $this->featureFlagChecker,
            ]
        );

        $this->subject = new MetaDataDeliverySyncTask();
    }

    public function testJsonSerialize(): void
    {
        $this->assertEquals(
            MetaDataDeliverySyncTask::class,
            $this->subject->jsonSerialize()
        );
    }

    public function testInvoke()
    {
        $this->persistDataService->method('persist');
        $this->qtiTestService->method('getItems')->willReturn([]);
        $this->queueDispatcher->method('createTask')->willReturn(true);
        $this->resourceJsonMetadataCompiler->method('compile')->willReturn([]);
        $this->subject->setServiceLocator($this->serviceLocator);

        $this->featureFlagChecker
            ->method('isEnabled')
            ->willReturn(false);

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
