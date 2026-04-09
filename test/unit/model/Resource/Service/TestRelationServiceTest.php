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
use oat\generis\test\TestCase;
use oat\tao\model\resources\relation\FindAllQuery;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoDeliveryRdf\model\Resource\Service\TestRelationService;
use PHPUnit\Framework\MockObject\MockObject;

class TestRelationServiceTest extends TestCase
{
    private const TEST_URI = 'http://tao.local/test';

    /** @var DeliveryAssemblyService|MockObject */
    private $deliveryAssemblyService;

    private TestRelationService $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deliveryAssemblyService = $this->createMock(DeliveryAssemblyService::class);
        $serviceLocator = $this->getServiceLocatorMock([
            DeliveryAssemblyService::class => $this->deliveryAssemblyService,
        ]);

        $this->subject = new TestRelationService();
        $this->subject->setServiceLocator($serviceLocator);
    }

    public function testReturnsDeliveryRelationsMatchingOrigin(): void
    {
        $matchingDelivery = $this->createDeliveryMock('http://tao.local/delivery-1', 'Delivery 1');
        $otherDelivery = $this->createDeliveryMock('http://tao.local/delivery-2', 'Delivery 2');

        $this->deliveryAssemblyService
            ->method('getAllAssemblies')
            ->willReturn([$matchingDelivery, $otherDelivery]);

        $this->deliveryAssemblyService
            ->method('getOrigin')
            ->willReturnMap([
                [$matchingDelivery, $this->createResourceMock(self::TEST_URI)],
                [$otherDelivery, $this->createResourceMock('http://tao.local/other-test')],
            ]);

        $result = $this->subject->findRelations(new FindAllQuery(self::TEST_URI, null, 'test_delivery'));

        $this->assertCount(1, $result);
        $relation = $result->getIterator()->current();
        $this->assertSame('delivery', $relation->getType());
        $this->assertSame('http://tao.local/delivery-1', $relation->getId());
    }

    public function testSkipsDeliveryWhenOriginIsMalformed(): void
    {
        $delivery = $this->createDeliveryMock('http://tao.local/delivery-1', 'Delivery 1');

        $this->deliveryAssemblyService
            ->method('getAllAssemblies')
            ->willReturn([$delivery]);

        $this->deliveryAssemblyService
            ->method('getOrigin')
            ->with($delivery)
            ->willThrowException(new \RuntimeException('Malformed origin'));

        $result = $this->subject->findRelations(new FindAllQuery(self::TEST_URI, null, 'test_delivery'));

        $this->assertCount(0, $result);
    }

    private function createDeliveryMock(string $uri, string $label): MockObject
    {
        $delivery = $this->createMock(core_kernel_classes_Resource::class);
        $delivery->method('getUri')->willReturn($uri);
        $delivery->method('getLabel')->willReturn($label);

        return $delivery;
    }

    private function createResourceMock(string $uri): MockObject
    {
        $resource = $this->createMock(core_kernel_classes_Resource::class);
        $resource->method('getUri')->willReturn($uri);
        $resource->method('getLabel')->willReturn('Test Label');

        return $resource;
    }
}
