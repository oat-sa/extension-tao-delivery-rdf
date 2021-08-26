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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 *
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\model\Delete;

use core_kernel_classes_Resource as KernelResource;

class DeliveryDeleteRequest
{
    private const SCOPE_DELIVERY   = 1 << 0;
    private const SCOPE_EXECUTIONS = 1 << 2;
    private const SCOPE_RESOURCES  = 1 << 3;

    /** @var string */
    private $deliveryId;

    /** @var int */
    private $scope = self::SCOPE_DELIVERY | self::SCOPE_EXECUTIONS;

    public function __construct(string $deliveryId)
    {
        $this->deliveryId = $deliveryId;
    }

    public function getDeliveryResource(): KernelResource
    {
        return new KernelResource($this->deliveryId);
    }

    public function isDeliveryRemovalRequested(): bool
    {
        return $this->hasInScope(self::SCOPE_DELIVERY);
    }

    public function isExecutionsRemovalRequested(): bool
    {
        return $this->hasInScope(self::SCOPE_EXECUTIONS);
    }

    public function isRecursive(): bool
    {
        return $this->hasInScope(self::SCOPE_RESOURCES);
    }

    public function setIsRecursive(): self
    {
        $this->scope |= self::SCOPE_RESOURCES;

        return $this;
    }

    public function setDeliveryExecutionsOnly(): self
    {
        $this->scope = self::SCOPE_EXECUTIONS;

        return $this;
    }

    private function hasInScope(int $scope): bool
    {
        return (bool)($this->scope & $scope);
    }
}
