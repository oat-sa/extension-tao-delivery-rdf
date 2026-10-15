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
 *
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\model\Usage;

use core_kernel_classes_Resource;

class DefaultDeliveryUsageFilter implements DeliveryUsageFilterInterface
{
    public function matches(core_kernel_classes_Resource $delivery, string $filter): bool
    {
        $normalizedFilter = mb_strtolower(trim($filter));
        if ($normalizedFilter === '') {
            return true;
        }

        $searchableValues = [
            mb_strtolower(trim((string) $delivery->getLabel())),
            mb_strtolower(trim((string) $delivery->getUri())),
        ];

        foreach ($searchableValues as $searchableValue) {
            if ($searchableValue !== '' && mb_strpos($searchableValue, $normalizedFilter) !== false) {
                return true;
            }
        }

        return false;
    }
}