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

class DeliveryRdfRelationService extends ConfigurableService implements ResourceRelationServiceInterface
{
    use OntologyAwareTrait;

    private const RELATION_TYPE = 'delivery';

    public function findRelations(FindAllQuery $query): ResourceRelationCollection
    {
        $testUri = $query->getSourceId();

        if (!$testUri) {
            return new ResourceRelationCollection(...[]);
        }

        $relations = [];

        foreach ($this->getDeliveries($query) as $delivery) {
            if (!$delivery instanceof core_kernel_classes_Resource) {
                continue;
            }

            if ($this->resolveOriginUri($delivery) !== $testUri) {
                continue;
            }

            $relations[] = new ResourceRelation(
                self::RELATION_TYPE,
                $delivery->getUri(),
                $delivery->getLabel()
            );
        }

        return new ResourceRelationCollection(...$relations);
    }

    private function getDeliveries(FindAllQuery $query): iterable
    {
        if ($query->getClassId()) {
            return $this->getClass($query->getClassId())->getInstances(true);
        }

        return $this->getDeliveryAssemblyService()->getAllAssemblies();
    }

    private function resolveOriginUri(core_kernel_classes_Resource $delivery): ?string
    {
        try {
            $origin = $this->getDeliveryAssemblyService()->getOrigin($delivery);
        } catch (\Throwable $exception) {
            return null;
        }

        return $origin instanceof core_kernel_classes_Resource
            ? $origin->getUri()
            : null;
    }

    private function getDeliveryAssemblyService(): DeliveryAssemblyService
    {
        return $this->getServiceLocator()->get(DeliveryAssemblyService::class);
    }
}
