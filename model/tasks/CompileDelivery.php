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
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\extension\AbstractAction;
use oat\oatbox\service\ServiceManager;
use oat\tao\model\taskQueue\QueueDispatcher;
use oat\tao\model\taskQueue\Task\TaskAwareInterface;
use oat\tao\model\taskQueue\Task\TaskAwareTrait;
use oat\tao\model\taskQueue\Task\TaskInterface;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoDeliveryRdf\model\DeliveryFactory;

/**
 * Class CompileDelivery
 *
 * Action to compile delivery by test uri
 *
 * @package oat\taoQtiTest\models\tasks
 * @author  Aleh Hutnikau, <hutnikau@1pt.com>
 */
class CompileDelivery extends AbstractAction implements \JsonSerializable, TaskAwareInterface
{

    use TaskAwareTrait;
    use OntologyAwareTrait;

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

        if (isset($params['deliveryClass'])) {
            $deliveryClass = new \core_kernel_classes_Class($params['deliveryClass']);
            if (!$deliveryClass->exists()) {
                $deliveryClass = new \core_kernel_classes_Class(DeliveryAssemblyService::CLASS_URI);
            }
        } else {
            $deliveryClass = new \core_kernel_classes_Class(DeliveryAssemblyService::CLASS_URI);
        }

        $test = new \core_kernel_classes_Resource($params['test']);
        $label = 'Delivery of ' . $test->getLabel();

        $deliveryResource =  \core_kernel_classes_ResourceFactory::create($deliveryClass);
        if ($params['initialProperties']) {
            // Setting "Sync to remote..." if enabled
            /** @var DeliveryFactory $deliveryFactoryResources */
            $deliveryFactoryResources = $this->getServiceManager()->get(DeliveryFactory::SERVICE_ID);

            $deliveryResource = $deliveryFactoryResources->setInitialProperties(
                $params['initialProperties'],
                $deliveryResource
            );
        }

        /** @var TaskInterface $task */
        $task = $this->getTask();
        if (!is_null($task)) {
            $deliveryCompileTaskProperty = $this->getProperty(DeliveryFactory::PROPERTY_DELIVERY_COMPILE_TASK);
            $deliveryResource->setPropertyValue($deliveryCompileTaskProperty, $task->getId());
        }

        /** @var DeliveryFactory $deliveryFactory */
        $deliveryFactory = $this->getServiceManager()->get(DeliveryFactory::SERVICE_ID);

        return $deliveryFactory->create($deliveryClass, $test, $label, $deliveryResource);
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
     * @param \core_kernel_classes_Resource $test          Test resource to be compiled
     * @param \core_kernel_classes_Class    $deliveryClass Delivery class where the test is compiled to
     * @param array                         $initialProperties
     * @return TaskInterface
     */
    public static function createTask(\core_kernel_classes_Resource $test, \core_kernel_classes_Class $deliveryClass, array $initialProperties = [])
    {
        $action = new self();
        /** @var QueueDispatcher $queueDispatcher */
        $queueDispatcher = ServiceManager::getServiceManager()->get(QueueDispatcher::SERVICE_ID);

        $parameters = [
            'test' => $test->getUri(),
            'initialProperties' => $initialProperties
        ];

        if (!is_null($deliveryClass)) {
            $parameters['deliveryClass'] = $deliveryClass->getUri();
        }

        return $queueDispatcher->createTask($action, $parameters, __('Publishing of "%s"', $test->getLabel()), null, true);
    }
}