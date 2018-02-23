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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoDeliveryRdf\scripts\tools;


use oat\oatbox\extension\AbstractAction;
use oat\oatbox\event\EventManager;
use oat\oatbox\filesystem\FileSystemService;
use oat\taoDeliveryRdf\model\Delete\DeliveryDeleteService;
use oat\taoDeliveryRdf\model\DeliveryArchiveService;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoDeliveryRdf\model\event\DeliveryRemovedEvent;
/**
 * Class RegisterDeliveryArchiveEvent
 *
 * sudo -u www-data php index.php 'oat\taoDeliveryRdf\scripts\tools\UnRegisterDeliveryArchive'
 *
 * @package oat\taoDeliveryRdf\scripts\tools
 */
class UnRegisterDeliveryArchive extends AbstractAction
{
    /**
     * @param $params
     * @return \common_report_Report
     * @throws \common_Exception
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    public function __invoke($params)
    {

        $this->getServiceManager()->unregister(DeliveryArchiveService::SERVICE_ID);

        /** @var FileSystemService $fileSystemService */
        $fileSystemService = $this->getServiceLocator()->get(FileSystemService::SERVICE_ID);
        $fileSystemService->unregisterFileSystem(DeliveryArchiveService::BUCKET_DIRECTORY);
        $this->registerService(FileSystemService::SERVICE_ID, $fileSystemService);

        /** @var EventManager $eventManager */
        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);
        $eventManager->detach(DeliveryCreatedEvent::class, [
            DeliveryArchiveService::SERVICE_ID,
            'catchDeliveryCreated'
        ]);
        $eventManager->detach(DeliveryRemovedEvent::class, [
            DeliveryArchiveService::SERVICE_ID,
            'catchDeliveryRemoved'
        ]);

        $this->registerService(EventManager::SERVICE_ID, $eventManager);

        /** @var DeliveryDeleteService $deliveryDeleteService */
        $deliveryDeleteService = $this->getServiceLocator()->get(DeliveryDeleteService::SERVICE_ID);
        $deleteServices = $deliveryDeleteService->getOption(DeliveryDeleteService::OPTION_DELETE_DELIVERY_DATA_SERVICES);
        if (($key = array_search(DeliveryArchiveService::SERVICE_ID, $deleteServices)) !== false) {
            unset($deleteServices[$key]);
            $deliveryDeleteService->setOption(DeliveryDeleteService::OPTION_DELETE_DELIVERY_DATA_SERVICES, $deleteServices);
            $this->registerService(DeliveryDeleteService::SERVICE_ID, $deliveryDeleteService);
        }
        
        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS,
            DeliveryArchiveService::BUCKET_DIRECTORY . ' unregister and event detached');
    }
}