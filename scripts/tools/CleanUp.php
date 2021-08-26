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

use core_kernel_classes_Resource as KernelResource;
use oat\oatbox\reporting\Report;
use oat\oatbox\extension\script\ScriptAction;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;

/**
 * sudo -u www-data php index.php 'oat\taoDeliveryRdf\scripts\tools\CleanUp' -d <delivery_to_keep_0>,<delivery_to_keep_1>
 *
 * Class CleanUp
 *
 * @package oat\taoDeliveryRdf\scripts\tools
 */
final class CleanUp extends ScriptAction
{
    public const OPTION_DELIVERIES = 'deliveries';

    protected function showTime(): bool
    {
        return true;
    }

    protected function provideUsage(): array
    {
        return [
            'prefix'      => 'h',
            'longPrefix'  => 'help',
            'description' => 'Prints a help statement',
        ];
    }

    protected function provideOptions(): array
    {
        return [
            self::OPTION_DELIVERIES => [
                'prefix'      => 'd',
                'longPrefix'  => self::OPTION_DELIVERIES,
                'required'    => true,
                'description' => 'A comma-separated list of Deliveries to keep on the instance, all the other Deliveries will be removed.',
            ],
        ];
    }

    protected function provideDescription(): string
    {
        return 'TAO Delivery - Clean up';
    }

    protected function run(): Report
    {
        $deliveriesToKeepMap = $this->denormalizeDeliveriesToKeep();

        $report = Report::createInfo('Cleaning up ...');

        foreach ($this->getDeliveryAssemblyService()->getAllAssemblies() as $assembly) {
            $deliveryRemovalParameters = $this->createBaseDeliveryRemovalParameters($assembly);
            $deliveryRemovalParameters[] = $this->createScriptArgument(
                isset($deliveriesToKeepMap[$assembly->getUri()])
                    ? DeleteDeliveryScript::OPTION_EXECUTIONS_ONLY
                    : DeleteDeliveryScript::OPTION_RECURSIVE
            );

            $report->add(
                $this->propagate(new DeleteDeliveryScript())($deliveryRemovalParameters)
            );
        }

        return $report;
    }

    private function denormalizeDeliveriesToKeep(): array
    {
        return array_flip(explode(',', $this->getOption(self::OPTION_DELIVERIES)));
    }

    private function createBaseDeliveryRemovalParameters(KernelResource $assembly): array
    {
        return [
            $this->createScriptArgument(DeleteDeliveryScript::OPTION_DELIVERY),
            $assembly->getUri()
        ];
    }

    private function createScriptArgument(string $argument): string
    {
        return "--$argument";
    }

    private function getDeliveryAssemblyService(): DeliveryAssemblyService
    {
        return $this->getServiceLocator()->get(DeliveryAssemblyService::class);
    }
}
