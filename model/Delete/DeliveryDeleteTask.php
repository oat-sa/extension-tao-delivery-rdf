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

use oat\oatbox\extension\AbstractAction;
use oat\tao\model\taskQueue\QueueDispatcher;

class DeliveryDeleteTask extends AbstractAction implements \JsonSerializable
{
    /**
     * @param $params
     * @return \common_report_Report
     * @throws \Exception
     */
    public function __invoke($params)
    {
        if (!isset($params['deliveryId'])) {
            throw new \common_exception_MissingParameter('Missing parameter `deliveryId` in ' . static::class);
        }
        /** @var QueueDispatcher $queueDispatcher */
        $queueDispatcher = $this->getServiceManager()->get(QueueDispatcher::SERVICE_ID);

        $report = \common_report_Report::createInfo('Deleting delivery: ' . $params['deliveryId']);
        try {
            /** @var DeliveryDeleteService $deleteDeliveryService */
            $deleteDeliveryService = $this->getServiceLocator()->get(DeliveryDeleteService::SERVICE_ID);
            $request = new DeliveryDeleteRequest($params['deliveryId']);
            $deleteDeliveryService->execute($request, true);
            $delivery = $request->getDeliveryResource();
            if ($deleteDeliveryService->hasDeliveryExecutions($delivery)) {
                $queueDispatcher->createTask($this, $params, __('Continue deleting of delivery "%s".', $delivery->getLabel()));
                $this->logInfo('Queued task to continue delete delivery ' . $delivery->getLabel());
            }

            $report->add($deleteDeliveryService->getReport());
        } catch (\Exception $exception) {
            $report->add(\common_report_Report::createFailure($exception->getMessage()));
        }

        return $report;
    }

    /**
     * @return mixed|string
     */
    public function jsonSerialize()
    {
        return __CLASS__;
    }
}
