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

use oat\oatbox\event\Event;
use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\exception\InvalidServiceManagerException;
use oat\tao\model\featureFlag\FeatureFlagChecker;
use oat\tao\model\taskQueue\QueueDispatcher;
use oat\taoDeliveryRdf\model\event\AbstractDeliveryEvent;
use RuntimeException;
use Throwable;

class DeliveryMetadataListener extends ConfigurableService
{
    use LoggerAwareTrait;

    public const SERVICE_ID = 'taoDeliveryRdf/DeliveryMetadataListener';

    public const FILE_SYSTEM_ID = 'dataStore';

    public function whenDeliveryIsPublished(Event $event): void
    {
        $featureFlag = $this->getFeatureFlag();
        if (!$featureFlag->isEnabled('FEATURE_FLAG_ENABLE_DATA_STORE_STORAGE')) {
            return;
        }
        try {
            $this->logDebug(sprintf('Processing MetaData event for %s', get_class($event)));
            $this->checkEventType($event);
            $this->triggerSyncTask([
                ProcessDataService::PARAM_RESOURCE_ID => $event->getDeliveryUri(),
                ProcessDataService::PARAM_INCLUDE_DELIVERY_METADATA => true,
                MetaDataDeliverySyncTask::MAX_TRIES_PARAM_NAME => $this->getOption('max_tries', 10),
                ProcessDataService::PARAM_FILE_SYSTEM_ID => self::FILE_SYSTEM_ID,
            ]);
            $this->logDebug(sprintf('Event %s processed', get_class($event)));
        } catch (Throwable $exception) {
            $this->logError(sprintf(
                'Error processing event %s: %s',
                get_class($event),
                $exception->getMessage()
            ));
        }
    }

    /**
     * @throws InvalidServiceManagerException
     */
    private function getQueueDispatcher(): ConfigurableService
    {
        return $this->getServiceLocator()->get(QueueDispatcher::SERVICE_ID);
    }

    /**
     * @throws RuntimeException
     */
    private function checkEventType(Event $event): void
    {
        if (!$event instanceof AbstractDeliveryEvent) {
            throw new RuntimeException(sprintf(
                "Wrong event type. Required instance of %s, %s given",
                AbstractDeliveryEvent::class,
                get_class($event)
            ));
        }
    }

    private function triggerSyncTask(array $params): void
    {
        /** @var QueueDispatcher $queueDispatcher */
        $queueDispatcher = $this->getQueueDispatcher();
        $queueDispatcher->createTask(
            new MetaDataDeliverySyncTask(),
            $params,
            __(
                'Syncing data of a delivery "%s".',
                $params[ProcessDataService::PARAM_RESOURCE_ID]
            )
        );
    }

    private function getFeatureFlag(): FeatureFlagChecker
    {
        return $this->getServiceLocator()->get(FeatureFlagChecker::class);
    }
}
