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

use oat\generis\test\TestCase;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;

class DeliveryCreatedEventTest extends TestCase
{
    private const DELIVERY_URI = 'http://uri';

    public function testSerializeForWebhook()
    {
        $event = new DeliveryCreatedEvent(self::DELIVERY_URI);
        $result = $event->serializeForWebhook();

        $this->assertArrayHasKey('delivery_id', $result);
        $this->assertEquals(self::DELIVERY_URI, $result['delivery_id']);
    }

    public function testJsonSerialize()
    {
        $event = new DeliveryCreatedEvent(self::DELIVERY_URI);
        $result = $event->jsonSerialize();
        $this->assertArrayHasKey('delivery', $result);
        $this->assertEquals($result['delivery'], self::DELIVERY_URI);
    }

    public function testGetName()
    {
        $event = new DeliveryCreatedEvent(self::DELIVERY_URI);
        $result = $event->getName();

        $this->assertEquals($result, DeliveryCreatedEvent::class);
    }

    public function testGetWebhookEventName()
    {
        $event = new DeliveryCreatedEvent(self::DELIVERY_URI);
        $result = $event->getWebhookEventName();
        $this->assertEquals('DeliveryCreatedEvent', $result);
    }
}
