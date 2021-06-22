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

namespace oat\taoDeliveryRdf\test\unit\model\Delivery\DataAccess;

use InvalidArgumentException;
use oat\generis\test\TestCase;
use oat\taoDeliveryRdf\model\Delivery\Business\Domain\DeliveryNamespace;
use oat\taoDeliveryRdf\model\Delivery\DataAccess\DeliveryNamespaceRegistry;

class DeliveryNamespaceRegistryTest extends TestCase
{
    public function testDifferentDeliveryNamespace(): void
    {
        $deliveryNamespace = new DeliveryNamespace('https://taotesting.com');

        $sut = new DeliveryNamespaceRegistry(
            new DeliveryNamespace('https://hub.taotesting.com'),
            $deliveryNamespace
        );

        static::assertSame(
            $deliveryNamespace,
            $sut->get()
        );
    }

    public function testNullDeliveryNamespace(): void
    {
        $sut = new DeliveryNamespaceRegistry(
            new DeliveryNamespace('https://hub.taotesting.com')
        );

        static::assertNull(
            $sut->get()
        );
    }

    public function testSameDeliveryNamespace(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $deliveryNamespace = new DeliveryNamespace('https://taotesting.com');

        new DeliveryNamespaceRegistry(
            clone $deliveryNamespace,
            $deliveryNamespace
        );
    }
}
