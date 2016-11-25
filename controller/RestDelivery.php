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
 */

namespace oat\taoDeliveryRdf\controller;

use \oat\taoDeliveryRdf\model\tasks\CompileDelivery;

class RestDelivery extends \tao_actions_RestController
{
	const REST_DELIVERY_TEST_ID = 'test';

    /**
     * Generate a delivery from test uri
     * Test uri has to be set and existing
     */
    public function generate()
    {   
        try {
            if (!$this->hasRequestParameter(self::REST_DELIVERY_TEST_ID)) {
                throw new \common_exception_MissingParameter(self::REST_DELIVERY_TEST_ID, $this->getRequestURI());
            }

            $test = new \core_kernel_classes_Resource($this->getRequestParameter(self::REST_DELIVERY_TEST_ID));
            if (!$test->exists()) {
                throw new \common_exception_NotFound('Unable to find a test associated to the given uri.');
            }
            $task = CompileDelivery::createTask($test);


            $result = [
                'reference_id' => $task->getId()
            ];
            $report = $task->getReport();
            if (!empty($report)) {
                if ($report instanceof \common_report_Report) {
                    //serialize report to array
                    $report = json_encode($report);
                    $report = json_decode($report);
                }
                $result['report'] = $report;
            }
            return $this->returnSuccess($result);

        } catch (\Exception $e) {
            $this->returnFailure($e);
        }
    }
}
