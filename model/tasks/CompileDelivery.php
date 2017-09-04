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

use oat\oatbox\extension\AbstractAction;
use oat\oatbox\service\ServiceManager;
use oat\oatbox\task\Queue;
use oat\oatbox\task\Task;
use oat\taoDeliveryRdf\model\DeliveryFactory;

/**
 * Class CompileDelivery
 *
 * Action to compile delivery by test uri
 *
 * @package oat\taoQtiTest\models\tasks
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class CompileDelivery extends AbstractAction implements \JsonSerializable
{
    /**
     * @param $params
     * @throws \common_exception_MissingParameter
     * @return \common_report_Report
     */
    public function __invoke($params)
    {
        if (! isset($params['test'])) {
            throw new \common_exception_MissingParameter('Missing parameter `test` in ' . self::class);
        }

        \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDeliveryRdf');

        if (isset($params['delivery'])) {
            $deliveryClass = new \core_kernel_classes_Class($params['delivery']);
            if (! $deliveryClass->exists()) {
                $deliveryClass = new \core_kernel_classes_Class(CLASS_COMPILEDDELIVERY);
            }
        } else {
            $deliveryClass = new \core_kernel_classes_Class(CLASS_COMPILEDDELIVERY);
        }

        $test = new \core_kernel_classes_Resource($params['test']);
        $label = 'Delivery of ' . $test->getLabel();

        $deliveryFactory = $this->getServiceManager()->get(DeliveryFactory::SERVICE_ID);
        /** @var \common_report_Report $report */
        $report = $deliveryFactory->create($deliveryClass, $test, $label);

        if ($report->getType() == \common_report_Report::TYPE_ERROR) {
            \common_Logger::i('Unable to generate delivery execution ' .
                'into taoDeliveryRdf::RestDelivery for test uri ' . $test->getUri());
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
     * Create a task to compile a delivery into a delviery class
     *
     * @param \core_kernel_classes_Resource $test test resource to compile
     * @param \core_kernel_classes_Class $delivery Optional delivery where to compile the test
     * @return Task created task id
     */
    public static function createTask(\core_kernel_classes_Resource $test, \core_kernel_classes_Class $delivery = null)
    {
        $action = new self();
        $queue = ServiceManager::getServiceManager()->get(Queue::SERVICE_ID);

        $parameters = ['test'=> $test->getUri()];
        if (! is_null($delivery)) {
            $parameters['delivery'] = $delivery->getUri();
        }
        //put task in queue with reference to the test resource and delivery
        $task = $queue->createTask($action, $parameters);

        return $task;
    }
}