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

use oat\generisSmooth\Exception;
use oat\tao\model\TaskQueueActionTrait;
use oat\taoDeliveryRdf\model\tasks\ImportAndCompile;

/**
 * Class RestTest
 * @package oat\taoDeliveryRdf\controller
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class RestTest extends \tao_actions_RestController
{
    use TaskQueueActionTrait {
        getTask as traitGetTask;
        getTaskData as traitGetTaskData;
    }

    const REST_IMPORTER_ID = 'importerId';
    const REST_FILE_NAME = 'testPackage';

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
            $task = ImportAndCompile::createTask($importerId, $file);
            $result = ['reference_id' => $task->getId()];
            $report = $task->getReport();
            if (!empty($report)) { //already executed
                if ($report instanceof \common_report_Report) {
                    //serialize report to array
                    $report = json_encode($report);
                    $report = json_decode($report);
                }
                $result['report'] = $report;
            }
            return $this->returnSuccess($result);
        } else {
            return $this->returnFailure(new \common_exception_BadRequest('Test package file was not given'));
        }
    }

    /**
     * @return \tao_models_classes_import_ImportHandler[]
     */
    protected function getAvailableImportHandlers()
    {
        $returnValue = [];
        $testModelClass = new \core_kernel_classes_Class(CLASS_TESTMODEL);
        foreach ($testModelClass->getInstances() as $model) {
            $impl = \taoTests_models_classes_TestsService::singleton()->getTestModelImplementation($model);
            if (in_array('tao_models_classes_import_ImportProvider', class_implements($impl))) {
                foreach ($impl->getImportHandlers() as $handler) {
                    array_unshift($returnValue, $handler);
                }
            }
        }
        return $returnValue;
    }
}
