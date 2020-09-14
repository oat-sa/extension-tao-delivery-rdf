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
 * Copyright (c) 2017-2020 (original work) Open Assessment Technologies SA;
 */

namespace oat\taoDeliveryRdf\model\tasks;

use Exception;
use JsonSerializable;
use common_Logger as Logger;
use common_report_Report as Report;
use common_exception_Error as Error;
use oat\oatbox\service\ServiceManager;
use oat\oatbox\task\AbstractTaskAction;
use common_Exception as CommonException;
use oat\generis\model\OntologyAwareTrait;
use oat\tao\model\import\ImportersService;
use core_kernel_classes_Class as CoreClass;
use oat\tao\model\taskQueue\QueueDispatcher;
use core_kernel_classes_Resource as Resource;
use oat\taoDeliveryRdf\model\DeliveryFactory;
use oat\taoTests\models\import\AbstractTestImporter;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\tao\model\taskQueue\Task\CallbackTaskInterface;
use oat\oatbox\service\exception\InvalidServiceManagerException;
use common_exception_InconsistentData as InconsistentDataException;
use common_exception_MissingParameter as MissingParameterException;

/**
 * Class ImportAndCompile
 * Action to import test and compile it into delivery
 *
 * @package oat\taoDeliveryRdf\model\tasks
 *
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class ImportAndCompile extends AbstractTaskAction implements JsonSerializable
{
    use OntologyAwareTrait;

    public const FILE_DIR = 'ImportAndCompileTask';
    private const OPTION_FILE = 'file';
    private const OPTION_IMPORTER = 'importer';
    private const OPTION_CUSTOM = 'custom';
    private const OPTION_DELIVERY_LABELS = 'delivery-class-labels';

    /**
     * @param array $params
     *
     * @throws Error
     * @throws InconsistentDataException
     * @throws MissingParameterException
     *
     * @return Report
     */
    public function __invoke($params)
    {
        $this->checkParams($params);

        $file = $this->getFileReferenceSerializer()->unserializeFile($params[self::OPTION_FILE]);
        $report = null;

        try {
            $importer = $this->getImporter($params[self::OPTION_IMPORTER]);

            /** @var Report $report */
            $report = $importer->import($file);

            if ($report->getType() === Report::TYPE_SUCCESS) {
                foreach ($report as $r) {
                    $test = $r->getData()->rdfsResource;
                }
            } else {
                throw new CommonException(
                    $file->getBasename() . ' Unable to import test with message ' . $report->getMessage()
                );
            }

            $label = 'Delivery of ' . $test->getLabel();
            $parent = $this->checkSubClasses($params[self::OPTION_DELIVERY_LABELS]);
            $deliveryFactory = $this->getServiceManager()->get(DeliveryFactory::SERVICE_ID);
            $compilationReport = $deliveryFactory->create($parent, $test, $label);

            if ($compilationReport->getType() == Report::TYPE_ERROR) {
                Logger::i(
                    'Unable to generate delivery execution into taoDeliveryRdf::RestDelivery for test uri '
                    . $test->getUri()
                );
            }
            /** @var Resource $delivery */
            $delivery = $compilationReport->getData();
            $customParams = $params[self::OPTION_CUSTOM];

            if ($delivery instanceof Resource && is_array($customParams)) {
                foreach ($customParams as $rdfKey => $rdfValue) {
                    $property = $this->getProperty($rdfKey);
                    $delivery->editPropertyValues($property, $rdfValue);
                }
            }

            $report->add($compilationReport);
            $report->setData(['delivery-uri' => $delivery->getUri()]);

            return $report;
        } catch (Exception $e) {
            $detailedErrorReport = Report::createFailure($e->getMessage());

            if ($report) {
                $errors = $report->getErrors();

                foreach ($errors as $error) {
                    $detailedErrorReport->add($error->getErrors());
                }
            }

            return $detailedErrorReport;
        }
    }

    /**
     * Create task in queue
     *
     * @param string $importerId test importer identifier
     * @param array $file uploaded file @see \tao_helpers_Http::getUploadedFile()
     * @param array $customParams
     * @param array $deliveryClassLabels
     *
     * @return CallbackTaskInterface
     */
    public static function createTask(
        string $importerId,
        array $file,
        array $customParams = [],
        array $deliveryClassLabels = []
    ): CallbackTaskInterface
    {
        $serviceManager = ServiceManager::getServiceManager();
        $action = new self();
        $action->setServiceLocator($serviceManager);

        $importersService = $serviceManager->get(ImportersService::SERVICE_ID);
        $importersService->getImporter($importerId);

        $fileUri = $action->saveFile($file['tmp_name'], $file['name']);
        /** @var QueueDispatcher $queueDispatcher */
        $queueDispatcher = ServiceManager::getServiceManager()->get(QueueDispatcher::SERVICE_ID);
        $taskParameters = [
            self::OPTION_FILE => $fileUri,
            self::OPTION_IMPORTER => $importerId,
            self::OPTION_CUSTOM => $customParams,
            self::OPTION_DELIVERY_LABELS => $deliveryClassLabels,
        ];
        $taskTitle = __('Import QTI test and create delivery.');;

        return $queueDispatcher->createTask($action, $taskParameters, $taskTitle, null, true);
    }

    /**
     * @return string
     */
    public function jsonSerialize()
    {
        return __CLASS__;
    }

    /**
     * @param array $params
     *
     * @throws InvalidServiceManagerException
     * @throws InconsistentDataException
     * @throws MissingParameterException
     */
    private function checkParams(array $params): void
    {
        foreach ([self::OPTION_FILE, self::OPTION_IMPORTER] as $param) {
            if (!isset($params[$param])) {
                throw new MissingParameterException(sprintf(
                    'Missing parameter `%s` in %s',
                    $param,
                    self::class
                ));
            }
        }

        $importer = $this->getImporter($params[self::OPTION_IMPORTER]);

        if (!$importer instanceof AbstractTestImporter) {
            throw new InconsistentDataException(sprintf(
                'Wrong importer `%s`',
                $params[self::OPTION_IMPORTER]
            ));
        }
    }

    /**
     * @param array $classLabels
     *
     * @return CoreClass
     */
    private function checkSubClasses(array $classLabels = []): CoreClass
    {
        $parent = new CoreClass(DeliveryAssemblyService::CLASS_URI);

        if (empty($classLabels)) {
            return $parent;
        } else {
            $deliveryClasses = $parent->getSubClasses(true);

            foreach (array_values($deliveryClasses) as $index => $deliveryClass) {
                if (isset($classLabels[$index]) && $classLabels[$index] === $deliveryClass->getLabel()) {
                    $class = $deliveryClass;
                } else {
                    break;
                }
            }
        }

        if (!isset($class)) {
            foreach ($classLabels as $classLabel) {
                $parent = $parent->createSubClass($classLabel);
            }

            $class = $parent;
        }

        return $class;
    }

    /**
     * @param string $id
     *
     * @throws InvalidServiceManagerException
     *
     * @return mixed
     */
    private function getImporter(string $id)
    {
        $importersService = $this->getServiceManager()->get(ImportersService::SERVICE_ID);

        return $importersService->getImporter($id);
    }
}
