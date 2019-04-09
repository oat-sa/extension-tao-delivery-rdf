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
 * Copyright (c) 2017-2018 (original work) Open Assessment Technologies SA;
 */

namespace oat\taoDeliveryRdf\scripts;

use oat\oatbox\event\EventManager;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionReactivated;
use oat\taoDeliveryRdf\helper\SessionStateHelper;

/**
 * @author Antoine Robin <antoine@taotesting.com>
 */
class RegisterEvents extends \common_ext_action_InstallAction
{
    public function __invoke($params)
    {
        $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);

        $eventManager->attach(
            'oat\\taoDeliveryRdf\\model\\event\\DeliveryCreatedEvent',
            ['oat\\taoDeliveryRdf\\model\\TestRunnerFeatures', 'enableDefaultFeatures']
        );

        $eventManager->attach(
            DeliveryExecutionReactivated::class,
            [SessionStateHelper::class, 'onExecutionReactivation']
        );

        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, 'Events attached');
    }
}
