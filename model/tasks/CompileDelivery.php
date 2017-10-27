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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoDeliveryRdf\model\tasks;

use common_report_Report as Report;
use oat\oatbox\service\ServiceManager;
use oat\oatbox\task\AbstractTaskAction;
use oat\oatbox\task\Task;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoDeliveryRdf\model\DeliveryFactory;
use oat\taoTaskQueue\model\QueueDispatcher;
use oat\taoTaskQueue\model\Task\TaskInterface;

/**
 * Class CompileDelivery
 *
 * Action to compile delivery by test uri
 *
 * @package oat\taoQtiTest\models\tasks
 * @author  Aleh Hutnikau, <hutnikau@1pt.com>
 */
class CompileDelivery extends AbstractTaskAction implements \JsonSerializable
{
    /**
     * @param $params
     * @throws \common_exception_MissingParameter
     * @return Report
     */
    public function __invoke($params)
    {
        if (!isset($params['test'])) {
            throw new \common_exception_MissingParameter('Missing parameter `test` in ' . self::class);
        }

        \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDeliveryRdf');

        if (isset($params['delivery'])) {
            $deliveryClass = new \core_kernel_classes_Class($params['delivery']);
            if (!$deliveryClass->exists()) {
                $deliveryClass = new \core_kernel_classes_Class(DeliveryAssemblyService::CLASS_URI);
            }
        } else {
            $deliveryClass = new \core_kernel_classes_Class(DeliveryAssemblyService::CLASS_URI);
        }

        $test = new \core_kernel_classes_Resource($params['test']);
        $deliveryResource = new \core_kernel_classes_Resource($params['deliveryResourceUri']);

        $label = 'Delivery of ' . $test->getLabel();

        $deliveryFactory = $this->getServiceManager()->get(DeliveryFactory::SERVICE_ID);
        /** @var Report $report */
        $report = $deliveryFactory->create($deliveryClass, $test, $label, $deliveryResource);

        if ($report->getType() == Report::TYPE_ERROR) {
            \common_Logger::i('Unable to generate delivery execution ' .
                'into taoDeliveryRdf::RestDelivery for test uri ' . $test->getUri());
        }

        if (isset($params['delivery'])) {
            /** @var \core_kernel_classes_Resource[] $taskResources */
            $taskResources = self::getTaskClass()->searchInstances([
                Task::PROPERTY_LINKED_RESOURCE => $deliveryClass->getUri()
            ]);
            foreach ($taskResources as $taskResource) {
                $taskResource->setPropertyValue(
                    new \core_kernel_classes_Property(Task::PROPERTY_REPORT),
                    json_encode($report)
                );
            }
        }

        return $report;
    }

    /**
     * @return string
     */
    public function jsonSerialize()
    {
        return __CLASS__;
    }

    /**
     * Create a task to compile a delivery into a delivery class
     *
     * @param \core_kernel_classes_Resource $test     test resource to compile
     * @param \core_kernel_classes_Class    $delivery Optional delivery where to compile the test
     * @param \core_kernel_classes_Resource $deliveryResource
     * @return TaskInterface
     */
    public static function createTask(\core_kernel_classes_Resource $test, \core_kernel_classes_Class $delivery = null, \core_kernel_classes_Resource $deliveryResource)
    {
        $action = new self();
        /** @var QueueDispatcher $queueDispatcher */
        $queueDispatcher = ServiceManager::getServiceManager()->get(QueueDispatcher::SERVICE_ID);

        $parameters = [
            'test' => $test->getUri()
        ];

        if (!is_null($delivery)) {
            $parameters['delivery'] = $delivery->getUri();
        }
        $parameters['deliveryResourceUri'] = $deliveryResource->getUri();

        //put task in queue with reference to the test resource and delivery
        $task = $queueDispatcher->createTask($action, $parameters, 'Compilation of ' . $test->getLabel());

        $queueDispatcher->linkTaskToResource($task, $deliveryResource);

        return $task;
    }
}