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

namespace oat\taoDeliveryRdf\test\unit\model;

use core_kernel_classes_Class;
use core_kernel_classes_Resource;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use PHPUnit\Framework\TestCase;

class DeliveryAssemblyServiceTest extends TestCase
{
    public function testFindAssembliesByOriginUsesExpectedSearchFilterAndOptions(): void
    {
        $expected = [
            'http://tao.local/delivery-1' => $this->createMock(core_kernel_classes_Resource::class),
        ];

        $rootClass = $this->createMock(core_kernel_classes_Class::class);
        $rootClass
            ->expects($this->once())
            ->method('searchInstances')
            ->with(
                [DeliveryAssemblyService::PROPERTY_ORIGIN => 'http://tao.local/test-1'],
                [
                    'recursive' => true,
                    'like' => false,
                ]
            )
            ->willReturn($expected);

        $service = $this->getMockBuilder(DeliveryAssemblyService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRootClass'])
            ->getMock();

        $service
            ->expects($this->once())
            ->method('getRootClass')
            ->willReturn($rootClass);

        $result = $service->findAssembliesByOrigin('http://tao.local/test-1');

        $this->assertSame($expected, $result);
    }

    public function testFindAssembliesByOriginMergesCustomOptions(): void
    {
        $rootClass = $this->createMock(core_kernel_classes_Class::class);
        $rootClass
            ->expects($this->once())
            ->method('searchInstances')
            ->with(
                [DeliveryAssemblyService::PROPERTY_ORIGIN => 'http://tao.local/test-1'],
                [
                    'recursive' => true,
                    'like' => false,
                    'limit' => 10,
                    'offset' => 20,
                ]
            )
            ->willReturn([]);

        $service = $this->getMockBuilder(DeliveryAssemblyService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRootClass'])
            ->getMock();

        $service
            ->expects($this->once())
            ->method('getRootClass')
            ->willReturn($rootClass);

        $service->findAssembliesByOrigin('http://tao.local/test-1', [
            'limit' => 10,
            'offset' => 20,
        ]);
    }
}
