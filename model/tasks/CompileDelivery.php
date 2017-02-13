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
        if (!isset($params['test'])) {
            throw new \common_exception_MissingParameter('Missing parameter `test` in ' . self::class);
        }
        \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDeliveryRdf');

        $test = new \core_kernel_classes_Resource($params['test']);
        $label = 'Delivery of ' . $test->getLabel();
        $deliveryClass = new \core_kernel_classes_Class(CLASS_COMPILEDDELIVERY);

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
     * @param \core_kernel_classes_Resource $test test resource to compile
     * @return Task created task id
     */
    public static function createTask(\core_kernel_classes_Resource $test)
    {
        $action = new self();
        $queue = ServiceManager::getServiceManager()->get(Queue::CONFIG_ID);
        //put task in queue with reference to the test resource
        $task = $queue->createTask($action, ['test' => $test->getUri()]);

        return $task;
    }
}