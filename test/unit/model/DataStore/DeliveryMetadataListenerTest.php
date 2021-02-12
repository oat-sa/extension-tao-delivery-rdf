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

    /** @var ServiceLocatorInterface */
    private $serviceLocator;

    /** @var QueueDispatcher|MockObject */
    private $queueDispatcher;

    /** @var FeatureFlagChecker */
    private $featureFlagChecker;

    /** @var core_kernel_persistence_smoothsql_SmoothModel */
    private $ontology;

    /** @var FeatureFlagChecker|MockObject */
    private $log;

    /** @var DeliveryMetadataListener */
    private $subject;

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
        $class->createInstance('Bogus');

        $event = $this->createMock(DeliveryCreatedEvent::class);
        $event->expects($this->once())->method('getDeliveryUri');

        $this->featureFlagChecker->method('isEnabled')->willReturn(true);
        $this->queueDispatcher->expects($this->once())->method('createTask')->willReturn(true);

        $this->subject->whenDeliveryIsPublished($event);
    }
}
