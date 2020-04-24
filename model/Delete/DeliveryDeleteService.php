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

namespace oat\taoDeliveryRdf\model\Delete;

use common_report_Report;
use common_ext_ExtensionsManager as ExtensionsManager;
use core_kernel_classes_Resource;
use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\Monitoring;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoDelivery\model\execution\Delete\DeliveryExecutionDeleteRequest;
use oat\taoDelivery\model\execution\Delete\DeliveryExecutionDeleteService;
use oat\taoQtiTest\models\TestSessionService;
use oat\taoResultServer\models\classes\ResultManagement;
use oat\taoResultServer\models\classes\ResultServerService;

class DeliveryDeleteService extends ConfigurableService
{
    const SERVICE_ID = 'taoDeliveryRdf/DeliveryDelete';

    const OPTION_DELETE_DELIVERY_DATA_SERVICES = 'deleteDeliveryDataServices';

    const OPTION_LIMIT_DELIVERY_EXECUTIONS = 'executionsLimit';

    /** @var common_report_Report  */
    protected $report;

    /**
     * DeliveryDeleteService constructor.
     * @param array $options
     * @throws \Exception
     */
    public function __construct(array $options)
    {
        parent::__construct($options);

        if (!$this->hasOption(static::OPTION_DELETE_DELIVERY_DATA_SERVICES)) {
            throw new \common_exception_Error('Invalid Option provided: ' . static::OPTION_DELETE_DELIVERY_DATA_SERVICES);
        }
    }

    /**
     * @param DeliveryDeleteRequest $request
     * @param boolean $byChunk
     * @return bool
     * @throws \Exception
     */
    public function execute(DeliveryDeleteRequest $request, $byChunk = false)
    {
        $delivery = $request->getDeliveryResource();

        $this->report = common_report_Report::createInfo('Deleting Delivery: ' . $delivery->getUri());
        $executions   = $this->getDeliveryExecutions($delivery);

        $executionsLimit = $this->hasOption(self::OPTION_LIMIT_DELIVERY_EXECUTIONS)
            ? $this->getOption(self::OPTION_LIMIT_DELIVERY_EXECUTIONS)
            : count($executions);
        if ($byChunk && $executions && count($executions) > $executionsLimit) {
            $executionsForDeleting = array_slice($executions, 0, $executionsLimit);
            $this->deleteDeliveryExecutions($delivery, $executionsForDeleting);
        } else {
            $this->deleteDeliveryExecutions($delivery, $executions);
            return $this->deleteDelivery($request);
        }
        return true;
    }

    /**
     * @param core_kernel_classes_Resource $deliveryResource
     * @return array|\oat\taoDelivery\model\execution\DeliveryExecution[]
     * @throws \common_exception_Error
     * @throws \common_exception_NoImplementation
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    protected function getDeliveryExecutions(core_kernel_classes_Resource $deliveryResource)
    {
        $serviceProxy = $this->getServiceProxy();
        /** @var ExtensionsManager $extensionsManager */
        $extensionsManager = $this->getServiceManager()->get(ExtensionsManager::SERVICE_ID);
        if ($serviceProxy instanceof Monitoring) {
            $executions = $serviceProxy->getExecutionsByDelivery($deliveryResource);
        } else if ($extensionsManager->isEnabled('taoResultServer')) {
            $resultStorage = $this->getResultStorage($deliveryResource->getUri());
            $results       = $resultStorage->getResultByDelivery([$deliveryResource->getUri()]);

            $executions = [];
            foreach ($results as $result) {
                $executions[] = $serviceProxy->getDeliveryExecution($result['deliveryResultIdentifier']);
            }
        } else {
            throw new \common_exception_Error('Cannot retrieve delivery executions by delivery');
        }

        return $executions;
    }

    /**
     * @return common_report_Report
     */
    public function getReport()
    {
        return $this->report;
    }

    /**
     * @param DeliveryDeleteRequest $request
     * @return bool
     * @throws \Exception
     */
    protected function deleteDelivery(DeliveryDeleteRequest $request)
    {
        $services = $this->getDeliveryDeleteService();

        foreach ($services as $service) {
            try {
                $deleted = $service->deleteDeliveryData($request);
                if ($deleted) {
                    $this->report->add(common_report_Report::createSuccess(
                        'Delete delivery Service: ' . get_class($service) . ' data has been deleted.'
                    ));
                } else {
                    $this->report->add(common_report_Report::createInfo(
                        'Delete delivery Service: ' . get_class($service) . ' data has nothing to delete.'
                    ));
                }
            } catch (\Exception $exception) {
                $this->report->add(common_report_Report::createInfo(
                    $exception->getMessage()
                ));
            }
        }

        return true;
    }

    /**
     * @param $deliveryId
     * @return ResultManagement
     * @throws \common_exception_Error
     */
    protected function getResultStorage()
    {
        /** @var ResultServerService $resultService */
        $resultService = $this->getServiceLocator()->get(ResultServerService::SERVICE_ID);
        return $resultService->getResultStorage();
    }

    /**
     * @return array|ServiceProxy|object
     */
    protected function getServiceProxy()
    {
        return $this->getServiceLocator()->get(ServiceProxy::SERVICE_ID);
    }

    /**
     * @return DeliveryDelete[]
     * @throws \common_exception_Error
     */
    private function getDeliveryDeleteService()
    {
        $services = [];
        $servicesStrings = $this->getOption(static::OPTION_DELETE_DELIVERY_DATA_SERVICES);

        foreach ($servicesStrings as $serviceString) {
            $deleteService = $this->getServiceLocator()->get($serviceString);
            if (!$deleteService instanceof DeliveryDelete) {
                throw new \common_exception_Error('Invalid Delete Service provided: ' . $serviceString);
            }

            $services[] = $deleteService;
        }

        return $services;
    }

    /**
     * @param $deliveryResource
     * @param $execution
     * @return DeliveryExecutionDeleteRequest
     * @throws \Exception
     */
    private function buildDeliveryExecutionDeleteRequest($deliveryResource, $execution)
    {
        /** @var TestSessionService $testSessionService */
        $testSessionService = $this->getServiceLocator()->get(TestSessionService::SERVICE_ID);
        try {
            $session = $testSessionService->getTestSession($execution, false);
        } catch (\Exception $exception) {
            $session = null;
        }

        return new DeliveryExecutionDeleteRequest(
            $deliveryResource,
            $execution,
            $session
        );
    }

    /**
     * @param core_kernel_classes_Resource $delivery
     * @param $executions
     * @throws \common_exception_Error
     */
    private function deleteDeliveryExecutions(\core_kernel_classes_Resource $delivery, $executions)
    {
        /** @var DeliveryExecutionDeleteService $deliveryExecutionDeleteService */
        $deliveryExecutionDeleteService = $this->getServiceLocator()->get(DeliveryExecutionDeleteService::SERVICE_ID);

        foreach ($executions as $execution) {
            try {
                $requestDeleteExecution = $this->buildDeliveryExecutionDeleteRequest(
                    $delivery,
                    $execution
                );

                $deliveryExecutionDeleteService->execute($requestDeleteExecution);
                $this->report->add($deliveryExecutionDeleteService->getReport());
            } catch (\Exception $exception) {
                $this->report->add(common_report_Report::createFailure('Failing deleting execution: ' . $execution->getIdentifier()));
                $this->report->add(common_report_Report::createFailure($exception->getMessage()));
            }
        }
    }

    /**
     * @param core_kernel_classes_Resource $delivery
     * @return bool
     * @throws \common_exception_Error
     */
    public function hasDeliveryExecutions(\core_kernel_classes_Resource $delivery)
    {
        $executions = $this->getDeliveryExecutions($delivery);
        return !empty($executions);
    }
}
