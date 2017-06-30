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
use oat\taoDeliveryRdf\model\DeliveryFactory;
use oat\taoDeliveryRdf\model\tasks\CompileDelivery;
use oat\tao\model\TaskQueueActionTrait;
use oat\oatbox\task\Task;

class RestDelivery extends \tao_actions_RestController
{
    use TaskQueueActionTrait {
        getTask as traitGetTask;
        getTaskData as traitGetTaskData;
    }

    const REST_DELIVERY_TEST_ID        = 'test';
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

            $test = new \core_kernel_classes_Resource($this->getRequestParameter(self::REST_DELIVERY_TEST_ID));
            if (!$test->exists()) {
                throw new \common_exception_NotFound('Unable to find a test associated to the given uri.');
            }

            $label = 'Delivery of ' . $test->getLabel();
            $deliveryClass = $this->getDeliveryClassByParameters();

            $deliveryFactory = $this->getServiceManager()->get(DeliveryFactory::SERVICE_ID);
            /** @var \common_report_Report $report */
            $report = $deliveryFactory->create($deliveryClass, $test, $label);

            if ($report->getType() == \common_report_Report::TYPE_ERROR) {
                \common_Logger::i('Unable to generate delivery execution ' .
                    'into taoDeliveryRdf::RestDelivery for test uri ' . $test->getUri());
                throw new \common_Exception('Unable to generate delivery execution.');
            }
            $delivery = $report->getData();
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

            $test = new \core_kernel_classes_Resource($this->getRequestParameter(self::REST_DELIVERY_TEST_ID));
            if (! $test->exists()) {
                throw new \common_exception_NotFound('Unable to find a test associated to the given uri.');
            }

            $deliveryClass = $this->getDeliveryClassByParameters();
            $task = CompileDelivery::createTask($test, $deliveryClass);

            $result = [
                'reference_id' => $task->getId()
            ];
            $report = $task->getReport();
            if (!empty($report)) {
                if ($report instanceof \common_report_Report) {
                    //serialize report to array
                    $report = json_decode($report);
                }
                $result['report'] = $report;
            }
            return $this->returnSuccess($result);

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
            $data = $this->getTaskData($this->getRequestParameter(self::TASK_ID_PARAM));
            $this->returnSuccess($data);
        } catch (\Exception $e) {
            $this->returnFailure($e);
        }
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
     * @param $taskId
     * @return array
     */
    protected function getTaskData($taskId)
    {
        $data = $this->traitGetTaskData($taskId);
        $task = $this->getTask($taskId);
        $report = \common_report_Report::jsonUnserialize($task->getReport());
        if ($report) {
            $plainReport = $this->getPlainReport($report);
            //the second report is report of compilation test
            if (isset($plainReport[1]) && isset($plainReport[1]->getData()['uriResource'])) {
                $data['delivery'] = $plainReport[1]->getData()['uriResource'];
            }
        }
        return $data;
    }

    /**
     * @param Task $taskId
     * @return Task
     * @throws \common_exception_BadRequest
     */
    protected function getTask($taskId)
    {
        $task = $this->traitGetTask($taskId);
        if ($task->getInvocable() !== 'oat\taoDeliveryRdf\model\tasks\CompileDelivery') {
            throw new \common_exception_BadRequest("Wrong task type");
        }
        return $task;
    }

    /**
     * @param Task $task
     * @return string
     */
    protected function getTaskStatus(Task $task)
    {
        $report = $task->getReport();
        if (in_array(
            $task->getStatus(),
            [Task::STATUS_CREATED, Task::STATUS_RUNNING, Task::STATUS_STARTED])
        ) {
            $result = 'In Progress';
        } else if ($report) {
            $report = \common_report_Report::jsonUnserialize($report);
            $plainReport = $this->getPlainReport($report);
            $success = true;
            foreach ($plainReport as $r) {
                $success = $success && $r->getType() != \common_report_Report::TYPE_ERROR;
            }
            $result = $success ? 'Success' : 'Failed';
        }
        return $result;
    }

    /**
     * @param Task $task
     * @return array
     */
    protected function getTaskReport(Task $task)
    {
        $report = \common_report_Report::jsonUnserialize($task->getReport());
        $result = [];
        if ($report) {
            $plainReport = $this->getPlainReport($report);
            foreach ($plainReport as $r) {
                $result[] = [
                    'type' => $r->getType(),
                    'message' => $r->getMessage(),
                ];
            }
        }
        return $result;
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
            $deliveryClass = new \core_kernel_classes_Class($this->getRequestParameter(self::REST_DELIVERY_CLASS_URI));
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
                ->add(RDFS_LABEL)->equals($label)
                ->add(RDFS_SUBCLASSOF)->in($classes)
            ;
            $queryBuilder->setCriteria($criteria);
            $result = $search->getGateway()->search($queryBuilder);

            switch ($result->count()) {
                case 0:
                    throw new \common_exception_NotFound(__('Delivery with label "%s" not found', $label));
                case 1:
                    return new \core_kernel_classes_Class($result->current()->getUri());
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
        return new \core_kernel_classes_Class(CLASS_COMPILEDDELIVERY);
    }

}
