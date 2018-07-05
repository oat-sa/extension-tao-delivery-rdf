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
 */

namespace oat\taoDeliveryRdf\scripts\install;

use oat\oatbox\extension\script\ScriptAction;
use oat\oatbox\service\ServiceNotFoundException;
use oat\taoDeliveryRdf\model\Delete\DeliveryDeleteService;
use common_report_Report as Report;

/**
 * Class RegisterDeliveryDeleteService
 *
 * This script aims at providing a way to register/configure the DeliveryDeleteService. The --services option
 * enables the invoker to setup, as a comma-separated list of service IDs, the set of services to be in use.
 *
 * @package oat\taoDeliveryRdf\scripts\install
 */
class RegisterDeliveryDeleteService extends ScriptAction
{
    private $services;

    public function provideDescription()
    {
        return 'TAO Delivery RDF - Register Delivery Delete Service';
    }

    public function run()
    {
        // Main report.
        $report = new Report(Report::TYPE_INFO, "Script ended gracefully.");

        // Deal with 'services' option.
        $this->handleServicesOption();

        // Check that services requested to be taken into account exist.
        if (($serviceValidation = $this->validateServices()) !== true) {
            return new Report(Report::TYPE_ERROR, "Service '${serviceValidation}' is not registered on the platform.");
        }

        try {
            /** @var DeliveryDeleteService $deliveryDeleteService */
            $deliveryDeleteService = $this->getServiceLocator()->get(DeliveryDeleteService::SERVICE_ID);
            $report->add(new Report(Report::TYPE_INFO, "'DeliveryDeleteService' service found. Configuration will be replaced."));

            // Update service configuration.
            $deliveryDeleteService->setOption(DeliveryDeleteService::OPTION_DELETE_DELIVERY_DATA_SERVICES, $this->services);
        } catch (ServiceNotFoundException $e) {
            $report->add(new Report(Report::TYPE_INFO, "'DeliveryDeleteService' service not found. A new instance of the service will be registered."));

            // Register new service instance.
            $deliveryDeleteService = new DeliveryDeleteService([
                DeliveryDeleteService::OPTION_DELETE_DELIVERY_DATA_SERVICES => $this->services
            ]);
        }

        $this->getServiceManager()->register(DeliveryDeleteService::SERVICE_ID, $deliveryDeleteService);
        $report->add(new Report(Report::TYPE_SUCCESS, "'DeliveryDeleteService' registered."));

        return $report;
    }

    public function provideOptions()
    {
        return [
            'services' => [
                'prefix' => 's',
                'longPrefix' => 'services',
                'required' => true
            ]
        ];
    }

    protected function provideUsage()
    {
        return [
            'prefix' => 'h',
            'longPrefix' => 'help',
            'description' => 'Prints a help statement'
        ];
    }

    private function handleServicesOption()
    {
        $this->services = array_map(
            function ($service) {
                return trim($service);
            },
            explode(',', $this->getOption('services'))
        );
    }

    private function validateServices()
    {
        foreach ($this->services as $service) {
            try {
                $this->getServiceManager()->get($service);
            } catch (ServiceNotFoundException $e) {
                return $service;
            }
        }

        return true;
    }
}