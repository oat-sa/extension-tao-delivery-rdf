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
 *
 * @author Sergei Mikhailov <sergei.mikhailov@taotesting.com>
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\scripts\tools;

use Exception;
use common_report_Report as Report;
use oat\oatbox\extension\script\ScriptAction;
use oat\taoDeliveryRdf\model\Delivery\Business\Domain\DeliveryNamespace;
use oat\taoDeliveryRdf\model\Delivery\DataAccess\DeliveryNamespaceRegistry;

/**
 * Usage:
 *
 * sudo -u www-data php index.php 'oat\taoDeliveryRdf\scripts\tools\SetDeliveryNamespace' -n https://taotesting.com
 */
class SetDeliveryNamespace extends ScriptAction
{
    protected function provideUsage(): array
    {
        return [
            'prefix'      => 'h',
            'longPrefix'  => 'help',
            'description' => 'Prints this message',
        ];
    }

    protected function provideOptions(): array
    {
        return [
            'namespace' => [
                'prefix'      => 'n',
                'longPrefix'  => 'namespace',
                'description' => 'A custom namespace for remotely published Delivery resources',
            ],
        ];
    }

    protected function run(): Report
    {
        $deliveryNamespace = $this->getDeliveryNamespace();

        try {
            $this->registerService(
                DeliveryNamespaceRegistry::SERVICE_ID,
                new DeliveryNamespaceRegistry(
                    new DeliveryNamespace(LOCAL_NAMESPACE),
                    $deliveryNamespace
                )
            );
        } catch (Exception $exception) {
            $errorReport = Report::createFailure("Failed to set \"$deliveryNamespace\" Delivery namespace.");
            $errorReport->add(Report::createInfo($exception->getMessage()));

            return $errorReport;
        }

        return Report::createSuccess("Registered \"$deliveryNamespace\" Delivery namespace.");
    }

    protected function provideDescription(): string
    {
        return 'TAO DeliveryRDF - Set up remotely published Delivery resource namespace';
    }

    private function getDeliveryNamespace(): ?DeliveryNamespace
    {
        return $this->hasOption('namespace')
            ? new DeliveryNamespace(
                $this->getOption('namespace')
            )
            : null;
    }
}
