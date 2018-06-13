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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoDeliveryRdf\controller;

use oat\tao\model\import\ImporterNotFound;
use oat\tao\model\taskQueue\TaskLogInterface;
use oat\taoDeliveryRdf\model\tasks\ImportAndCompile;

/**
 * Class RestTest
 * @package oat\taoDeliveryRdf\controller
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class RestTest extends \tao_actions_RestController
{
    const REST_IMPORTER_ID = 'importerId';
    const REST_FILE_NAME = 'testPackage';
    const REST_DELIVERY_PARAMS = 'delivery-params';
    const REST_DELIVERY_CLASS_LABEL = 'delivery-class-label';

    /**
     * Import test and compile it into delivery
     */
    public function compileDeferred()
    {
        if ($this->getRequestMethod() !== \Request::HTTP_POST) {
            throw new \common_exception_NotImplemented('Only post method is accepted to compile test');
        }

        if (!$this->hasRequestParameter(self::REST_IMPORTER_ID)) {
            throw new \common_exception_MissingParameter(self::REST_IMPORTER_ID, $this->getRequestURI());
        }

        if (\tao_helpers_Http::hasUploadedFile(self::REST_FILE_NAME)) {
            $file = \tao_helpers_Http::getUploadedFile(self::REST_FILE_NAME);
            $importerId = $this->getRequestParameter(self::REST_IMPORTER_ID);
            try {
                $customParams = [];
                if ($this->hasRequestParameter(self::REST_DELIVERY_PARAMS)) {
                    $customParams = $this->getRequestParameter(self::REST_DELIVERY_PARAMS);
                    $customParams = json_decode(html_entity_decode($customParams), true);
                }
                $deliveryClassLabel = '';
                if ($this->hasRequestParameter(self::REST_DELIVERY_CLASS_LABEL)) {
                    $deliveryClassLabel = $this->getRequestParameter(self::REST_DELIVERY_CLASS_LABEL);
                }
                $task = ImportAndCompile::createTask($importerId, $file, $customParams, $deliveryClassLabel);
            } catch (ImporterNotFound $e) {
                $this->returnFailure(new \common_exception_NotFound($e->getMessage()));
            }
            
            $result = ['reference_id' => $task->getId()];

            /** @var TaskLogInterface $taskLog */
            $taskLog = $this->getServiceLocator()->get(TaskLogInterface::SERVICE_ID);

            $report = $taskLog->getReport($task->getId());

            if (!empty($report)) { //already executed
                if ($report instanceof \common_report_Report) {
                    //serialize report to array
                    $report = json_encode($report);
                    $report = json_decode($report);
                }
                $result['common_report_Report'] = $report;
            }

            return $this->returnSuccess($result);
        } else {
            return $this->returnFailure(new \common_exception_BadRequest('Test package file was not given'));
        }
    }
}
