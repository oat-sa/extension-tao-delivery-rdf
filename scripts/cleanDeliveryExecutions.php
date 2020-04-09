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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoDeliveryRdf\scripts;

//Load extension to define necessary constants.
\common_ext_ExtensionsManager::singleton()->getExtensionById('taoDeliveryRdf');

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\extension\AbstractAction;
use oat\taoDelivery\model\execution\implementation\KeyValueService;
use oat\taoDelivery\model\execution\OntologyDeliveryExecution;
use oat\taoDelivery\model\execution\OntologyService;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoResultServer\models\classes\implementation\ResultServerService;

class cleanDeliveryExecutions extends AbstractAction
{
    use OntologyAwareTrait;

    /**
     * @var \core_kernel_classes_Class
     */
    protected $deliveryClass = null;

    /**
     * @var \common_report_Report
     */
    protected $finalReport = null;

    public function __invoke($params)
    {

        $this->verifyParams($params);

        if ($this->finalReport->containsError()) {
            return $this->finalReport;
        }

        $this->removeServiceState();

        $this->removeResults();

        $this->removeDeliveryExecutions();

        $this->finalReport->setMessage('done');

        return $this->finalReport;
    }

    protected function verifyParams($params)
    {
        $this->finalReport = new \common_report_Report(\common_report_Report::TYPE_SUCCESS);

        $class_uri = array_shift($params);

        $deliveryRootClass = $this->getClass(DeliveryAssemblyService::CLASS_URI);
        if (is_null($class_uri)) {
            $deliveryClass = $deliveryRootClass;
        } else {
            $deliveryClass = $this->getClass($class_uri);
            if (!$deliveryClass->isSubClassOf($deliveryRootClass)) {
                $msg = "Usage: php index.php '" . __CLASS__ . "' [CLASS_URI]" . PHP_EOL;
                $msg .= "CLASS_URI : a valid delivery class uri" . PHP_EOL . PHP_EOL;
                $msg .= "Uri : " . $class_uri . " is not a valid delivery class" . PHP_EOL;
                $this->finalReport->add(\common_report_Report::createFailure($msg));
            }
        }
        $this->deliveryClass = $deliveryClass;
    }

    protected function removeResults()
    {
        $report = new \common_report_Report(\common_report_Report::TYPE_SUCCESS);
        try {
            /** @var ResultServerService $resultService */
            $resultService = $this->getServiceLocator()->get(ResultServerService::SERVICE_ID);
            $deliveries = $this->deliveryClass->getInstances(true);
            $count = 0;

            foreach ($deliveries as $delivery) {
                $implementation = $resultService->getResultStorage($delivery->getUri());
                foreach ($implementation->getResultByDelivery([$delivery->getUri()]) as $result) {
                    if ($implementation->deleteResult($result['deliveryResultIdentifier'])) {
                        $count++;
                    } else {
                        $report->setType(\common_report_Report::TYPE_ERROR);
                        $report->setMessage('Cannot cleanup results for ' . $result['deliveryResultIdentifier']);
                    }
                }
            }

            $report->setMessage('Removed ' . $count . ' on ' . count($deliveries) . ' RDS results');
        } catch (\common_Exception $e) {
            $report->setType(\common_report_Report::TYPE_ERROR);
            $report->setMessage('Cannot cleanup Results: ' . $e->getMessage());
        }

        $this->finalReport->add($report);
    }

    protected function removeServiceState()
    {
        $report = new \common_report_Report(\common_report_Report::TYPE_SUCCESS);
        // service states
        $persistence = \common_persistence_KeyValuePersistence::getPersistence(\tao_models_classes_service_StateStorage::PERSISTENCE_ID);
        if ($persistence instanceof \common_persistence_AdvKeyValuePersistence) {
            $count = 0;
            foreach ($persistence->keys('tao:state:*') as $key) {
                if (substr($key, 0, 10) == 'tao:state:') {
                    $persistence->del($key);
                    $count++;
                }
            }
            $report->setMessage('Removed ' . $count . ' states');
        } elseif ($persistence instanceof \common_persistence_KeyValuePersistence) {
            try {
                if ($persistence->purge()) {
                    $report->setMessage('States correctly removed');
                }
            } catch (\common_exception_NotImplemented $e) {
                $report->setType(\common_report_Report::TYPE_ERROR);
                $report->setMessage($e->getMessage());
            }
        } else {
            $report->setType(\common_report_Report::TYPE_ERROR);
            $report->setMessage('Cannot cleanup states from ' . get_class($persistence));
        }

        $this->finalReport->add($report);
    }

    protected function removeDeliveryExecutions()
    {
        $report = new \common_report_Report(\common_report_Report::TYPE_SUCCESS);

        // deliveryExecutions
        $extension = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDelivery');
        $deliveryService = $extension->getConfig('execution_service');
        if ($deliveryService instanceof KeyValueService) {
            $persistenceOption = $deliveryService->getOption(KeyValueService::OPTION_PERSISTENCE);
            $persistence = \common_persistence_KeyValuePersistence::getPersistence($persistenceOption);
            $count = 0;
            foreach ($persistence->keys('kve_*') as $key) {
                if (substr($key, 0, 4) == 'kve_') {
                    $persistence->del($key);
                    $count++;
                }
            }
            $report->setMessage('Removed ' . $count . ' key-value delivery executions');
        } elseif ($deliveryService instanceof OntologyService) {
            $count = 0;
            $deliveryExecutionClass = new \core_kernel_classes_Class(OntologyDeliveryExecution::CLASS_URI);
            $deliveryExecutions = $deliveryExecutionClass->getInstances();
            /** @var  \core_kernel_classes_Class $deliveryExecution */
            foreach ($deliveryExecutions as $deliveryExecution) {
                $deliveryExecution->delete(true);
                $count++;
            }
            $report->setMessage('Removed ' . $count . ' ontology delivery executions');
        } else {
            $report->setType(\common_report_Report::TYPE_ERROR);
            $report->setMessage('Cannot cleanup delivery executions from ' . get_class($deliveryService));
        }

        $this->finalReport->add($report);
    }
}
