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
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\test\unit\model\theme\Listener;

use Exception;
use oat\generis\test\TestCase;
use oat\oatbox\log\LoggerService;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoDeliveryRdf\model\theme\Exception\ThemeAutoSetNotSupported;
use oat\taoDeliveryRdf\model\theme\Listener\ThemeAutoSetListener;
use oat\taoDeliveryRdf\model\theme\Service\ThemeAutoSetService;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class ThemeAutoSetListenerTest extends TestCase
{
    private const DELIVERY_ID = 'myDeliveryId';

    /** @var ThemeAutoSetListener */
    private $subject;

    /** @var ThemeAutoSetService|MockObject */
    private $themeAutoSetService;

    /** @var MockObject|LoggerInterface */
    private $logger;

    /** @var DeliveryCreatedEvent|MockObject */
    private $deliveryEvent;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->themeAutoSetService = $this->createMock(ThemeAutoSetService::class);
        $this->deliveryEvent = $this->createMock(DeliveryCreatedEvent::class);
        $this->deliveryEvent
            ->method('getDeliveryUri')
            ->willReturn(self::DELIVERY_ID);

        $this->subject = new ThemeAutoSetListener();
        $this->subject->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    ThemeAutoSetService::class => $this->themeAutoSetService,
                    LoggerService::SERVICE_ID => $this->logger,
                ]
            )
        );
    }

    public function testWhenDeliveryIsCreatedWithSuccess(): void
    {
        $this->themeAutoSetService
            ->expects($this->once())
            ->method('setThemeByDelivery')
            ->with(self::DELIVERY_ID);

        $this->subject->whenDeliveryIsCreated($this->deliveryEvent);
    }

    public function testWhenDeliveryIsCreatedWithAutoSetNotSupported(): void
    {
        $this->themeAutoSetService
            ->expects($this->once())
            ->method('setThemeByDelivery')
            ->willThrowException(new ThemeAutoSetNotSupported('error'));

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('AutoSet Theme not supported: error');

        $this->subject->whenDeliveryIsCreated($this->deliveryEvent);
    }

    public function testWhenDeliveryIsCreatedWithUnknownError(): void
    {
        $this->themeAutoSetService
            ->expects($this->once())
            ->method('setThemeByDelivery')
            ->willThrowException(new Exception('unknown error'));

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Could not set theme: unknown error');

        $this->subject->whenDeliveryIsCreated($this->deliveryEvent);
    }
}
