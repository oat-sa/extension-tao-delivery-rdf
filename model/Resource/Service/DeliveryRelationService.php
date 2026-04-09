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

namespace oat\taoDeliveryRdf\model\Resource\Service;

use core_kernel_classes_Resource;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\resources\relation\FindAllQuery;
use oat\tao\model\resources\relation\ResourceRelation;
use oat\tao\model\resources\relation\ResourceRelationCollection;
use oat\tao\model\resources\relation\service\ResourceRelationServiceInterface;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;

class DeliveryRelationService extends ConfigurableService implements ResourceRelationServiceInterface
{
    use OntologyAwareTrait;

    private const RELATION_TYPE = 'test';

    public function findRelations(FindAllQuery $query): ResourceRelationCollection
    {
        $deliveryUri = $query->getSourceId();

        if (!$deliveryUri) {
            return new ResourceRelationCollection(...[]);
        }

        $delivery = $this->getResource($deliveryUri);

        if (!$delivery instanceof core_kernel_classes_Resource) {
            return new ResourceRelationCollection(...[]);
        }

        try {
            $origin = $this->getDeliveryAssemblyService()->getOrigin($delivery);
        } catch (\Throwable $exception) {
            return new ResourceRelationCollection(...[]);
        }

        if (!$origin instanceof core_kernel_classes_Resource) {
            return new ResourceRelationCollection(...[]);
        }

        return new ResourceRelationCollection(
            new ResourceRelation(
                self::RELATION_TYPE,
                $origin->getUri(),
                $origin->getLabel()
            )
        );
    }

    private function getDeliveryAssemblyService(): DeliveryAssemblyService
    {
        return $this->getServiceLocator()->get(DeliveryAssemblyService::class);
    }
}
