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
 * Copyright (c) 2021-2022 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\model\DataStore;

use common_exception_Error;
use core_kernel_classes_Resource;
use core_kernel_persistence_Exception;
use JsonSerializable;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\extension\AbstractAction;
use oat\oatbox\reporting\Report;
use oat\oatbox\service\exception\InvalidServiceManagerException;
use oat\tao\model\featureFlag\FeatureFlagChecker;
use oat\tao\model\featureFlag\FeatureFlagCheckerInterface;
use oat\tao\model\metadata\compiler\AdvancedJsonResourceMetadataCompiler;
use oat\tao\model\metadata\compiler\ResourceJsonMetadataCompiler;
use oat\tao\model\metadata\compiler\ResourceMetadataCompilerInterface;
use oat\tao\model\taskQueue\QueueDispatcher;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use taoQtiTest_models_classes_QtiTestService;
use Throwable;

class MetaDataDeliverySyncTask extends AbstractAction implements JsonSerializable
{
    use OntologyAwareTrait;

    public const INCLUDE_METADATA_PARAM_NAME = 'includeMetadata';
    public const DELIVERY_OR_TEST_ID_PARAM_NAME = 'deliveryOrTestId';
    public const FILE_SYSTEM_ID_PARAM_NAME = 'fileSystemId';
    public const TEST_URI_PARAM_NAME = 'testUri';
    public const MAX_TRIES_PARAM_NAME = 'maxTries';
    public const IS_REMOVE_PARAM_NAME = 'isRemove';

    /**
     * @throws InvalidServiceManagerException
     * @throws common_exception_Error
     * @throws core_kernel_persistence_Exception
     */
    public function __invoke($params)
    {
        if ($params[self::INCLUDE_METADATA_PARAM_NAME]) {
            $params = $this->prepareData($params);
        }

        $report = new Report(Report::TYPE_SUCCESS);

        $params['count'] = $params['count'] ?? 0;
        if ($params['count'] < $params[self::MAX_TRIES_PARAM_NAME]) {
            $params['count']++;
            try {
                if ($params[self::IS_REMOVE_PARAM_NAME]) {
                    $this->getPersistDataService()->remove($params);
                } else {
                    $this->getPersistDataService()->persist($params);
                }
                $report->setMessage(sprintf(
                    'Success MetaData syncing for delivery: %s',
                    $params[self::DELIVERY_OR_TEST_ID_PARAM_NAME]
                ));
            } catch (Throwable $exception) {
                $this->logError(sprintf(
                    'Failing MetaData syncing for delivery: %s with message: %s',
                    $params[self::DELIVERY_OR_TEST_ID_PARAM_NAME],
                    $exception->getMessage()
                ));

                $report->setType(Report::TYPE_ERROR);
                $report->setMessage($exception->getMessage());
                $this->requeueTask($params);
            }
        }

        return $report;
    }

    public function jsonSerialize(): string
    {
        return self::class;
    }

    /**
     * @throws InvalidServiceManagerException
     */
    private function getQueueDispatcher(): QueueDispatcher
    {
        return $this->getServiceLocator()->get(QueueDispatcher::SERVICE_ID);
    }

    /**
     * @param $params
     * @throws InvalidServiceManagerException
     */
    private function requeueTask($params): void
    {
        /** @var QueueDispatcher $queueDispatcher */
        $queueDispatcher = $this->getQueueDispatcher();
        $queueDispatcher->createTask(
            $this,
            $params,
            __(
                'DataStore sync retry number "%s" for test of delivery with id: "%s".',
                $params['count'],
                $params[self::DELIVERY_OR_TEST_ID_PARAM_NAME]
            )
        );
    }

    private function getPersistDataService(): PersistDataService
    {
        return $this->getServiceLocator()->get(PersistDataService::class);
    }

    /**
     * @throws common_exception_Error
     * @throws core_kernel_persistence_Exception
     */
    private function prepareData($params)
    {
        if (!isset($params['deliveryMetaData'], $params['testMetaData'], $params['testUri'], $params['itemMetaData'])) {
            $compiler = $this->getMetaDataCompiler();
            //DeliveryMetaData
            $deliveryResource = $this->getResource($params[self::DELIVERY_OR_TEST_ID_PARAM_NAME]);
            $params['deliveryMetaData'] = $this->getResourceJsonMetadataCompiler()->compile($deliveryResource);
            //test MetaData
            $test = $this->getTest($deliveryResource);
            $params['testUri'] = $this->getTestUri($deliveryResource);
            $params['testMetaData'] = $compiler->compile($test);
            //Item MetaData
            $params['itemMetaData'] = $this->getItemMetaData($test, $compiler);
        }

        return $params;
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
     * @throws common_exception_Error
     * @throws core_kernel_persistence_Exception
     */
    private function getTest(core_kernel_classes_Resource $deliveryResource): core_kernel_classes_Resource
    {
        $testUri = $this->getTestUri($deliveryResource);

        return $this->getResource($testUri);
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
