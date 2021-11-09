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
use core_kernel_classes_Resource;
use core_kernel_persistence_Exception;
use JsonSerializable;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\extension\AbstractAction;
use oat\oatbox\reporting\Report;
use oat\oatbox\service\exception\InvalidServiceManagerException;
use oat\tao\model\metadata\compiler\ResourceMetadataCompilerInterface;
use oat\tao\model\taskQueue\QueueDispatcher;
use oat\taoDeliveryRdf\model\DataStore\Metadata\JsonMetaDataCompiler;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use taoQtiTest_models_classes_QtiTestService;
use Throwable;

class MetaDataDeliverySyncTask extends AbstractAction implements JsonSerializable
{
    use OntologyAwareTrait;

    /**
     * @throws InvalidServiceManagerException
     * @throws common_exception_Error
     * @throws core_kernel_persistence_Exception
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
            __('DataStore sync retry for delivery "%s".', $params['deliveryId'])
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
            $deliveryResource = $this->getResource($params['deliveryId']);
            $params['deliveryMetaData'] = $compiler->compile($deliveryResource);
            //test MetaData
            $test = $this->getTest($deliveryResource);
            $params['testUri'] = $this->getTestUri($deliveryResource);
            $params['testMetaData'] = $compiler->compile($test); //@TODO @FIXME Get additional lists metadata...
            //Item MetaData
            $params['itemMetaData'] = $this->getItemMetaData($test, $compiler);  //@TODO @FIXME Get additional lists metadata...
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
        return $this->getServiceManager()->getContainer()->get(JsonMetaDataCompiler::class);
        //FIXME @TODO remove after test
        //return $this->getServiceLocator()->get(\oat\tao\model\metadata\compiler\ResourceJsonMetadataCompiler::SERVICE_ID);
    }
}
