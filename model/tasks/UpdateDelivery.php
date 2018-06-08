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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoDeliveryRdf\model\tasks;

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ServiceManager;
use oat\oatbox\task\AbstractTaskAction;
use oat\oatbox\task\Queue;
use oat\oatbox\task\Task;
use oat\tao\model\taskQueue\QueueDispatcher;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use \common_report_Report as Report;

/**
 * Class UpdateDelivery
 *
 * Action to update delivery
 *
 * @package oat\taoQtiTest\models\tasks
 * @author Aleksej Tikhanovich, <aleksej@taotesting.com>
 */
class UpdateDelivery extends AbstractTaskAction implements \JsonSerializable
{
    use OntologyAwareTrait;
    const OPTION_WHERE = 'where';
    const OPTION_PARAMETERS = 'parameters';

    /**
     * @param $params
     * @throws \common_exception_MissingParameter
     * @return \common_report_Report
     */
    public function __invoke($params)
    {
        $propertyValues = $params[self::OPTION_PARAMETERS];
        $where = $params[self::OPTION_WHERE];

        $deliveryModelClass = new \core_kernel_classes_Class(DeliveryAssemblyService::CLASS_URI);
        $deliveries = $deliveryModelClass->searchInstances($where, ['like' => false, 'recursive' => true]);
        $report = Report::createSuccess();

        /** @var \core_kernel_classes_Resource $delivery */
        foreach ($deliveries as $key => $delivery) {
            foreach ($propertyValues as $rdfKey => $rdfValue) {
                $rdfKey = \tao_helpers_Uri::decode($rdfKey);
                $property = $this->getProperty($rdfKey);
                $delivery->editPropertyValues($property, $rdfValue);
            }

            $report->add(Report::createSuccess($delivery->getUri()));
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
     * Create a task to update a delivery
     * @param array $where
     * @param array $propertyValues
     * @return Task created task id
     */
    public static function createTask($where = [], $propertyValues = [])
    {
        $serviceManager = ServiceManager::getServiceManager();
        $action = new self();
        $action->setServiceLocator($serviceManager);

        $parameters = [
            self::OPTION_WHERE => $where,
            self::OPTION_PARAMETERS => $propertyValues
        ];
        /** @var QueueDispatcher $queueDispatcher */
        $queueDispatcher = ServiceManager::getServiceManager()->get(QueueDispatcher::SERVICE_ID);
        return $queueDispatcher->createTask($action, $parameters, null, null, true);
    }
}