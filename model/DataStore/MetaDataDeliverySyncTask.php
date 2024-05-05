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
 * Copyright (c) 2021-2022 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\model\DataStore;

use common_exception_Error;
use core_kernel_persistence_Exception;
use JsonSerializable;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\extension\AbstractAction;
use oat\oatbox\reporting\Report;
use oat\oatbox\service\exception\InvalidServiceManagerException;
use oat\tao\model\taskQueue\QueueDispatcher;
use Throwable;

class MetaDataDeliverySyncTask extends AbstractAction implements JsonSerializable
{
    use OntologyAwareTrait;

    public const MAX_TRIES_PARAM_NAME = 'maxTries';

    /**
     * @throws InvalidServiceManagerException
     * @throws common_exception_Error
     * @throws core_kernel_persistence_Exception
     */
    public function __invoke($params)
    {
        $params = $this->getProcessDataService()->prepareMetaData($params);

        $report = new Report(Report::TYPE_SUCCESS);

        $params[ProcessDataService::PARAM_COUNT] = $params[ProcessDataService::PARAM_COUNT] ?? 0;
        if ($params[ProcessDataService::PARAM_COUNT] < $params[self::MAX_TRIES_PARAM_NAME]) {
            $params[ProcessDataService::PARAM_COUNT]++;
            try {
                $this->getPersistDataService()->persist($params);
                $report->setMessage(sprintf(
                    'Success MetaData syncing for delivery: %s',
                    $params[ProcessDataService::PARAM_RESOURCE_ID]
                ));
            } catch (Throwable $exception) {
                $this->logError(sprintf(
                    'Failing MetaData syncing for delivery: %s with message: %s',
                    $params[ProcessDataService::PARAM_RESOURCE_ID],
                    $exception->getMessage()
                ));

                $report->setType(Report::TYPE_ERROR);
                $report->setMessage($exception->getMessage());
                $this->requeueTask($params);
            }
        }

        return $report;
    }

    public function jsonSerialize(): string
    {
        return self::class;
    }

    /**
     * @throws InvalidServiceManagerException
     */
    private function getQueueDispatcher(): QueueDispatcher
    {
        return $this->getServiceLocator()->get(QueueDispatcher::SERVICE_ID);
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
            $params,
            __(
                'DataStore sync retry number "%s" for test of delivery with id: "%s".',
                $params[ProcessDataService::PARAM_COUNT],
                $params[ProcessDataService::PARAM_RESOURCE_ID]
            )
        );
    }

    private function getPersistDataService(): PersistDataService
    {
        return $this->getServiceLocator()->get(PersistDataService::class);
    }

    private function getProcessDataService(): ProcessDataService
    {
        return $this->getServiceLocator()->get(ProcessDataService::class);
    }
}
