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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA;
 *
 * @author Sergei Mikhailov <sergei.mikhailov@taotesting.com>
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\test\unit\model\Delivery\DataAccess;

use common_exception_ResourceNotFound as ResourceNotFoundException;
use core_kernel_classes_Class as KernelClass;
use core_kernel_classes_Resource as KernelResource;
use oat\generis\test\TestCase;
use oat\taoDeliveryRdf\model\Delivery\Business\Domain\DeliverySearchRequest;
use oat\taoDeliveryRdf\model\Delivery\DataAccess\DeliveryRepository;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use PHPUnit\Framework\MockObject\MockObject;

class DeliveryRepositoryTest extends TestCase
{
    /** @var DeliveryRepository */
    private $sut;

    /** @var KernelClass|MockObject */
    private $deliveryClassMock;

    /**
     * @before
     */
    public function init(): void
    {
        $this->deliveryClassMock = $this->createMock(KernelClass::class);

        $this->sut = $this->createPartialMock(DeliveryRepository::class, ['getClass']);
        $this->sut
            ->expects(static::once())
            ->method('getClass')
            ->with(DeliveryAssemblyService::CLASS_URI)
            ->willReturn($this->deliveryClassMock);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testFindOrFail(
        DeliverySearchRequest $searchRequest,
        bool $isClass = false,
        bool $exists = true,
        bool $isInstanceOfDeliveryClass = true,
        string $expectedExceptionClass = null
    ): void {
        $delivery = $this->expectDelivery($searchRequest, $isClass, $exists, $isInstanceOfDeliveryClass);

        if (null !== $expectedExceptionClass) {
            $this->expectException($expectedExceptionClass);
        }

        $this->assertSame($delivery, $this->sut->findOrFail($searchRequest));
    }

    public function testGetRootClass(): void
    {
        $this->assertSame(
            $this->deliveryClassMock,
            $this->sut->getRootClass()
        );
    }

    public function dataProvider(): array
    {
        return [
            'Valid Delivery' => [
                'searchRequest' => new DeliverySearchRequest('https://example.com#1'),
            ],
            'Class instead of a Delivery' => [
                'searchRequest' => new DeliverySearchRequest('https://example.com#1'),
                'isClass' => true,
                'exists' => true,
                'isInstanceOfDeliveryClass' => true,
                'expectedExceptionClass' => ResourceNotFoundException::class,
            ],
            'Nonexistent Delivery' => [
                'searchRequest' => new DeliverySearchRequest('https://example.com#1'),
                'isClass' => false,
                'exists' => false,
                'isInstanceOfDeliveryClass' => true,
                'expectedExceptionClass' => ResourceNotFoundException::class,
            ],
            'Not a Delivery' => [
                'searchRequest' => new DeliverySearchRequest('https://example.com#1'),
                'isClass' => false,
                'exists' => true,
                'isInstanceOfDeliveryClass' => false,
                'expectedExceptionClass' => ResourceNotFoundException::class,
            ],
        ];
    }

    private function expectDelivery(
        DeliverySearchRequest $searchRequest,
        bool $isClass,
        bool $exists,
        bool $isInstanceOfDeliveryClass
    ): KernelResource {
        $delivery = $this->createMock(KernelResource::class);

        $this->deliveryClassMock
            ->expects(static::once())
            ->method('getResource')
            ->with($searchRequest->getId())
            ->willReturn($delivery);

        $delivery
            ->method('isClass')
            ->willReturn($isClass);
        $delivery
            ->method('exists')
            ->willReturn($exists);
        $delivery
            ->method('isInstanceOf')
            ->with($this->deliveryClassMock)
            ->willReturn($isInstanceOfDeliveryClass);

        return $delivery;
    }
}
