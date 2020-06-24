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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

namespace oat\taoDeliveryRdf\test\unit\model\event;

use core_kernel_classes_Resource;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;

class DeliveryCreatedEventTest extends TestCase
{
    private const DELIVERY_URI = 'https://delivery';
    private const TEST_URI = 'https://test_uri';

    /** @var core_kernel_classes_Resource|MockObject */
    private $deliveryMock;

    /** @var core_kernel_classes_Resource|MockObject */
    private $testResource;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testResource = $this->createMock(core_kernel_classes_Resource::class);
        $this->testResource->method('getUri')->willReturn(self::TEST_URI);

        $this->deliveryMock = $this->createMock(core_kernel_classes_Resource::class);
        $this->deliveryMock->method('getUri')->willReturn(self::DELIVERY_URI);
        $this->deliveryMock->method('getOnePropertyValue')->willReturn($this->testResource);
    }

    public function testSerializeForWebhook(): void
    {
        $event = new DeliveryCreatedEvent($this->deliveryMock, self::TEST_URI);
        $result = $event->serializeForWebhook();

        $this->assertArrayHasKey('deliveryId', $result);
        $this->assertArrayHasKey('testId', $result);
        $this->assertEquals(self::DELIVERY_URI, $result['deliveryId']);
        $this->assertEquals(self::TEST_URI, $result['testId']);
    }

    public function testJsonSerialize(): void
    {
        $event = new DeliveryCreatedEvent($this->deliveryMock, self::TEST_URI);
        $result = $event->jsonSerialize();
        $this->assertArrayHasKey('delivery', $result);
        $this->assertEquals($result['delivery'], self::DELIVERY_URI);
        $this->assertArrayHasKey('testId', $result);
        $this->assertEquals($result['testId'], self::TEST_URI);
    }

    public function testGetName(): void
    {
        $event = new DeliveryCreatedEvent($this->deliveryMock, self::TEST_URI);
        $result = $event->getName();

        $this->assertEquals($result, DeliveryCreatedEvent::class);
    }

    public function testGetWebhookEventName(): void
    {
        $event = new DeliveryCreatedEvent($this->deliveryMock, self::TEST_URI);
        $result = $event->getWebhookEventName();
        $this->assertEquals('DeliveryCreatedEvent', $result);
    }

    public function testGetUri(): void
    {
        $event = new DeliveryCreatedEvent($this->deliveryMock, self::TEST_URI);
        $result = $event->getDeliveryUri();

         $this->assertEquals(self::DELIVERY_URI, $result);
    }

    public function testGetOriginTestUri_WhenEventCreatedWithoutTestUri_ThenGetTestUriFromDeliveryResource(): void
    {
        $event = new DeliveryCreatedEvent($this->deliveryMock);

        $testUri = $event->getOriginTestUri();
        self::assertSame(self::TEST_URI, $testUri, 'Method must return correct origin test URI.');
    }

    public function testGetOriginTestUri_WhenDeliveryCreatedWithoutTest_TryGetTestUriFromDeliveryResource(): void
    {
        $deliveryMock = $this->getDeliveryWithoutTestMock();

        $event = new DeliveryCreatedEvent($deliveryMock);

        $testUri = $event->getOriginTestUri();
        self::assertNull($testUri, 'Method must return NULL if delivery does not have origin test uri property.');
    }

    public function testGetOriginTestUri_WhenDeliveryCreatedWithoutTest_TestUriIsNullInSerializedEvent(): void
    {
        $expectedSerializedEvent = [
            'delivery' => self::DELIVERY_URI,
            'testId' => null,
        ];
        $expectedSerializedForWebhookEvent = [
            'deliveryId' => self::DELIVERY_URI,
            'testId' => null,
        ];
        $deliveryMock = $this->getDeliveryWithoutTestMock();

        $event = new DeliveryCreatedEvent($deliveryMock);

        self::assertSame($expectedSerializedEvent, $event->jsonSerialize(), 'Serialized event must be as expected.');
        self::assertSame(
            $expectedSerializedForWebhookEvent,
            $event->serializeForWebhook(),
            'Serialized for webhook event must be as expected.'
        );
    }

    /**
     * @return core_kernel_classes_Resource|MockObject
     */
    private function getDeliveryWithoutTestMock(): core_kernel_classes_Resource
    {
        $deliveryMock = $this->createMock(core_kernel_classes_Resource::class);
        $deliveryMock->method('getUri')
            ->willReturn(self::DELIVERY_URI);
        $deliveryMock->method('getOnePropertyValue')
            ->willReturn(null);

        return $deliveryMock;
    }
}
