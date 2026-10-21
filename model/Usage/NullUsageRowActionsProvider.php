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

namespace oat\taoDeliveryRdf\model\Usage;

use core_kernel_classes_Resource;

/**
 * Default binding: no extension is installed, so rows are returned untouched and the
 * usage grid renders exactly as it did without the extension point.
 */
final class NullUsageRowActionsProvider implements UsageRowActionsProvider
{
    public function isExtensionEnabled(): bool
    {
        return false;
    }

    public function enrichDeliveryUsageRow(
        array $row,
        core_kernel_classes_Resource $delivery,
        string $testUri,
        string $currentUserId,
    ): array {
        return $row;
    }
}
