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
use oat\taoDeliveryRdf\model\Usage\TestUsageService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use tao_helpers_Uri;

class TestUsageServiceTest extends TestCase
{
    private const TEST_URI = 'http://tao.local/test';

    public function testReturnsDeliveriesForProvidedTest(): void
    {
        $deliveryAssemblyService = $this->createMock(DeliveryAssemblyService::class);
        $ontology = $this->createMock(Ontology::class);

        $deliveryOne = $this->createDelivery('http://tao.local/delivery-1', 'Delivery 1', ['http://class/a']);
        $deliveryTwo = $this->createDelivery('http://tao.local/delivery-2', 'Delivery 2', ['http://class/b']);

        $deliveryAssemblyService
            ->method('findAssembliesByOrigin')
            ->with(self::TEST_URI)
            ->willReturn([$deliveryOne, $deliveryTwo]);

        $deliveryAssemblyService
            ->method('getCompilationDate')
            ->willReturnMap([
                [$deliveryOne, '2026-04-09'],
                [$deliveryTwo, '2026-04-01'],
            ]);

        $classA = $this->createMock(\core_kernel_classes_Class::class);
        $classA->method('getLabel')->willReturn('Folder A');
        $classB = $this->createMock(\core_kernel_classes_Class::class);
        $classB->method('getLabel')->willReturn('Folder B');

        $ontology->method('getClass')->willReturnMap([
            ['http://class/a', $classA],
            ['http://class/b', $classB],
        ]);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([
            'uri' => tao_helpers_Uri::encode(self::TEST_URI),
            'rows' => 25,
            'page' => 1,
            'sortby' => 'label',
            'sortorder' => 'asc',
        ]);

        $service = new TestUsageService($deliveryAssemblyService, $ontology);

        $result = $service->getDeliveriesWhereTestUsed($request);

        $this->assertSame(2, $result['totalResults']);
        $this->assertCount(2, $result['data']);
        $this->assertSame('http://tao.local/delivery-1', $result['data'][0]['deliveryId']);
        $this->assertSame('Delivery 1', $result['data'][0]['label']);
        $this->assertSame('Folder A', $result['data'][0]['classPath']);
        $this->assertSame('04/09/2026 - 00:00', $result['data'][0]['publicationTime']);
        $this->assertSame('http://tao.local/delivery-2', $result['data'][1]['deliveryId']);
    }

    private function createDelivery(string $uri, string $label, array $parents): core_kernel_classes_Resource
    {
        $delivery = $this->createMock(core_kernel_classes_Resource::class);
        $delivery->method('getUri')->willReturn($uri);
        $delivery->method('getLabel')->willReturn($label);
        $delivery->method('getParentClassesIds')->willReturn($parents);

        return $delivery;
    }

}
