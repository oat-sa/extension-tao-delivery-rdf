<?php
/**
 * Copyright (c) 2017 Open Assessment Technologies, S.A.
 *
 */

namespace oat\taoDeliveryRdf\scripts\tools;

use common_report_Report;
use oat\oatbox\extension\AbstractAction;
use common_report_Report as Report;
use oat\oatbox\extension\script\ScriptAction;
use oat\taoDeliveryRdf\model\Delete\DeliveryDeleteRequest;
use oat\taoDeliveryRdf\model\Delete\DeliveryDeleteService;

/**
 * sudo -u www-data php index.php 'oat\taoDeliveryRdf\scripts\tools\DeleteDeliveryScript'
 *
 * Class TerminateSession
 * @package oat\taoDeliveryRdf\scripts\tools
 */
class DeleteDeliveryScript extends ScriptAction
{
    /**
     * @var common_report_Report
     */
    private $report;

    protected function showTime()
    {
        return true;
    }

    protected function provideUsage()
    {
        return [
            'prefix' => 'h',
            'longPrefix' => 'help',
            'description' => 'Prints a help statement'
        ];
    }

    protected function provideOptions()
    {
        return [
            'delivery' => [
                'prefix' => 'd',
                'longPrefix' => 'delivery',
                'required' => true,
                'description' => 'A delivery ID.'
            ]
        ];
    }

    protected function provideDescription()
    {
        return 'TAO Delivery - Delete Delivery';
    }

    /**
     * @return Report
     * @throws \common_exception_Error
     */
    protected function run()
    {
        $this->report = common_report_Report::createInfo('Deleting Delivery ...');

        /** @var DeliveryDeleteService $deliveryDeleteService */
        $deliveryDeleteService = $this->getServiceLocator()->get(DeliveryDeleteService::SERVICE_ID);

        $deliveryId = $this->getOption('delivery');
        try{
            $deliveryDeleteService->execute(new DeliveryDeleteRequest($deliveryId));
            $this->report->add($deliveryDeleteService->getReport());
        } catch (\Exception $exception) {
            $this->report->add(common_report_Report::createFailure('Failing deleting delivery: '. $deliveryId));
            $this->report->add(common_report_Report::createFailure($exception->getMessage()));
        }

        return $this->report;
    }
}
