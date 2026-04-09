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

namespace oat\taoDeliveryRdf\scripts\install;

use oat\oatbox\extension\InstallAction;
use oat\tao\model\resources\relation\service\ResourceRelationServiceProxy;
use oat\taoDeliveryRdf\model\Resource\Service\DeliveryRelationService;
use oat\taoDeliveryRdf\model\Resource\Service\TestRelationService;

class RegisterResourceRelationsService extends InstallAction
{
    private const TEST_DELIVERY_TYPE = 'test_delivery';
    private const DELIVERY_TEST_TYPE = 'delivery_test';

    public function __invoke($params): void
    {
        $resourceRelationService = $this->getServiceManager()->get(ResourceRelationServiceProxy::SERVICE_ID);
        $resourceRelationService->addService(self::TEST_DELIVERY_TYPE, TestRelationService::class);
        $resourceRelationService->addService(self::DELIVERY_TEST_TYPE, DeliveryRelationService::class);

        $this->getServiceManager()->register(ResourceRelationServiceProxy::SERVICE_ID, $resourceRelationService);
    }
}
