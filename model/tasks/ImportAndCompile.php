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
 *
 */

namespace oat\taoDeliveryRdf\model\tasks;

use oat\oatbox\task\AbstractTaskAction;
use oat\oatbox\service\ServiceManager;
use oat\oatbox\task\Queue;
use oat\oatbox\task\Task;
use oat\generis\model\OntologyAwareTrait;
use oat\tao\model\import\ImportersService;
use oat\taoTests\models\import\AbstractTestImporter;
use oat\taoDeliveryRdf\model\DeliveryFactory;

/**
 * Class ImportAndCompile
 *
 * Action to import test and compile it into delivery
 *
 * @package oat\taoQtiTest\models\tasks
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class ImportAndCompile extends AbstractTaskAction implements \JsonSerializable
{

    use OntologyAwareTrait;

    const FILE_DIR = 'ImportAndCompileTask';
    const OPTION_FILE = 'file';
    const OPTION_IMPORTER = 'importer';

    /**
     * @param $params
     * @throws \common_exception_MissingParameter
     * @return \common_report_Report
     */
    public function __invoke($params)
    {
        $this->checkParams($params);
        \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDeliveryRdf');
        $file = $this->getFileReferenceSerializer()->unserializeFile($params[self::OPTION_FILE]);

        $importer = $this->getImporter($params[self::OPTION_IMPORTER]);
        $report = $importer->import($file);

        if ($report->getType() === \common_report_Report::TYPE_SUCCESS) {
            foreach ($report as $r) {
                $test = $r->getData()->rdfsResource;
            }
        } else {
            \common_Logger::i('Unable to import test.');
        }

        $label = 'Delivery of ' . $test->getLabel();
        $deliveryClass = new \core_kernel_classes_Class(CLASS_COMPILEDDELIVERY);
        $deliveryFactory = $this->getServiceManager()->get(DeliveryFactory::SERVICE_ID);
        $compilationReport = $deliveryFactory->create($deliveryClass, $test, $label);

        if ($compilationReport->getType() == \common_report_Report::TYPE_ERROR) {
            \common_Logger::i('Unable to generate delivery execution ' .
                'into taoDeliveryRdf::RestDelivery for test uri ' . $test->getUri());
        }
        $report->add($compilationReport);
        return $report;
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
     * @throws \common_exception_MissingParameter
     * @throws \common_exception_InconsistentData
     */
    protected function checkParams(array $params)
    {
        if (!isset($params[self::OPTION_FILE])) {
            throw new \common_exception_MissingParameter('Missing parameter `'.self::OPTION_FILE.'` in ' . self::class);
        }
        if (!isset($params[self::OPTION_IMPORTER])) {
            throw new \common_exception_MissingParameter('Missing parameter `'.self::OPTION_IMPORTER.'` in ' . self::class);
        }

        $importer = $this->getImporter($params[self::OPTION_IMPORTER]);

        if (!$importer instanceof AbstractTestImporter) {
            throw new \common_exception_InconsistentData('Wrong importer `' . $params[self::OPTION_IMPORTER] . '`');
        }
    }

    /**
     * @param string $id
     * @return AbstractTestImporter
     */
    protected function getImporter($id)
    {
        $importersService = $this->getServiceManager()->get(ImportersService::SERVICE_ID);
        return $importersService->getImporter($id);
    }

    /**
     * Create task in queue
     * @param $importerId test importer identifier
     * @param array $file uploaded file @see \tao_helpers_Http::getUploadedFile()
     * @return Task created task id
     */
    public static function createTask($importerId, $file)
    {
        $serviceManager = ServiceManager::getServiceManager();
        $action = new self();
        $action->setServiceLocator($serviceManager);

        $importersService = $serviceManager->get(ImportersService::SERVICE_ID);
        $importersService->getImporter($importerId);

        $fileUri = $action->saveFile($file['tmp_name'], $file['name']);
        $queue = ServiceManager::getServiceManager()->get(Queue::SERVICE_ID);
        return $queue->createTask($action, [self::OPTION_FILE => $fileUri, self::OPTION_IMPORTER => $importerId]);
    }


}