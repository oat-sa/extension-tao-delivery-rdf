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

    /**
     * @throws InvalidServiceManagerException
     * @throws common_exception_Error
     * @throws core_kernel_persistence_Exception
     */
    public function __invoke($params)
    {
        $report = new Report(Report::TYPE_SUCCESS);
        /** @var ResourceTransferDTO $resourceTransferDTO */
        $resourceTransferDTO = $params[0];
        $tryNumber = $params[1];

        if ($tryNumber < $resourceTransferDTO->getMaxTries()) {
            $tryNumber++;
            try {
                $this->getPersistDataService()->persist($params);
                $report->setMessage(sprintf(
                    'Success MetaData syncing for delivery: %s',
                    $resourceTransferDTO->getResourceId()
                ));
            } catch (Throwable $exception) {
                $this->logError(sprintf(
                    'Failing MetaData syncing for delivery: %s with message: %s',
                    $resourceTransferDTO->getResourceId(),
                    $exception->getMessage()
                ));

                $report->setType(Report::TYPE_ERROR);
                $report->setMessage($exception->getMessage());
                $this->requeueTask($resourceTransferDTO, $tryNumber);
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
    private function requeueTask(ResourceTransferDTO $resourceTransferDTO, int $tryNumber): void
    {
        $queueDispatcher = $this->getQueueDispatcher();
        $queueDispatcher->createTask(
            $this,
            [$resourceTransferDTO, $tryNumber],
            __(
                'DataStore sync retry number "%s" for test of delivery with id: "%s".',
                $tryNumber,
                $resourceTransferDTO->getResourceId()
            )
        );
    }

    private function getPersistDataService(): PersistDataService
    {
        return $this->getServiceLocator()->get(PersistDataService::class);
    }
}
