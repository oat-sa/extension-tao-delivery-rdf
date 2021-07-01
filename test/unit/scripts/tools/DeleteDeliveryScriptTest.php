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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 *
 * @author Sergei Mikhailov <sergei.mikhailov@taotesting.com>
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\test\unit\scripts\tools;

use Exception;
use Laminas\ServiceManager\ServiceLocatorInterface;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\oatbox\reporting\Report;
use oat\taoDeliveryRdf\model\Delete\DeliveryDeleteRequest;
use oat\taoDeliveryRdf\model\Delete\DeliveryDeleteService;
use oat\taoDeliveryRdf\scripts\tools\DeleteDeliveryScript;

class DeleteDeliveryScriptTest extends TestCase
{
    private const DELIVERY_ID = 'https://www.taotesting.com/#1';

    /** @var DeleteDeliveryScript */
    private $sut;

    /** @var MockObject|DeliveryDeleteService */
    private $deliveryDeleteServiceMock;

    /**
     * @before
     */
    public function init(): void
    {
        $this->deliveryDeleteServiceMock = $this->createMock(DeliveryDeleteService::class);

        $this->sut = new DeleteDeliveryScript();
        $this->sut->setServiceLocator(
            $this->createServiceLocatorMock()
        );
    }

    public function testDeleteDelivery(): void
    {
        $this->assertDeliveryDeleteServiceCall(
            $this->createDeliveryDeleteRequest()
        );

        $this->assertReportContainsNoError(
            ($this->sut)(['--delivery', self::DELIVERY_ID])
        );
    }

    public function testDeleteDeliveryRecursively(): void
    {
        $this->assertDeliveryDeleteServiceCall(
            $this->createDeliveryDeleteRequest(true)
        );

        $this->assertReportContainsNoError(
            ($this->sut)(['--delivery', self::DELIVERY_ID, '-r'])
        );
    }

    public function testDeleteDeliveryWithException(): void
    {
        $this->assertDeliveryDeleteServiceCall(
            $this->createDeliveryDeleteRequest(),
            new Exception('test')
        );

        $this->assertReportContainsError(
            ($this->sut)(['--delivery', self::DELIVERY_ID])
        );
    }

    private function createServiceLocatorMock(): ServiceLocatorInterface
    {
        $serviceLocatorMock = $this->createMock(ServiceLocatorInterface::class);

        $serviceLocatorMock
            ->method('get')
            ->willReturnMap(
                [
                    [DeliveryDeleteService::class, $this->deliveryDeleteServiceMock],
                ]
            );

        return $serviceLocatorMock;
    }

    private function assertDeliveryDeleteServiceCall(
        DeliveryDeleteRequest $request,
        Exception $expectedException = null
    ): void {
        $this->deliveryDeleteServiceMock
            ->expects(static::once())
            ->method('execute')
            ->with($request)
            ->will(
                $expectedException
                    ? static::throwException($expectedException)
                    : static::returnValue(true)
            );

        $this->deliveryDeleteServiceMock
            ->method('getReport')
            ->willReturn(
                Report::createSuccess('test')
            );
    }

    private function createDeliveryDeleteRequest(bool $isRecursive = false): DeliveryDeleteRequest
    {
        $deliveryDeleteRequest = new DeliveryDeleteRequest(self::DELIVERY_ID);

        if ($isRecursive) {
            $deliveryDeleteRequest->setIsRecursive();
        }

        return $deliveryDeleteRequest;
    }

    private function assertReportContainsNoError(Report $report): void
    {
        static::assertFalse(
            $report->containsError()
        );
    }

    private function assertReportContainsError(Report $report): void
    {
        static::assertTrue(
            $report->containsError()
        );
    }
}
