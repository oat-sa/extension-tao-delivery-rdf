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
 * Foundation, Inc., 31 Milk St # 960789 Boston, MA 02196 USA.
 *
 * Copyright (c) 2026 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\test\unit\model\Resource\Service;

use core_kernel_classes_Resource;
use oat\generis\model\data\Ontology;
use oat\generis\test\TestCase;
use oat\tao\model\resources\relation\FindAllQuery;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoDeliveryRdf\model\Resource\Service\DeliveryRelationService;

class DeliveryRelationServiceTest extends TestCase
{
    private const DELIVERY_URI = 'http://tao.local/delivery-1';
    private const TEST_URI = 'http://tao.local/test';

    private DeliveryRelationService $subject;

    private $ontology;
    private $deliveryAssemblyService;
    private $delivery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ontology = $this->createMock(Ontology::class);
        $this->deliveryAssemblyService = $this->createMock(DeliveryAssemblyService::class);
        $this->delivery = $this->createMock(core_kernel_classes_Resource::class);

        $this->ontology
            ->method('getResource')
            ->with(self::DELIVERY_URI)
            ->willReturn($this->delivery);

        $serviceLocator = $this->getServiceLocatorMock([
            Ontology::SERVICE_ID => $this->ontology,
            DeliveryAssemblyService::class => $this->deliveryAssemblyService,
        ]);

        $this->subject = new DeliveryRelationService();
        $this->subject->setServiceLocator($serviceLocator);
    }

    public function testReturnsOriginTestRelation(): void
    {
        $test = $this->createMock(core_kernel_classes_Resource::class);
        $test->method('getUri')->willReturn(self::TEST_URI);
        $test->method('getLabel')->willReturn('Test Label');

        $this->deliveryAssemblyService
            ->expects($this->once())
            ->method('getOrigin')
            ->with($this->delivery)
            ->willReturn($test);

        $result = $this->subject->findRelations(new FindAllQuery(self::DELIVERY_URI, null, 'delivery_test'));

        $this->assertCount(1, $result);
        $relation = $result->getIterator()->current();
        $this->assertSame('test', $relation->getType());
        $this->assertSame(self::TEST_URI, $relation->getId());
        $this->assertSame('Test Label', $relation->getLabel());
    }

    public function testReturnsEmptyWhenOriginCannotBeResolved(): void
    {
        $this->deliveryAssemblyService
            ->expects($this->once())
            ->method('getOrigin')
            ->with($this->delivery)
            ->willThrowException(new \RuntimeException('Missing origin'));

        $result = $this->subject->findRelations(new FindAllQuery(self::DELIVERY_URI, null, 'delivery_test'));

        $this->assertCount(0, $result);
    }
}
