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

    const OPTION_WHITE_LIST_EXCEPTIONS = 'whiteListExceptions';

    /** @var common_report_Report  */
    private $report;

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
     * @return bool
     * @throws \Exception
     */
    public function execute(DeliveryDeleteRequest $request)
    {
        $this->report = common_report_Report::createInfo('Deleting Delivery: '. $request->getDeliveryResource()->getUri());
        $serviceProxy = $this->getServiceProxy();

        if (!$serviceProxy instanceof Monitoring) {
            $resultStorage = $this->getResultStorage($request->getDeliveryResource()->getUri());

            $results = $resultStorage->getResultByDelivery([$request->getDeliveryResource()->getUri()]);

            $executions = [];
            foreach ($results as $result) {
                $executions[] = $serviceProxy->getDeliveryExecution($result['deliveryResultIdentifier']);
            }
        } else{
            $executions = $serviceProxy->getExecutionsByDelivery($request->getDeliveryResource());
        }

        $canDeleteDelivery = true;
        foreach ($executions as $execution) {
            /** @var DeliveryExecutionDeleteService $deliveryExecutionDeleteService */
            $deliveryExecutionDeleteService = $this->getServiceLocator()->get(DeliveryExecutionDeleteService::SERVICE_ID);
            try{
                $requestDeleteExecution = $this->buildDeliveryExecutionDeleteRequest(
                    $request->getDeliveryResource(),
                    $execution
                );

                $deliveryExecutionDeleteService->execute($requestDeleteExecution);
                $this->report->add($deliveryExecutionDeleteService->getReport());
            } catch (\Exception $exception) {
                //check if exception it's acceptable.
                $isWhiteException = false;
                $whiteListExceptions = $this->getWhiteListExceptions();
                foreach ($whiteListExceptions as $whiteException){
                    if ($exception instanceof $whiteException){
                        $isWhiteException = true;
                        break;
                    }
                }
                if($isWhiteException === false){
                    $canDeleteDelivery = false;
                    $this->report->add(common_report_Report::createFailure($exception->getMessage()));
                } else {
                    $this->report->add(common_report_Report::createInfo($exception->getMessage()));
                }
            }
        }

        if ($canDeleteDelivery === false){
            throw new DeleteDeliveryException('Cannot delete delivery not all his executions has been deleted.');
        }

        return $this->deleteDelivery($request);
    }


    /**
     * @return common_report_Report
     */
    public function getReport()
    {
        return $this->report;
    }

    /**
     * @return array
     */
    protected function getWhiteListExceptions()
    {
        $exceptions = $this->getOption(static::OPTION_WHITE_LIST_EXCEPTIONS);
        return $exceptions === null ? [] : $exceptions;
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
            $deleted = $service->deleteDeliveryData($request);
            if ($deleted) {
                $this->report->add(common_report_Report::createSuccess(
                    'Delete delivery Service: '. get_class($service) . ' data has been deleted.')
                );
            } else {
                $this->report->add(common_report_Report::createInfo(
                    'Delete delivery Service: '. get_class($service) . ' data has nothing to delete.')
                );
            }
        }

        return true;
    }

    /**
     * @param $deliveryId
     * @return ResultManagement
     * @throws \common_exception_Error
     */
    protected function getResultStorage($deliveryId)
    {
        /** @var ResultServerService $resultService */
        $resultService = $this->getServiceLocator()->get(ResultServerService::SERVICE_ID);
        return $resultService->getResultStorage($deliveryId);
    }

    /**
     * @return array|ServiceProxy|object
     */
    private function getServiceProxy()
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
        $session = $testSessionService->getTestSession($execution);

        return new DeliveryExecutionDeleteRequest(
            $deliveryResource,
            $execution,
            $session
        );
    }
}
