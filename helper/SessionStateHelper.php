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
 *
 */

namespace oat\taoDeliveryRdf\helper;

use oat\oatbox\service\ServiceManager;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionReactivated;
use oat\taoQtiTest\models\TestSessionService;
use qtism\runtime\tests\AssessmentTestSessionState;

class SessionStateHelper
{
    /**
     * @param DeliveryExecutionReactivated $event
     * @throws \common_exception_Error
     * @throws \common_exception_MissingParameter
     * @throws \qtism\runtime\storage\common\StorageException
     */
    public static function onExecutionReactivation(DeliveryExecutionReactivated $event)
    {
        /** @var TestSessionService $testSessionService */
        $testSessionService = ServiceManager::getServiceManager()->get(TestSessionService::SERVICE_ID);
        $session = $testSessionService->getTestSession($event->getDeliveryExecution());
        if ($session) {
            $session->setState(AssessmentTestSessionState::SUSPENDED);
            $testSessionService->persist($session);
        }

    }
}