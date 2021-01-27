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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\model\DataStore;

use JsonSerializable;
use oat\oatbox\extension\AbstractAction;
use oat\oatbox\reporting\Report;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\exception\InvalidServiceManagerException;
use oat\tao\model\taskQueue\QueueDispatcher;

class GCPDeliverySyncTask extends AbstractAction implements JsonSerializable
{
    private const MAX_TRIES = 10;

    /**
     * @throws InvalidServiceManagerException
     */
    public function __invoke($params)
    {
        $report = new Report(Report::TYPE_SUCCESS);
        $error = true;

        if (!$error) {
            $report->setMessage('Success syncing GCP for delivery: ' . $params['deliveryId']);
        }
        if ($error && $params['count'] < self::MAX_TRIES) {
            $params['count']++;
            $this->requeueTask($params);

            $report->setType(Report::TYPE_ERROR);
            $report->setMessage('Failing syncing GCP for delivery: ' . $params['deliveryId']);
        }

        return $report;
    }

    public function jsonSerialize()
    {
        return __CLASS__;
    }

    /**
     * @throws InvalidServiceManagerException
     */
    private function getQueueDispatcher(): ConfigurableService
    {
        return $this->getServiceManager()->get(QueueDispatcher::SERVICE_ID);
    }

    /**
     * @param $params
     * @throws InvalidServiceManagerException
     */
    private function requeueTask($params): void
    {
        /** @var QueueDispatcher $queueDispatcher */
        $queueDispatcher = $this->getQueueDispatcher();
        $queueDispatcher->createTask(
            $this,
            $params, __('Continue try to sync GCP of delivery "%s".', $params['deliveryId'])
        );
    }
}