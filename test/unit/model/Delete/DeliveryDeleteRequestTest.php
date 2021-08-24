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

namespace oat\taoDeliveryRdf\test\unit\model\Delete;

use oat\generis\test\TestCase;
use oat\taoDeliveryRdf\model\Delete\DeliveryDeleteRequest;

class DeliveryDeleteRequestTest extends TestCase
{
    private const DELIVERY_ID = 'testDeliveryId';

    /** @var DeliveryDeleteRequest */
    private $sut;

    /**
     * @before
     */
    public function init(): void
    {
        $this->sut = new DeliveryDeleteRequest(self::DELIVERY_ID);
    }

    public function testGetDeliveryResource(): void
    {
        static::assertSame(
            self::DELIVERY_ID,
            $this->sut->getDeliveryResource()->getUri()
        );
    }

    public function testDefaultScope(): void
    {
        static::assertTrue(
            $this->sut->isDeliveryRemovalRequested()
        );
        static::assertTrue(
            $this->sut->isExecutionsRemovalRequested()
        );
        static::assertFalse(
            $this->sut->isRecursive()
        );
    }

    public function testScopeWithResourcesRemovalRequest(): void
    {
        $this->sut->setIsRecursive();

        static::assertTrue(
            $this->sut->isDeliveryRemovalRequested()
        );
        static::assertTrue(
            $this->sut->isExecutionsRemovalRequested()
        );
        static::assertTrue(
            $this->sut->isRecursive()
        );
    }

    public function testScopeWithDeliveryExecutionRemovalRequest(): void
    {
        $this->sut->setDeliveryExecutionsOnly();

        static::assertFalse(
            $this->sut->isDeliveryRemovalRequested()
        );
        static::assertTrue(
            $this->sut->isExecutionsRemovalRequested()
        );
        static::assertFalse(
            $this->sut->isRecursive()
        );
    }
}
