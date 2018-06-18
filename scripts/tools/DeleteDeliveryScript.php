<?php
/**
 * Copyright (c) 2017 Open Assessment Technologies, S.A.
 *
 */

namespace oat\taoDeliveryRdf\scripts\tools;

use common_report_Report;
use oat\oatbox\extension\AbstractAction;
use common_report_Report as Report;
use oat\taoDeliveryRdf\model\Delete\DeliveryDeleteRequest;
use oat\taoDeliveryRdf\model\Delete\DeliveryDeleteService;

/**
 * sudo -u www-data php index.php 'oat\taoDeliveryRdf\scripts\tools\DeleteDeliveryScript'
 *
 * Class TerminateSession
 * @package oat\taoAct\scripts\tools
 */
class DeleteDeliveryScript extends AbstractAction
{
    /**
     * @var common_report_Report
     */
    private $report;

    /**
     * @param $params
     * @return Report
     * @throws \common_exception_Error
     */
    public function __invoke($params)
    {

        if (empty($params)) {
            return new \common_report_Report(\common_report_Report::TYPE_ERROR, 'delivery id was not given');
        }
        $this->report = common_report_Report::createInfo('Deleting Delivery ...');
        $time_start = microtime(true);

        /** @var DeliveryDeleteService $deliveryDeleteService */
        $deliveryDeleteService = $this->getServiceLocator()->get(DeliveryDeleteService::SERVICE_ID);
        $this->propagate($deliveryDeleteService);

        try{
            $deliveryDeleteService->execute(new DeliveryDeleteRequest($params[0]));
            $this->report->add($deliveryDeleteService->getReport());
        } catch (\Exception $exception) {
            $this->report->add(common_report_Report::createFailure('Failing deleting delivery: '. $params[0]));
            $this->report->add(common_report_Report::createFailure($exception->getMessage()));
        }

        $time_end = microtime(true);
        $execution_time = ($time_end - $time_start)/60;

        $this->report->add(common_report_Report::createInfo('Time:' . round($execution_time, 4) .' Minutes.' ));

        return $this->report;
    }
}
