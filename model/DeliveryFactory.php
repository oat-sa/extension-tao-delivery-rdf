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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */
namespace oat\taoDeliveryRdf\model;

use oat\oatbox\service\ConfigurableService;
use core_kernel_classes_Resource;
use core_kernel_classes_Class;
use oat\tao\helpers\form\ValidationRuleRegistry;
use oat\oatbox\event\EventManager;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoDelivery\model\container\delivery\ContainerProvider;

/**
 * Services to manage Deliveries
 *
 * @access public
 * @author Antoine Robin, <antoine@taotesting.com>
 * @package taoDelivery
 */
class DeliveryFactory extends ConfigurableService
{

    const SERVICE_ID = 'taoDeliveryRdf/DeliveryFactory';

    const OPTION_PROPERTIES = 'properties';

    /**
     * Creates a new simple delivery
     *
     * @param core_kernel_classes_Class $deliveryClass
     * @param core_kernel_classes_Resource $test
     * @param string $label
     * @return \common_report_Report
     */
    public function create(core_kernel_classes_Class $deliveryClass, core_kernel_classes_Resource $test, $label) {

        \common_Logger::i('Creating '.$label.' with '.$test->getLabel().' under '.$deliveryClass->getLabel());

        // checking on properties
        foreach ($this->getOption(self::OPTION_PROPERTIES) as $deliveryProperty => $testProperty) {
            $testPropretyInstance = new \core_kernel_classes_Property($testProperty);
            $validationValue = (string) $testPropretyInstance->getOnePropertyValue(new \core_kernel_classes_Property(ValidationRuleRegistry::PROPERTY_VALIDATION_RULE));

            $propertyValues = $test->getPropertyValues($testPropretyInstance);

            if ($validationValue == 'notEmpty' && empty($propertyValues)) {
                $report = \common_report_Report::createFailure(__('Test publishing failed because "%s" is empty.', $testPropretyInstance->getLabel()));
                return $report;
            }
        }

        $storage = new TrackedStorage();

        $testCompilerClass = \taoTests_models_classes_TestsService::singleton()->getCompilerClass($test);
        $compiler = new $testCompilerClass($test, $storage);

        $report = $compiler->compile();
        if ($report->getType() == \common_report_Report::TYPE_SUCCESS) {
            $serviceCall = $report->getData();

            $properties = array(
                RDFS_LABEL => $label,
                PROPERTY_COMPILEDDELIVERY_DIRECTORY => $storage->getSpawnedDirectoryIds(),
                DeliveryAssemblyService::PROPERTY_ORIGIN => $test,
            );

            foreach ($this->getOption(self::OPTION_PROPERTIES) as $deliveryProperty => $testProperty) {
                $properties[$deliveryProperty] = $test->getPropertyValues(new \core_kernel_classes_Property($testProperty));
            }

            $container = null;
            if ($compiler instanceof ContainerProvider) {
                $container = $compiler->getContainer();
            }
            $compilationInstance = $this->createDeliveryResource($deliveryClass, $serviceCall, $container, $properties);
            $report->setData($compilationInstance);
        }

        return $report;
    }

    /**
     * Create a delivery resource based on a successfull compilation
     *
     * @param core_kernel_classes_Class $deliveryClass
     * @param \tao_models_classes_service_ServiceCall $serviceCall
     * @param string $containerId
     * @param string $containerParam
     * @param array $properties
     */
    public function createDeliveryResource(core_kernel_classes_Class $deliveryClass, \tao_models_classes_service_ServiceCall $serviceCall,
        $container, $properties = array()) {

        $properties[PROPERTY_COMPILEDDELIVERY_TIME]      = time();
        $properties[PROPERTY_COMPILEDDELIVERY_RUNTIME]   = $serviceCall->toOntology();
        if (!isset($properties[TAO_DELIVERY_RESULTSERVER_PROP])) {
            $properties[TAO_DELIVERY_RESULTSERVER_PROP] = \taoResultServer_models_classes_ResultServerAuthoringService::singleton()->getDefaultResultServer();
        }
        if (!is_null($container)) {
            $properties[ContainerRuntime::PROPERTY_CONTAINER] = json_encode($container);
        }

        $compilationInstance = $deliveryClass->createInstanceWithProperties($properties);
        $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
        $eventManager->trigger(new DeliveryCreatedEvent($compilationInstance->getUri()));
        return $compilationInstance;
    }
}
