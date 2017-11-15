<?php
/**
 * Copyright (c) 2017 Open Assessment Technologies, S.A.
 *
 * @author A.Zagovorichev, <zagovorichev@1pt.com>
 */


namespace oat\taoDeliveryRdf\scripts\tools;

use oat\oatbox\extension\AbstractAction;
use oat\generis\model\OntologyAwareTrait;
use oat\taoDelivery\model\execution\OntologyDeliveryExecution;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoOutcomeRds\model\RdsResultStorage;
use oat\taoDelivery\model\execution\implementation\KeyValueService;

/**
 * Start your way from this
 * php index.php "oat\taoDeliveryRdf\scripts\tools\jMeterCleaner" it will provide you your path
 *
 * But be careful not all ways are safe, make sure that you have dump of the DB before use this
 *
 * Class jMeterCleaner
 * @package oat\taoAct\scripts\tools
 */
class jMeterCleaner extends AbstractAction
{
    use OntologyAwareTrait;

    /**
     * @var \common_report_Report
     */
    private $report;

    /**
     * If one of the sections has been already done
     * @var bool
     */
    private $done = false;

    /**
     * All passed params
     * @var array
     */
    private $params = [];

    private $testTakerClass;
    private $deliveryClass;

    public function __invoke($params)
    {
        // Load needed constants
        \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDelivery');
        $extensionManager = $this->getServiceManager()->get(\common_ext_ExtensionsManager::SERVICE_ID);
        $extensionManager->getExtensionById('taoDeliveryRdf');
        $extensionManager->getExtensionById('taoDeliveryRdf');

        $this->params = $params;

        // since lti uses #user then we can't use #subject (but subject is the subclass of user then it will work)
        $this->testTakerClass = $this->getClass('http://www.tao.lu/Ontologies/generis.rdf#User');

        $deliveryService = DeliveryAssemblyService::singleton();
        $this->deliveryClass = $deliveryService->getRootClass();

        $this->report = \common_report_Report::createInfo('Report');
        $this->usage();
        $this->run();
        return $this->report;
    }

    // how it works
    private function usage()
    {
        if (empty($this->params)) {
            $usageHelper = \common_report_Report::createInfo('USAGE: What you could do here:');
            $usageHelper->add(\common_report_Report::createSuccess('Please, Note that sections must not intersect otherwise it will work according to priority'));

            $countDeliveriesForUsers = \common_report_Report::createInfo('1. Count how many deliveries has each of the user');
            $countDeliveriesForUsers->add(\common_report_Report::createInfo('--count-deliveries-by-user'));
            $countDeliveriesForUsers->add(\common_report_Report::createInfo('--open-out   `show all deliveries and executions id`'));
            $usageHelper->add($countDeliveriesForUsers);

            $openDeliveriesForUser = \common_report_Report::createInfo('2. Detailed report about deliveries for user');
            $openDeliveriesForUser->add(\common_report_Report::createInfo('--detailed-report'));
            $openDeliveriesForUser->add(\common_report_Report::createInfo('--detailed-user=[userId]'));
            $openDeliveriesForUser->add(\common_report_Report::createInfo('--open-out   `show all deliveries and executions id`'));
            $usageHelper->add($openDeliveriesForUser);

            $cleaner = \common_report_Report::createInfo('2. Clean test data that you want (The greater the force, the greater the responsibility)');
            $cleaner->add(\common_report_Report::createFailure('Note: everything will be deleted even user'));
            $cleaner->add(\common_report_Report::createInfo('--run-cleaner'));
            $cleaner->add(\common_report_Report::createInfo('--clean-user=[userId] `will be deleted user, executions and states`'));
            $cleaner->add(\common_report_Report::createInfo('--clean-user-with-his-deliveries `will be deleted user, executions, states, DELIVERIES and RESULTS`'));
            $cleaner->add(\common_report_Report::createInfo('--clean-delivery=[deliveryId] `not required. If provided, then will be deleted only that delivery and results`'));
            $usageHelper->add($cleaner);

            $executionsCleaner = \common_report_Report::createInfo('3. Delete only executions and test results (The greater the force, the greater the responsibility)');
            $executionsCleaner->add(\common_report_Report::createFailure('Note: will be deleted results, executions and services states'));
            $executionsCleaner->add(\common_report_Report::createInfo('--run-executions-cleaner'));
            $executionsCleaner->add(\common_report_Report::createInfo('--clean-user=[userId] `will be deleted executions, results and states`'));
            $executionsCleaner->add(\common_report_Report::createInfo('--clean-users-whose-label-begin-with=[string] `min length 3 symbols. Will be deleted executions, results and states only for the users whose labels begin with specified string.`'));
            $usageHelper->add($executionsCleaner);

            $this->report->add($usageHelper);
        }
    }

    // entry for the actions
    private function run()
    {
        $this->counter();
        $this->detailed();
        $this->cleaner();
        $this->executionsCleaner();
    }

    /**
     * First section with general information about users
     */
    private function counter()
    {
        if (!$this->done && in_array('--count-deliveries-by-user', $this->params)) {
            $this->done = true;

            $counterReport = \common_report_Report::createInfo('List of Users:');

            $helper = new ConsoleTableHelper();
            $helper->addHeader(['TestTaker', 'Deliveries', 'Executions']);
            $helper->addRows($this->getCountersByUsers(!in_array('--open-out', $this->params)));
            $counterReport->add($helper->generateReport());

            $this->report->add($counterReport);
        }
    }

    private function getCountersByUsers($counted = true)
    {
        $deliveries = $this->deliveryClass->getInstances(true);
        $testTakers = $this->testTakerClass->getInstances(true);
        $src = [];

        foreach ($deliveries as $delivery) {
            foreach ($testTakers as $testTaker) {
                 $this->getUserDeliveryData($testTaker, $delivery, $counted, $src);
            }
        }

        return $this->convertSrcToData($src, $counted);
    }

    private function convertSrcToData($src, $counted)
    {
        if ($counted) {
            $data = [];
            foreach ($src as $ttLabel => $ttData) {
                $row = [];
                $row[] = $ttLabel;
                $row[] = count($ttData); // count of the deliveries
                $row[] = array_sum($ttData); // count of the executions
                $data[] = $row;
            }
        } else {
            $data = $src;
        }

        return $data;
    }

    private function getUserDeliveryData($testTaker, $delivery, $counted, &$src)
    {
        $executions = ServiceProxy::singleton()->getUserExecutions($delivery, $testTaker->getUri());
        foreach ($executions as $execution) {
            /*
             * Uncounted source
             */
            if (!$counted) {
                $row = [];
                $row[] = $testTaker->getUri() . ' ('.$testTaker->getLabel().')';
                $row[] = $delivery->getUri();
                $row[] = $execution->getIdentifier();
                $src[] = $row;
            } else {
                $ttLabel = $testTaker->getUri() . ' ('.$testTaker->getLabel().')';
                if (isset($src[$ttLabel])) {
                    if (isset($src[$ttLabel][$delivery->getUri()])) {
                        $src[$ttLabel][$delivery->getUri()]++;
                    } else {
                        $src[$ttLabel][$delivery->getUri()] = 1;
                    }
                } else {
                    $src[$ttLabel] = [ $delivery->getUri() => 1];
                }
            }
        }
    }

    /**
     * Second section with detailed information about the specified user
     */
    private function detailed()
    {
        if (!$this->done && in_array('--detailed-report', $this->params)) {
            $this->done = true;
            $details = $this->getDetailsForUser(!in_array('--open-out', $this->params));
            if ($details !== false) {
                $counterReport = \common_report_Report::createInfo('Detailed report for the user');
                $helper = new ConsoleTableHelper();
                $helper->addHeader(['TestTaker', 'Deliveries', 'Executions']);
                $helper->addRows($details);
                $counterReport->add($helper->generateReport());
                $this->report->add($counterReport);
            }
        }
    }

    /**
     * Get parameter from the list of parameters as Resource
     * @param string $prefix
     * @return bool|\core_kernel_classes_Resource
     */
    private function getResourceFromParameter($prefix = '--unique-prefix')
    {
        $resource = null;
        $hasParameter = false;

        $val = $this->getParameterValue($prefix);
        if ($val) {
            $hasParameter = true;
            $resource = $this->getResource($val);
            if (!$resource->exists()) {
                $resource = null;
            }
        }

        return $hasParameter ? $resource : false;
    }

    private function getParameterValue($prefix = '--unique-prefix')
    {
        $value = false;
        foreach ($this->params as $param) {
            if (mb_strpos($param, $prefix) !== false) {
                $value = str_replace($prefix, '', $param);
                $value = trim($value, '[]');
                break;
            }
        }

        return $value;
    }

    private function getDetailsForUser($counted = true)
    {
        $testTaker = $this->getResourceFromParameter('--detailed-user=');

        if (!$testTaker) {
            $this->report->add(\common_report_Report::createFailure('--detailed-user=[userId] is required and need to be a Resource'));
            return false;
        }

        return $this->getDataByTestTaker($testTaker, $counted);
    }

    private function getDataByTestTaker($testTaker, $counted)
    {
        $deliveries = $this->deliveryClass->getInstances(true);
        $src = [];
        foreach ($deliveries as $delivery) {
            $this->getUserDeliveryData($testTaker, $delivery, $counted, $src);
        }

        return $this->convertSrcToData($src, $counted);
    }

    /**
     * The most dangerous of the sections which will affect on the stored data
     */
    private function cleaner()
    {
        if (!$this->done && in_array('--run-cleaner', $this->params)) {
            $this->done = true;

            $testTaker = $this->getResourceFromParameter('--clean-user=');
            if (!$testTaker) {
                $this->report->add(\common_report_Report::createFailure('--clean-user=[userId] is required and need to be a Resource'));
                return false;
            }

            $delivery = $this->getResourceFromParameter('--clean-delivery=');
            if ($delivery) {
                $this->report->add(\common_report_Report::createInfo('Deleting of the delivery data [' . $delivery->getUri() . ']'));
            } elseif ($delivery === null) {
                $this->report->add(\common_report_Report::createFailure('Delivery does not found'));
                return false;
            } else {
                $this->report->add(\common_report_Report::createInfo('Deleting of the TestTaker data [' . $testTaker->getUri() . ']'));
            }

            $ttData = $this->getDataByTestTaker($testTaker, false);
            if (!count($ttData)) {
                $this->report->add(\common_report_Report::createFailure('TestTaker with id [' . $testTaker->getUri() . '] has not been found'));
                return false;
            } elseif($delivery) {
                $hasDelivery = false;
                foreach ($ttData as $row) {
                    if ($row[1] == $delivery->getUri()) {
                        $hasDelivery = true;
                        break;
                    }
                }

                if (!$hasDelivery) {
                    $this->report->add(\common_report_Report::createFailure('Delivery with id [' . $testTaker->getUri() . '] has not been found'));
                    return false;
                }
            }

            if ($delivery) {
                $this->deleteDelivery($delivery);
            } else {
                $this->deleteTestTaker($testTaker, $ttData);
            }
        }
        return true;
    }

    private function executionsCleaner()
    {
        if (!$this->done && in_array('--run-executions-cleaner', $this->params)) {
            $this->done = true;

            $testTaker = $this->getResourceFromParameter('--clean-user=');
            $labelBeginWith = $this->getParameterValue('--clean-users-whose-label-begin-with=');

            if (!$testTaker && !$labelBeginWith) {
                $this->report->add(\common_report_Report::createFailure('You should use one of the --clean-user or --clean-users-whose-label-begin-with, not together'));
                return false;
            }

            if ($testTaker && $labelBeginWith) {
                $this->report->add(\common_report_Report::createFailure('You can use --clean-user or --clean-users-whose-label-begin-with, not together'));
                return false;
            }

            if (mb_strlen($labelBeginWith) < 3) {
                $this->report->add(\common_report_Report::createFailure('Value of the --clean-users-whose-label-begin-with can not be less then 3 symbols'));
                return false;
            }

            if ($testTaker) {
                // clean his data
                $this->cleanTestTakersExecutions($testTaker);
            }

            if ($labelBeginWith) {
                // clean all test takers according to this mask
                $this->cleanExecutionsByMask($labelBeginWith);
            }
        }
        return true;
    }

    private function cleanTestTakersExecutions($testTaker)
    {
        $ttData = $this->getDataByTestTaker($testTaker, false);
        if (!count($ttData)) {
            $this->report->add(\common_report_Report::createFailure('TestTaker with id [' . $testTaker->getUri() . '] has not been found'));
            return false;
        }

        // delete deliveries results
        $deliveries = [];
        foreach ($ttData  as $row) {
            if (isset($row[1])) {
                $deliveries[] = $row[1];
            }
        }

        $removeResultsReport = $this->removeResults($deliveries);
        $this->report->add($removeResultsReport);

        // delete executions
        $executionRemovedReport = $this->removeDeliveryExecutions($testTaker->getUri());
        $this->report->add($executionRemovedReport);

        // delete states
        $statesRemovedReport = $this->removeServiceState($testTaker->getUri());
        $this->report->add($statesRemovedReport);

        $this->report->add(\common_report_Report::createSuccess('TestTakers data about results and executions were cleaned'));
    }

    private function cleanExecutionsByMask($labelBeginWith = '')
    {
        $data = $this->getCountersByUsers();
        foreach ($data as $row) {
            if ( $pos = mb_strpos($row[0], '(' . $labelBeginWith) ) {
                $uri = trim(mb_substr($row[0], 0, $pos));
                $testTaker = $this->getResource($uri);
                $this->cleanTestTakersExecutions($testTaker);
            }
        }
    }

    private function deleteDelivery($delivery)
    {
        if (!$delivery->exists()) {
            return false;
        }
        $resultRemoveReport = $this->removeResults((array)$delivery->getUri());
        $this->report->add($resultRemoveReport);
        $delivery->delete(true);
        $this->report->add(\common_report_Report::createSuccess('Delivery deleted'));
    }

    private function deleteTestTaker($testTaker, $ttData)
    {
        if (in_array('--clean-user-with-his-deliveries', $this->params)) {
            foreach ($ttData as $executionRow) {
                if (isset($executionRow[1])){
                    $this->deleteDelivery($this->getResource($executionRow[1]));
                }
            }
        }

        $executionRemovedReport = $this->removeDeliveryExecutions($testTaker->getUri());
        $this->report->add($executionRemovedReport);

        $statesRemovedReport = $this->removeServiceState($testTaker->getUri());
        $this->report->add($statesRemovedReport);

        $testTaker->delete(true);
        $this->report->add(\common_report_Report::createSuccess('TestTaker deleted'));
    }

    private function removeResults(array $deliveries){
        $report = new \common_report_Report(\common_report_Report::TYPE_SUCCESS);
        try {
            // results rds
            $rdsStorage = new RdsResultStorage();
            $deliveryIds = $rdsStorage->getResultByDelivery($deliveries);
            $count = 0;
            foreach ($deliveryIds as $deliveryId) {
                if ($rdsStorage->deleteResult($deliveryId['deliveryResultIdentifier'])) {
                    $count++;
                } else {
                    $report->setType(\common_report_Report::TYPE_ERROR);
                    $report->setMessage('Cannot cleanup results for ' . $deliveryId['deliveryResultIdentifier']);
                }

            }
            $report->setMessage('Removed ' . $count . ' on ' . count($deliveryIds) . ' RDS results');

        } catch (\common_Exception $e) {
            $report->setType(\common_report_Report::TYPE_ERROR);
            $report->setMessage('Cannot cleanup Results: ' . $e->getMessage());
        }

        return $report;
    }

    private function removeDeliveryExecutions($userUri){
        $report = new \common_report_Report(\common_report_Report::TYPE_SUCCESS);

        // deliveryExecutions
        $extension = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDelivery');
        $deliveryService = $extension->getConfig('execution_service');
        if ($deliveryService instanceof KeyValueService) {
            $persistenceOption = $deliveryService->getOption(KeyValueService::OPTION_PERSISTENCE);
            $persistence = \common_persistence_KeyValuePersistence::getPersistence($persistenceOption);
            $count = 0;
            foreach ($persistence->keys('kve_*'.$userUri.'*') as $key) {
                if (substr($key, 0, 4) == 'kve_') {
                    $persistence->del($key);
                    $count++;
                }
            }
            $report->setMessage('Removed ' . $count . ' key-value delivery executions of '. $userUri);
        } elseif ($deliveryService instanceof OntologyDeliveryExecution) {
            $count = 0;
            $deliveryExecutionClass = new \core_kernel_classes_Class(OntologyDeliveryExecution::CLASS_URI);
            $deliveryExecutions = $deliveryExecutionClass->searchInstances( [OntologyDeliveryExecution::PROPERTY_SUBJECT => $userUri]);
            /** @var  \core_kernel_classes_Class $deliveryExecution */
            foreach ($deliveryExecutions as $deliveryExecution) {
                $deliveryExecution->delete(true);
                $count++;
            }
            $report->setMessage('Removed ' . $count . ' ontology delivery executions of '. $userUri);

        } else {
            $report->setType(\common_report_Report::TYPE_ERROR);
            $report->setMessage('Cannot cleanup delivery executions from ' . get_class($deliveryService));
        }

        return $report;
    }

    private function removeServiceState($userUri){
        $report = new \common_report_Report(\common_report_Report::TYPE_SUCCESS);
        // service states
        $persistence = \common_persistence_KeyValuePersistence::getPersistence(\tao_models_classes_service_StateStorage::PERSISTENCE_ID);
        if ($persistence instanceof \common_persistence_AdvKeyValuePersistence) {
            $count = 0;
            foreach ($persistence->keys('tao:state:'.$userUri.'*') as $key) {
                if (substr($key, 0, 10) == 'tao:state:') {
                    $persistence->del($key);
                    $count++;
                }
            }
            $report->setMessage('Removed ' . $count . ' states '. $userUri);
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

        return $report;
    }
}
