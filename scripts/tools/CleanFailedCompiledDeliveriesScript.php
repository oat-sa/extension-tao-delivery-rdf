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
 *
 */

namespace oat\taoDeliveryRdf\scripts\tools;

use common_report_Report;
use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\extension\script\ScriptAction;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;

/**
 * Class CleanFailedCompiledDeliveriesScript
 * @package oat\taoDeliveryRdf\scripts\tools
 * sudo -u www-data php index.php '\oat\taoDeliveryRdf\scripts\tools\CleanFailedCompiledDeliveriesScript'
 */
class CleanFailedCompiledDeliveriesScript extends ScriptAction
{
    use OntologyAwareTrait;

    protected function provideOptions()
    {
        return [];
    }

    protected function provideDescription()
    {
        return 'TAO Delivery - Clean Failed Compiled Deliveries';
    }

    /**
     * Run Script.
     *
     * Run the userland script. Implementers will use this method
     * to implement the main logic of the script.
     *
     * @return \common_report_Report
     * @throws \oat\search\base\exception\SearchGateWayExeption
     * @throws \common_exception_Error
     */
    protected function run()
    {
        $report = common_report_Report::createInfo('Deleting Failing deliveries');

        /** @var ComplexSearchService $search */
        $search = $this->getModel()->getSearchInterface();
        $deliveryAssemblyService = DeliveryAssemblyService::singleton();

        $queryBuilder = $search->query();
        $query = $search->searchType($queryBuilder, $deliveryAssemblyService->getRootClass()->getUri(),true);
        $queryBuilder->setCriteria($query);
        $searchResult = $search->getGateway()->search($queryBuilder);

        $failedDeliveries = [];
        /** @var \core_kernel_classes_Resource $instance */
        foreach ($searchResult as $instance) {
            try {
                $value = DeliveryAssemblyService::singleton()->getRuntime($instance);
                if (is_null($value)) {
                    $failedDeliveries[] = $instance;
                }
            } catch (\common_Exception $e) {
                $failedDeliveries[] = $instance;
            }
        }

        /** @var \core_kernel_classes_Resource $failedDelivery */
        foreach ($failedDeliveries as $failedDelivery) {
            $failedDelivery->delete(true);
            $report->add(common_report_Report::createSuccess('Delivery deleted:'. $failedDelivery->getUri()));
        }

        return $report;
    }
}