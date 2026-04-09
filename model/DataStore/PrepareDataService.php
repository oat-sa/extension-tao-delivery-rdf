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
 * Copyright (c) 2024 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\model\DataStore;

use core_kernel_classes_Resource;
use core_kernel_persistence_Exception;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\featureFlag\FeatureFlagChecker;
use oat\tao\model\featureFlag\FeatureFlagCheckerInterface;
use oat\tao\model\metadata\compiler\AdvancedJsonResourceMetadataCompiler;
use oat\tao\model\metadata\compiler\ResourceJsonMetadataCompiler;
use oat\tao\model\metadata\compiler\ResourceMetadataCompilerInterface;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use taoQtiTest_models_classes_QtiTestService;

class PrepareDataService extends ConfigurableService
{
    use OntologyAwareTrait;

    public function getResourceSyncData(
        string $resourceId,
        int $maxTries,
        bool $includeDeliveryMetaData,
        string $fileSystemId,
        bool $isDelete = false,
        ?string $firstTenantId = null
    ): ResourceSyncDTO {
        $compiler = $this->getMetaDataCompiler();
        $testUri = $resourceId;
        $deliveryMetaData = [];
        $testMetaData = [];
        $itemMetaData = [];
        $tenantId = null;

        if (!$isDelete) {
            if ($includeDeliveryMetaData) {
                $deliveryResource = $this->getResource($resourceId);
                $deliveryMetaData = $this->getResourceJsonMetadataCompiler()->compile($deliveryResource);
                $tenantId = $deliveryMetaData['tenant-id'] ?? null;
                $testUri = $this->getTestUri($deliveryResource);
            }

            $test = $this->getResource($testUri);
            $testMetaData = $compiler->compile($test);
            $testMetaData['first-tenant-id'] = $firstTenantId;

            $itemMetaData = $this->getItemMetaData($test, $compiler);
        }

        return new ResourceSyncDTO(
            $resourceId,
            $fileSystemId,
            $testUri,
            $isDelete,
            $tenantId,
            $firstTenantId,
            $maxTries,
            $deliveryMetaData,
            $testMetaData,
            $itemMetaData
        );
    }

    private function getItemMetaData(
        core_kernel_classes_Resource $test,
        ResourceMetadataCompilerInterface $compiler
    ): array {
        /** @var taoQtiTest_models_classes_QtiTestService $testService */
        $testService = $this->getServiceLocator()->get(taoQtiTest_models_classes_QtiTestService::class);
        $items = $testService->getItems($test);
        $itemMetaData = [];
        foreach ($items as $item) {
            $itemMetaData[] = $compiler->compile($item);
        }

        return $itemMetaData;
    }

    /**
     * @throws core_kernel_persistence_Exception
     */
    private function getTestUri(core_kernel_classes_Resource $deliveryResource): ?string
    {
        $testProperty = $this->getProperty(DeliveryAssemblyService::PROPERTY_ORIGIN);
        $test = $deliveryResource->getOnePropertyValue($testProperty);

        return $test ? $test->getUri() : null;
    }

    private function getMetaDataCompiler(): ResourceMetadataCompilerInterface
    {
        return $this->getFeatureFlagChecker()->isEnabled('FEATURE_FLAG_DATA_STORE_METADATA_V2')
            ? $this->getJsonMetadataCompiler()
            : $this->getResourceJsonMetadataCompiler();
    }

    private function getJsonMetadataCompiler(): ResourceMetadataCompilerInterface
    {
        return $this->getServiceManager()->getContainer()->get(AdvancedJsonResourceMetadataCompiler::class);
    }

    private function getResourceJsonMetadataCompiler(): ResourceMetadataCompilerInterface
    {
        return $this->getServiceManager()->getContainer()->get(ResourceJsonMetadataCompiler::SERVICE_ID);
    }

    private function getFeatureFlagChecker(): FeatureFlagCheckerInterface
    {
        return $this->getServiceManager()->getContainer()->get(FeatureFlagChecker::class);
    }
}
