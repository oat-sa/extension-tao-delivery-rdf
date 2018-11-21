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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */
namespace oat\taoDeliveryRdf\scripts\install;

use oat\oatbox\extension\InstallAction;
use oat\taoDelivery\model\AssignmentService;
use oat\taoDeliveryRdf\model\GuestAssignment;
use oat\tao\model\entryPoint\EntryPointService;
use oat\taoDelivery\model\entrypoint\GuestAccess;

class RegisterAssignmentService extends InstallAction
{
    /**
     * @param $params
     * @throws \common_Exception
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    public function __invoke($params)
    {
        $this->registerService(AssignmentService::SERVICE_ID, new GuestAssignment());
        $entryPointService = $this->getServiceManager()->get(EntryPointService::SERVICE_ID);
        $entryPointService->addEntryPoint(new GuestAccess(), EntryPointService::OPTION_PRELOGIN);
        $this->getServiceManager()->register(EntryPointService::SERVICE_ID, $entryPointService);
    }
}
