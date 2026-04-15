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

namespace oat\taoDeliveryRdf\test\unit\model\Usage;

use core_kernel_classes_Resource;
use oat\generis\model\data\Ontology;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoDeliveryRdf\model\Usage\DeliveryUsageService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use tao_helpers_Uri;

class DeliveryUsageServiceTest extends TestCase
{
    private const DELIVERY_URI = 'http://tao.local/delivery';

    public function testReturnsSourceTestForDelivery(): void
    {
        $deliveryAssemblyService = $this->createMock(DeliveryAssemblyService::class);
        $ontology = $this->createMock(Ontology::class);

        $delivery = $this->createResource(self::DELIVERY_URI, 'Delivery');
        $test = $this->createResource('http://tao.local/test', 'Source Test');

        $ontology->method('getResource')->with(self::DELIVERY_URI)->willReturn($delivery);
        $deliveryAssemblyService->method('getOrigin')->with($delivery)->willReturn($test);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['uri' => tao_helpers_Uri::encode(self::DELIVERY_URI)]);

        $service = new DeliveryUsageService($deliveryAssemblyService, $ontology);
        $result = $service->getSourceTestByDelivery($request);

        $this->assertSame(1, $result['totalResults']);
        $this->assertCount(1, $result['data']);
        $this->assertSame('http://tao.local/test', $result['data'][0]['sourceTestUri']);
        $this->assertSame('Source Test', $result['data'][0]['sourceTestLabel']);
    }

    public function testReturnsEmptyWhenOriginCannotBeResolved(): void
    {
        $deliveryAssemblyService = $this->createMock(DeliveryAssemblyService::class);
        $ontology = $this->createMock(Ontology::class);

        $ontology
            ->method('getResource')
            ->willThrowException(new \RuntimeException('Missing delivery'));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['uri' => tao_helpers_Uri::encode(self::DELIVERY_URI)]);

        $service = new DeliveryUsageService($deliveryAssemblyService, $ontology);
        $result = $service->getSourceTestByDelivery($request);

        $this->assertSame(0, $result['totalResults']);
        $this->assertSame([], $result['data']);
    }

    public function testThrowsBadRequestWhenUriMissing(): void
    {
        $deliveryAssemblyService = $this->createMock(DeliveryAssemblyService::class);
        $ontology = $this->createMock(Ontology::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([]);

        $service = new DeliveryUsageService($deliveryAssemblyService, $ontology);

        $this->expectException(\common_exception_BadRequest::class);
        $service->getSourceTestByDelivery($request);
    }

    private function createResource(string $uri, string $label): core_kernel_classes_Resource
    {
        $resource = $this->createMock(core_kernel_classes_Resource::class);
        $resource->method('getUri')->willReturn($uri);
        $resource->method('getLabel')->willReturn($label);
        $resource->method('getParentClassesIds')->willReturn([]);

        return $resource;
    }
}
