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

use oat\taoDeliveryRdf\model\DeliveryFactory;
use oat\taoDeliveryRdf\model\tasks\CompileDelivery;
use oat\tao\model\TaskQueueActionTrait;
use oat\oatbox\task\Task;
use oat\taoDeliveryRdf\model\SimpleDeliveryFactory;

class RestDelivery extends \tao_actions_RestController
{
    use TaskQueueActionTrait {
        getTask as traitGetTask;
        getTaskData as traitGetTaskData;
    }

    const REST_DELIVERY_TEST_ID = 'test';
    const TASK_ID_PARAM = 'id';

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
            $deliveryClass = new \core_kernel_classes_Class(CLASS_COMPILEDDELIVERY);


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
            if (!$this->hasRequestParameter(self::REST_DELIVERY_TEST_ID)) {
                throw new \common_exception_MissingParameter(self::REST_DELIVERY_TEST_ID, $this->getRequestURI());
            }

            $test = new \core_kernel_classes_Resource($this->getRequestParameter(self::REST_DELIVERY_TEST_ID));
            if (!$test->exists()) {
                throw new \common_exception_NotFound('Unable to find a test associated to the given uri.');
            }
            $task = CompileDelivery::createTask($test);

            $result = [
                'reference_id' => $task->getId()
            ];
            $report = $task->getReport();
            if (!empty($report)) {
                if ($report instanceof \common_report_Report) {
                    //serialize report to array
                    $report = json_encode($report);
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
}
