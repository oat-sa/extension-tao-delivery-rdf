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
 * Copyright (c) 2018-2022 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoDeliveryRdf\helper;

use common_Exception;
use common_exception_Error;
use common_exception_NotFound;
use common_ext_ExtensionException;
use oat\oatbox\log\LoggerService;
use oat\oatbox\service\exception\InvalidServiceManagerException;
use oat\oatbox\service\ServiceManager;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionReactivated;
use oat\taoQtiTest\models\QtiTestExtractionFailedException;
use oat\taoQtiTest\models\TestSessionService;
use oat\taoQtiTest\models\TestSessionState\Api\TestSessionStateRestorationInterface;
use oat\taoQtiTest\models\TestSessionState\Exception\RestorationImpossibleException;
use qtism\runtime\storage\common\StorageException;
use qtism\runtime\tests\AssessmentTestSessionState;

class SessionStateHelper
{
    /**
     * @param DeliveryExecutionReactivated $event
     * @throws StorageException
     * @throws common_Exception
     * @throws common_exception_NotFound
     * @throws common_ext_ExtensionException
     * @throws InvalidServiceManagerException
     * @throws QtiTestExtractionFailedException
     * @throws common_exception_Error
     */
    public static function onExecutionReactivation(DeliveryExecutionReactivated $event)
    {
        $testSessionService = self::getTestSessionService();
        $deliveryExecution = $event->getDeliveryExecution();
        $session = $testSessionService->getTestSession($deliveryExecution);
        if (!$session) {
            try {
                self::getTestSessionStateRestorationService()->restore($event->getDeliveryExecution());
            } catch (RestorationImpossibleException $e) {
                self::getLoggerService()->warning($e->getMessage());
                return;
            }

            $session = $testSessionService->getTestSession($deliveryExecution);
        }

        if ($session) {
            $session->setState(AssessmentTestSessionState::SUSPENDED);
            $testSessionService->persist($session);
        }
    }

    private static function getTestSessionService(): TestSessionService
    {
        return ServiceManager::getServiceManager()->getContainer()->get(TestSessionService::SERVICE_ID);
    }

    private static function getTestSessionStateRestorationService(): TestSessionStateRestorationInterface
    {
        return ServiceManager::getServiceManager()->getContainer()->get(TestSessionStateRestorationInterface::class);
    }

    private static function getLoggerService(): LoggerService
    {
        return ServiceManager::getServiceManager()->getContainer()->get(LoggerService::SERVICE_ID);
    }
}
