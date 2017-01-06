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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 *
 */
namespace oat\taoDeliveryRdf\scripts;

use common_report_Report as Report;
use oat\oatbox\extension\AbstractAction;
use oat\tao\model\accessControl\func\AccessRule;
use oat\tao\model\accessControl\func\AclProxy;
use oat\tao\model\entryPoint\EntryPointService;
use oat\tao\model\user\TaoRoles;
use oat\taoDeliveryRdf\model\guest\GuestAccess;


/**
 * Class DeactivateGuest
 * @package oat\taoDeliveryRdf\scripts
 * @author Antoine Robin, <antoine@taotesting.com>
 *
 * Run examples:
 *
 * - activate Guest Access
 * ```
 * sudo -u www-data php index.php 'oat\taoDeliveryRdf\scripts\ToggleGuestAccess' on
 * ```
 *
 * - deactivate Guest Access
 * ```
 * sudo -u www-data php index.php 'oat\taoDeliveryRdf\scripts\ToggleGuestAccess' off
 * ```
 */
class ToggleGuestAccess extends AbstractAction
{


    /**
     * @var EntryPointService
     */
    protected $entryPointService;


    /**
     * @param $params
     * @return Report
     */
    public function __invoke($params)
    {
        $mode = array_shift($params);
        $mode = strtolower($mode);
        $report = Report::createFailure('Please enter a valid mode on/off');
        $this->entryPointService = $this->getServiceManager()->get(EntryPointService::SERVICE_ID);
        if ($mode === 'off') {
            $report = $this->deactivate();
        } else {
            if ($mode === 'on') {
                $report = $this->activate();
            }
        }

        $this->getServiceManager()->register(EntryPointService::SERVICE_ID, $this->entryPointService);

        return $report;


    }

    /**
     * Try to deactivate the guest entry point
     * @return Report
     */
    private function deactivate()
    {
        $guestAccess = new GuestAccess();
        try {
            if ($this->entryPointService->deactivateEntryPoint(
                $guestAccess->getId(),
                EntryPointService::OPTION_PRELOGIN
            )
            ) {
                $rule = new AccessRule('grant', TaoRoles::ANONYMOUS, 'oat\taoDeliveryRdf\controller\Guest@guest');
                AclProxy::revokeRule($rule);

                return Report::createSuccess('The guest entry point has been correctly deactivated');
            } else {
                return Report::createInfo('The guest entry point was already deactivated');
            }
        } catch (\common_exception_InconsistentData $e) {
            return Report::createFailure($e->getMessage());
        }
    }

    /**
     * Try to activate the guest entry point
     * @return Report
     */
    private function activate()
    {
        $guestAccess = new GuestAccess();
        try {
            $this->entryPointService->activateEntryPoint($guestAccess->getId(), EntryPointService::OPTION_PRELOGIN);
            $rule = new AccessRule('grant', TaoRoles::ANONYMOUS, 'oat\taoDeliveryRdf\controller\Guest@guest');
            AclProxy::applyRule($rule);

            return Report::createSuccess('The guest entry point has been correctly activated');
        } catch (\common_exception_InconsistentData $e) {
            return Report::createFailure($e->getMessage());
        }
    }

}