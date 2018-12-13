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
 */

namespace oat\taoDeliveryRdf\controller;

use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\oatbox\event\EventManagerAwareTrait;
use oat\tao\model\taskQueue\QueueDispatcher;
use oat\tao\model\taskQueue\TaskLog\Broker\TaskLogBrokerInterface;
use oat\tao\model\taskQueue\TaskLog\Entity\EntityInterface;
use oat\tao\model\taskQueue\TaskLog\TaskLogFilter;
use oat\tao\model\taskQueue\TaskLogActionTrait;
use oat\tao\model\taskQueue\TaskLogInterface;
use oat\taoDeliveryRdf\model\Delete\DeliveryDeleteTask;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\generis\model\OntologyRdfs;
use oat\taoDeliveryRdf\model\DeliveryFactory;
use oat\taoDeliveryRdf\model\tasks\CompileDelivery;
use oat\taoDeliveryRdf\model\tasks\UpdateDelivery;

class RestDelivery extends \tao_actions_RestController
{
    use EventManagerAwareTrait;
    use TaskLogActionTrait;

    const REST_DELIVERY_TEST_ID        = 'test';
    const REST_DELIVERY_SEARCH_PARAMS  = 'searchParams';
    const REST_DELIVERY_ID             = 'delivery';
    const REST_DELIVERY_CLASS_URI      = 'delivery-uri';
    const REST_DELIVERY_CLASS_LABEL    = 'delivery-label';
    const REST_DELIVERY_CLASS_PARENT   = 'delivery-parent';
    const REST_DELIVERY_CLASS_COMMENT  = 'delivery-comment';
    const TASK_ID_PARAM                = 'id';

    const CLASS_LABEL_PARAM            = 'delivery-label';
    const CLASS_COMMENT_PARAM          = 'delivery-comment';
    const PARENT_CLASS_URI_PARAM       = 'delivery-parent';

    /**
     * Generate a delivery from test uri
     * Test uri has to be set and existing
     */
    public function generate()
    {
        try {
            if (!$this->hasRequestParameter(self::REST_DELIVERY_TEST_ID)) {
                throw new \common_exception_MissingParameter(self::REST_DELIVERY_TEST_ID, $this->getRequestURI());
            }

            $test = $this->getResource($this->getRequestParameter(self::REST_DELIVERY_TEST_ID));
            if (!$test->exists()) {
                throw new \common_exception_NotFound('Unable to find a test associated to the given uri.');
            }

            $label = 'Delivery of ' . $test->getLabel();
            $deliveryClass = $this->getDeliveryClassByParameters();

            $deliveryFactory = $this->getServiceManager()->get(DeliveryFactory::SERVICE_ID);
            /** @var \common_report_Report $report */
            $report = $deliveryFactory->create($deliveryClass, $test, $label);

            if ($report->getType() == \common_report_Report::TYPE_ERROR) {
                $this->logInfo('Unable to generate delivery execution ' .
                    'into taoDeliveryRdf::RestDelivery for test uri ' . $test->getUri());
                throw new \common_Exception('Unable to generate delivery execution.');
            }
            $delivery = $report->getData();

            /** @var DeliveryFactory $deliveryFactoryService */
            $deliveryFactoryService = $this->getServiceManager()->get(DeliveryFactory::SERVICE_ID);
            $initialProperties = $deliveryFactoryService->getInitialPropertiesFromRequest($this->getRequest());
            $delivery = $deliveryFactoryService->setInitialProperties($initialProperties, $delivery);
            $this->returnSuccess(array('delivery' => $delivery->getUri()));
        } catch (\Exception $e) {
            $this->returnFailure($e);
        }
    }

    /**
     * Put task to generate a delivery from test uri to the task queue
     * Test uri has to be set and existing
     */
    public function generateDeferred()
    {
        try {
            if (! $this->hasRequestParameter(self::REST_DELIVERY_TEST_ID)) {
                throw new \common_exception_MissingParameter(self::REST_DELIVERY_TEST_ID, $this->getRequestURI());
            }

            $test = $this->getResource($this->getRequestParameter(self::REST_DELIVERY_TEST_ID));
            if (! $test->exists()) {
                throw new \common_exception_NotFound('Unable to find a test associated to the given uri.');
            }

            $deliveryClass = $this->getDeliveryClassByParameters();

            /** @var DeliveryFactory $deliveryFactoryService */
            $deliveryFactoryService = $this->getServiceManager()->get(DeliveryFactory::SERVICE_ID);
            $initialProperties = $deliveryFactoryService->getInitialPropertiesFromRequest($this->getRequest());

            $task = CompileDelivery::createTask($test, $deliveryClass, $initialProperties);

            $result = [
                'reference_id' => $task->getId()
            ];

            /** @var TaskLogInterface $taskLog */
            $taskLog = $this->getServiceManager()->get(TaskLogInterface::SERVICE_ID);

            $report = $taskLog->getReport($task->getId());

            if (!empty($report)) {
                if ($report instanceof \common_report_Report) {
                    //serialize report to array
                    $report = json_decode($report);
                }
                $result['common_report_Report'] = $report;
            }

            return $this->returnSuccess($result);
        } catch (\Exception $e) {
            $this->returnFailure($e);
        }
    }

    /**
     * Update delivery by parameters
     */
    public function update()
    {
        try {
            if ($this->getRequestMethod() !== \Request::HTTP_POST) {
                throw new \common_exception_NotImplemented('Only post method is accepted to updating delivery');
            }

            if (! $this->hasRequestParameter(self::REST_DELIVERY_SEARCH_PARAMS)) {
                throw new \common_exception_MissingParameter(self::REST_DELIVERY_SEARCH_PARAMS, $this->getRequestURI());
            }

            $where = json_decode(html_entity_decode($this->getRequestParameter(self::REST_DELIVERY_SEARCH_PARAMS)), true);
            $propertyValues = $this->getRequestParameters();
            unset($propertyValues[self::REST_DELIVERY_SEARCH_PARAMS]);

            $deliveryModelClass = $this->getDeliveryRootClass();
            $deliveries = $deliveryModelClass->searchInstances($where, ['like' => false, 'recursive' => true]);

            $response = [];

            /** @var \core_kernel_classes_Resource $delivery */
            foreach ($deliveries as $key => $delivery) {
                foreach ($propertyValues as $rdfKey => $rdfValue) {
                    $rdfKey = \tao_helpers_Uri::decode($rdfKey);
                    $property = $this->getProperty($rdfKey);
                    $delivery->editPropertyValues($property, $rdfValue);
                }
                $response[] = ['delivery' => $delivery->getUri()];
            }
            $this->returnSuccess($response);
        }catch (\Exception $e) {
                $this->returnFailure($e);
            }
    }

    /**
     * Update delivery by parameters
     */
    public function updateDeferred()
    {
        try {
            if ($this->getRequestMethod() !== \Request::HTTP_POST) {
                throw new \common_exception_NotImplemented('Only post method is accepted to updating delivery');
            }
            if (! $this->hasRequestParameter(self::REST_DELIVERY_SEARCH_PARAMS)) {
                throw new \common_exception_MissingParameter(self::REST_DELIVERY_SEARCH_PARAMS, $this->getRequestURI());
            }
            $where = json_decode(html_entity_decode($this->getRequestParameter(self::REST_DELIVERY_SEARCH_PARAMS)), true);
            $propertyValues = $this->getRequestParameters();
            unset($propertyValues[self::REST_DELIVERY_SEARCH_PARAMS]);

            $task = UpdateDelivery::createTask($where, $propertyValues);

            $result = [
                'reference_id' => $task->getId()
            ];

            /** @var TaskLogInterface $taskLog */
            $taskLog = $this->getServiceManager()->get(TaskLogInterface::SERVICE_ID);
            $report = $taskLog->getReport($task->getId());
            if (!empty($report)) {
                if ($report instanceof \common_report_Report) {
                    //serialize report to array
                    $report = json_decode($report);
                }
                $result['common_report_Report'] = $report;
            }
            return $this->returnSuccess($result);
        }catch (\Exception $e) {
            $this->returnFailure($e);
        }
    }

    /**
     * Delete delivery by URI
     */
    public function deleteDeferred()
    {
        try {
            if ($this->getRequestMethod() !== \Request::HTTP_DELETE) {
                throw new \common_exception_NotImplemented('Only delete method is accepted to deleting delivery');
            }

            if (!$this->hasRequestParameter('uri')) {
                throw new \common_exception_MissingParameter('uri', $this->getRequestURI());
            }

            $uri = $this->getRequestParameter('uri');
            $delivery  = $this->getResource($uri);

            if (!$delivery->exists()) {
                $this->returnFailure(new \common_exception_NotFound('Delivery has not been found'));
            }

            /** @var QueueDispatcher $queueDispatcher */
            $queueDispatcher = $this->getServiceManager()->get(QueueDispatcher::SERVICE_ID);

            $task = new DeliveryDeleteTask();
            $task->setServiceLocator($this->getServiceLocator());
            $taskParameters = ['deliveryId' => $uri];

            $task = $queueDispatcher->createTask($task, $taskParameters, __('Deleting of "%s"', $delivery->getLabel()), null, true);

            $data = $this->getTaskLogReturnData(
                $task->getId(),
                DeliveryDeleteTask::class
            );
            $this->returnSuccess($data);
        } catch (\Exception $e) {
            $this->returnFailure($e);
        }
    }

    /**
     * List all deliveries or paginated range
     */
    public function get()
    {
        try {
            if ($this->getRequestMethod() !== \Request::HTTP_GET) {
                throw new \common_exception_NotImplemented('Only get method is accepted to getting deliveries');
            }

            $limit = 0;
            if ($this->hasRequestParameter('limit')) {
                $limit = $this->getRequestParameter('limit');
                if (!is_numeric($limit) || (int)$limit != $limit || $limit < 0) {
                    throw new \common_exception_ValidationFailed('limit', '\'Limit\' should be a positive integer');
                }
            }

            $offset = 0;
            if ($this->hasRequestParameter('offset')) {
                $offset = $this->getRequestParameter('offset');
                if (!is_numeric($offset) || (int)$offset != $offset || $offset < 0) {
                    throw new \common_exception_ValidationFailed('offset', '\'Offset\' should be a positive integer');
                }
            }

            $service = DeliveryAssemblyService::singleton();

            /** @var \core_kernel_classes_Resource[] $deliveries */
            $deliveries = $service->getAllAssemblies();
            $overallCount = count($deliveries);
            if ($offset || $limit) {
                if ($overallCount <= $offset) {
                    throw new \common_exception_ValidationFailed('offset', '\'Offset\' is too large');
                }
                $deliveries = array_slice($deliveries, $offset, $limit);
            }

            $mappedDeliveries = [];
            foreach ($deliveries as $delivery) {
                $mappedDeliveries[] = [
                    'uri' => $delivery->getUri(),
                    'label' => $delivery->getLabel(),
                ];
            }

            $response = [
                'items' => $mappedDeliveries,
                'overallCount' => $overallCount,
            ];
            $this->returnSuccess($response);
        } catch (\Exception $e) {
            $this->returnFailure($e);
        }
    }

    /**
     * Action to retrieve test compilation task status from queue
     */
    public function getStatus()
    {
        try {
            if (!$this->hasRequestParameter(self::TASK_ID_PARAM)) {
                throw new \common_exception_MissingParameter(self::TASK_ID_PARAM, $this->getRequestURI());
            }

            $data = $this->getTaskLogReturnData(
                $this->getRequestParameter(self::TASK_ID_PARAM),
                CompileDelivery::class
            );
            $children = $this->getStatusesForChildren($this->getRequestParameter(self::TASK_ID_PARAM));
            $data['children'] = $children;
            $this->returnSuccess($data);
        } catch (\Exception $e) {
            $this->returnFailure($e);
        }
    }

    /**
     * @param $taskId
     * @return array
     */
    protected function getStatusesForChildren($taskId)
    {
        /** @var TaskLogInterface $taskLog */
        $taskLog = $this->getServiceManager()->get(TaskLogInterface::SERVICE_ID);
        $filter = (new TaskLogFilter())
            ->eq(TaskLogBrokerInterface::COLUMN_PARENT_ID, $taskId);
        $collection = $taskLog->search($filter);
        $response = [];
        if ($collection->isEmpty()) {
            return $response;
        }
        /** @var EntityInterface $item */
        foreach ($collection as $item) {
            $response[] = [
                'id' => $this->getTaskId($item),
                'label' => $item->getLabel(),
                'status' => $this->getTaskStatus($item),
                'report' => $item->getReport() ? $this->getTaskReport($item) : []
            ];
        }
        return $response;
    }

    /**
     * Return 'Success' instead of 'Completed', required by the specified API.
     *
     * @param EntityInterface $taskLogEntity
     * @return string
     */
    protected function getTaskStatus(EntityInterface $taskLogEntity)
    {
        if ($taskLogEntity->getStatus()->isCreated()) {
            return 'In Progress';
        } else if ($taskLogEntity->getStatus()->isCompleted()){
            return 'Success';
        }

        return $taskLogEntity->getStatus()->getLabel();
    }

    /**
     * @param EntityInterface $taskLogEntity
     * @return array
     */
    protected function addExtraReturnData(EntityInterface $taskLogEntity)
    {
        $data = [];

        if ($taskLogEntity->getReport()) {
            $plainReport = $this->getPlainReport($taskLogEntity->getReport());

            //the second report is the report of the compilation test
            if (isset($plainReport[1]) && isset($plainReport[1]->getData()['uriResource'])) {
                $data['delivery'] = $plainReport[1]->getData()['uriResource'];
            }
        }

        return $data;
    }


    /**
     * Create a Delivery Class
     *
     * Label parameter is mandatory
     * If parent class parameter is an uri of valid delivery class, new class will be created under it
     * If not parent class parameter is provided, class will be created under root class
     * Comment parameter is not mandatory, used to describe new created class
     *
     * @return \core_kernel_classes_Class
     */
    public function createClass()
    {
        try {
            $class = $this->createSubClass($this->getDeliveryRootClass());

            $result = [
                'message' => __('Class successfully created.'),
                'delivery-uri' => $class->getUri(),
            ];

            $this->returnSuccess($result);
        } catch (\common_exception_ClassAlreadyExists $e) {
            $result = [
                'message' => $e->getMessage(),
                'delivery-uri' => $e->getClass()->getUri(),
            ];
            $this->returnSuccess($result);
        } catch (\Exception $e) {
            $this->returnFailure($e);
        }
    }

    /**
     * Get a delivery class based on parameters
     *
     * If an uri parameter is provided, and it is a delivery class, this delivery class is returned
     * If a label parameter is provided, and only one delivery class has this label, this delivery class is returned
     *
     * @return \core_kernel_classes_Class
     * @throws \common_Exception
     * @throws \common_exception_NotFound
     */
    protected function getDeliveryClassByParameters()
    {
        $rootDeliveryClass = $this->getDeliveryRootClass();

        // If an uri is provided, check if it's an existing delivery class
        if ($this->hasRequestParameter(self::REST_DELIVERY_CLASS_URI)) {
            $deliveryClass = $this->getClass($this->getRequestParameter(self::REST_DELIVERY_CLASS_URI));
            if ($deliveryClass == $rootDeliveryClass
                || ($deliveryClass->exists() && $deliveryClass->isSubClassOf($rootDeliveryClass))) {
                return $deliveryClass;
            }
            throw new \common_Exception(__('Delivery class uri provided is not a valid delivery class.'));
        }

        if ($this->hasRequestParameter(self::REST_DELIVERY_CLASS_LABEL)) {
            $label = $this->getRequestParameter(self::REST_DELIVERY_CLASS_LABEL);

            $deliveryClasses = $rootDeliveryClass->getSubClasses(true);
            $classes = [$rootDeliveryClass->getUri()];
            foreach ($deliveryClasses as $class) {
                $classes[] = $class->getUri();
            }

            /** @var ComplexSearchService $search */
            $search = $this->getServiceManager()->get(ComplexSearchService::SERVICE_ID);
            $queryBuilder = $search->query();
            $criteria = $queryBuilder->newQuery()
                ->add(OntologyRdfs::RDFS_LABEL)->equals($label)
                ->add(OntologyRdfs::RDFS_SUBCLASSOF)->in($classes)
            ;
            $queryBuilder->setCriteria($criteria);
            $result = $search->getGateway()->search($queryBuilder);

            switch ($result->count()) {
                case 0:
                    throw new \common_exception_NotFound(__('Delivery with label "%s" not found', $label));
                case 1:
                    return $this->getClass($result->current()->getUri());
                default:
                    $availableClasses = [];
                    foreach ($result as $raw) {
                        $availableClasses[] = $raw->getUri();
                    }
                    throw new \common_exception_NotFound(__('Multiple delivery class found for label "%s": %s',
                        $label, implode(',',$availableClasses)
                    ));
            }
        }

        return $rootDeliveryClass;
    }

    /**
     * Get the delivery root class
     *
     * @return \core_kernel_classes_Class
     */
    protected function getDeliveryRootClass()
    {
        return $this->getClass(DeliveryAssemblyService::CLASS_URI);
    }
}
