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

use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\oatbox\task\AbstractTaskAction;
use oat\oatbox\service\ServiceManager;
use oat\oatbox\task\Queue;
use oat\oatbox\task\Task;
use oat\generis\model\OntologyAwareTrait;
use oat\tao\model\import\ImportersService;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
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
    const OPTION_CUSTOM = 'custom';
    const OPTION_DELIVERY_LABEL= 'delivery-class-label';

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
        $parent = $this->checkSubClasses($params[self::OPTION_DELIVERY_LABEL]);
        $deliveryFactory = $this->getServiceManager()->get(DeliveryFactory::SERVICE_ID);
        $compilationReport = $deliveryFactory->create($parent, $test, $label);

        if ($compilationReport->getType() == \common_report_Report::TYPE_ERROR) {
            \common_Logger::i('Unable to generate delivery execution ' .
                'into taoDeliveryRdf::RestDelivery for test uri ' . $test->getUri());
        }
        /** @var \core_kernel_classes_Resource $delivery */
        $delivery = $compilationReport->getData();
        $customParams = $params[self::OPTION_CUSTOM];
        if (($delivery instanceof \core_kernel_classes_Resource) && $customParams) {
            $delivery->setPropertiesValues($customParams);
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
     * @param string $classLabel
     * @return \core_kernel_classes_Class
     */
    protected function checkSubClasses($classLabel = '')
    {
        $parent = new \core_kernel_classes_Class(DeliveryAssemblyService::CLASS_URI);
        if ($classLabel) {
            $deliveryClasses = $parent->getSubClasses(true);
            $classes = [$parent->getUri()];
            foreach ($deliveryClasses as $class) {
                $classes[] = $class->getUri();
            }

            /** @var ComplexSearchService $search */
            $search = $this->getServiceManager()->get(ComplexSearchService::SERVICE_ID);
            $queryBuilder = $search->query();
            $criteria = $queryBuilder->newQuery()
                ->add(RDFS_LABEL)->equals($classLabel)
                ->add(RDFS_SUBCLASSOF)->in($classes)
            ;
            $queryBuilder->setCriteria($criteria);
            $result = $search->getGateway()->search($queryBuilder);

            switch ($result->count()) {
                case 0:
                    $deliveryClass = new \core_kernel_classes_Class(DeliveryAssemblyService::CLASS_URI);
                    $parent = $deliveryClass->createSubClass($classLabel);
                    break;
                default:
                    $parent = new \core_kernel_classes_Class($result->current()->getUri());
                    break;
            }
        }
        return $parent;
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
     * @param array $customParams
     * @param string $deliveryClassLabel
     * @return Task created task id
     */
    public static function createTask($importerId, $file, $customParams = [], $deliveryClassLabel = '')
    {
        $serviceManager = ServiceManager::getServiceManager();
        $action = new self();
        $action->setServiceLocator($serviceManager);

        $importersService = $serviceManager->get(ImportersService::SERVICE_ID);
        $importersService->getImporter($importerId);

        $fileUri = $action->saveFile($file['tmp_name'], $file['name']);
        $queue = ServiceManager::getServiceManager()->get(Queue::SERVICE_ID);
        return $queue->createTask($action, [
            self::OPTION_FILE => $fileUri,
            self::OPTION_IMPORTER => $importerId,
            self::OPTION_CUSTOM => $customParams,
            self::OPTION_DELIVERY_LABEL => $deliveryClassLabel
        ]);
    }


}