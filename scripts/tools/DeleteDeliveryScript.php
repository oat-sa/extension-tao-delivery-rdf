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
 * Copyright (c) 2017-2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\scripts\tools;

use Exception;
use oat\oatbox\reporting\Report;
use oat\oatbox\extension\script\ScriptAction;
use oat\taoDeliveryRdf\model\Delete\DeliveryDeleteRequest;
use oat\taoDeliveryRdf\model\Delete\DeliveryDeleteService;

/**
 * sudo -u www-data php index.php 'oat\taoDeliveryRdf\scripts\tools\DeleteDeliveryScript'
 *
 * Class TerminateSession
 *
 * @package oat\taoDeliveryRdf\scripts\tools
 */
class DeleteDeliveryScript extends ScriptAction
{
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
            'delivery'  => [
                'prefix'      => 'd',
                'longPrefix'  => 'delivery',
                'required'    => true,
                'description' => 'A delivery ID',
            ],
            'recursive' => [
                'prefix'      => 'r',
                'longPrefix'  => 'recursive',
                'flag'        => true,
                'description' => 'Remove all the linked resources such as Tests or Items',
            ],
        ];
    }

    protected function provideDescription(): string
    {
        return 'TAO Delivery - Delete Delivery';
    }

    protected function run(): Report
    {
        $report = Report::createInfo('Deleting Delivery ...');

        /** @var DeliveryDeleteService $deliveryDeleteService */
        $deliveryDeleteService = $this->getServiceLocator()->get(DeliveryDeleteService::class);

        $deliveryId = $this->getOption('delivery');
        try {
            $deliveryDeleteService->execute($this->createDeliveryDeleteRequest());
            $report->add($deliveryDeleteService->getReport());
        } catch (Exception $exception) {
            $report->add(Report::createError('Failing deleting delivery: ' . $deliveryId));
            $report->add(Report::createError($exception->getMessage()));
        }

        return $report;
    }

    private function createDeliveryDeleteRequest(): DeliveryDeleteRequest
    {
        $deliveryDeleteRequest = new DeliveryDeleteRequest($this->getOption('delivery'));

        if ($this->getOption('recursive')) {
            $deliveryDeleteRequest->setIsRecursive();
        }

        return $deliveryDeleteRequest;
    }
}
