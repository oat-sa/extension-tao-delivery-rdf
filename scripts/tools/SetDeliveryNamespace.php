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
use InvalidArgumentException;
use oat\oatbox\extension\script\ScriptAction;
use oat\oatbox\reporting\Report;
use oat\taoDeliveryRdf\model\DeliveryFactory;

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
        if (!$this->getOption('namespace')) {
            return $this->unsetNamespace();
        }

        $namespace = $this->getNamespace();

        $deliveryFactory = $this->getDeliveryFactory();
        $deliveryFactory->setOption(DeliveryFactory::OPTION_NAMESPACE, $namespace);

        try {
            $this->setDeliveryFactory($deliveryFactory);
        } catch (Exception $exception) {
            return Report::createError(
                "Failed to set \"$namespace\" Delivery namespace.",
                null,
                [Report::createInfo($exception->getMessage())]
            );
        }

        return Report::createSuccess("Registered \"$namespace\" Delivery namespace.");
    }

    protected function provideDescription(): string
    {
        return 'TAO DeliveryRDF - Set up remotely published Delivery resource namespace';
    }

    private function unsetNamespace(): Report
    {
        $deliveryFactory        = $this->getDeliveryFactory();
        $deliveryFactoryOptions = $deliveryFactory->getOptions();

        unset($deliveryFactoryOptions[DeliveryFactory::OPTION_NAMESPACE]);
        $deliveryFactory->setOptions($deliveryFactoryOptions);

        $this->setDeliveryFactory($deliveryFactory);

        return Report::createSuccess('Removed a Delivery namespace.');
    }

    private function getNamespace(): string
    {
        $namespace = rtrim($this->getOption('namespace'), '#');

        if ($namespace === LOCAL_NAMESPACE) {
            throw new InvalidArgumentException(
                "Overridden namespace value must be different from a local one, \"$namespace\" given"
            );
        }

        return $namespace;
    }

    private function getDeliveryFactory(): DeliveryFactory
    {
        return $this->getServiceLocator()->get(DeliveryFactory::class);
    }

    private function setDeliveryFactory(DeliveryFactory $deliveryFactory): void
    {
        $this->getServiceManager()->register($deliveryFactory::SERVICE_ID, $deliveryFactory);
    }
}
