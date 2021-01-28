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
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\model\DataStore;

use common_exception_Error;
use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use core_kernel_persistence_Exception;
use JsonSerializable;
use oat\oatbox\extension\AbstractAction;
use oat\oatbox\reporting\Report;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\exception\InvalidServiceManagerException;
use oat\tao\model\metadata\compiler\ResourceJsonMetadataCompiler;
use oat\tao\model\taskQueue\QueueDispatcher;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use taoQtiTest_models_classes_QtiTestService;
use Throwable;

class MetaDataDeliverySyncTask extends AbstractAction implements JsonSerializable
{


    /** @var bool */
    private $error;

    /**
     * @throws InvalidServiceManagerException
     */
    public function __invoke($params)
    {
        $params = $this->prepareData($params);
        $report = new Report(Report::TYPE_SUCCESS);

        if ($params['count'] < $params[DeliveryMetadataListener::OPTION_MAX_TRIES]) {
            $params['count']++;
            try {
                $this->getPersistDataService()->persist($params);
                $report->setMessage('Success MetaData syncing for delivery: ' . $params['deliveryId']);
            } catch (Throwable $exception) {
                $this->logError(sprintf(
                    'Failing MetaData syncing for delivery: %s with message: %s',
                    $params['deliveryId'],
                    $exception->getMessage()
                ));

                $report->setType(Report::TYPE_ERROR);
                $report->setMessage('Failing MetaData syncing for delivery: ' . $params['deliveryId']);
                $this->requeueTask($params);
            }
        }

        return $report;
    }

    public function jsonSerialize()
    {
        return __CLASS__;
    }

    /**
     * @throws InvalidServiceManagerException
     */
    private function getQueueDispatcher(): ConfigurableService
    {
        return $this->getServiceManager()->get(QueueDispatcher::SERVICE_ID);
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
            __('Continue try to sync GCP of delivery "%s".', $params['deliveryId'])
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
        $params['count'] = $params['count'] ?? 0;
        if (!isset($params['deliveryMetaData'], $params['testMetaData'], $params['testUri'], $params['itemMetaData'])) {
            $compiler = $this->getMetaDataCompiler();
            //DeliveryMetaData
            $deliveryResource = new core_kernel_classes_Resource($params['deliveryId']);
            $params['deliveryMetaData'] = $compiler->compile($deliveryResource);
            //test MetaData
            $test = $this->getTest($deliveryResource);
            $params['testUri'] = $this->getTestUri($deliveryResource);
            $params['testMetaData'] = $compiler->compile($test);
            //Item MetaData
            $params['itemMetaData'] = $this->getItemMetaData($test, $compiler);
        }

        return $params;
    }
    private function getItemMetaData(core_kernel_classes_Resource $test, ResourceJsonMetadataCompiler $compiler): array
    {
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

        return new core_kernel_classes_Resource($testUri);
    }

    /**
     * @throws core_kernel_persistence_Exception
     */
    private function getTestUri(core_kernel_classes_Resource $deliveryResource): ?string
    {
        $testProperty = new core_kernel_classes_Property(DeliveryAssemblyService::PROPERTY_ORIGIN);

        return ($deliveryResource->getOnePropertyValue($testProperty)) ?
            $deliveryResource->getOnePropertyValue($testProperty)->getUri() :
            null;
    }

    private function getMetaDataCompiler(): ResourceJsonMetadataCompiler
    {
        return $this->getServiceLocator()->get(ResourceJsonMetadataCompiler::SERVICE_ID);
    }
}
