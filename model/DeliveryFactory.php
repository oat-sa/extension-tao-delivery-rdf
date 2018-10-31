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

use oat\generis\model\OntologyAwareTrait;
use oat\generis\model\OntologyRdfs;
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
    use OntologyAwareTrait;

    const SERVICE_ID = 'taoDeliveryRdf/DeliveryFactory';

    const OPTION_PROPERTIES = 'properties';

    /**
     * 'initialProperties' => array(
     *      'uri_of_property'
     * )
     */
    const OPTION_INITIAL_PROPERTIES = 'initialProperties';

    /**
     * initialPropertiesMap' => array(
     *      'name_of_rest_parameter' => array(
     *          'uri' => 'uri_of_property',
     *          'values' => array(
     *              'true' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#ComplyEnabled'
     *              )
     *          )
     *      )
     */
    const OPTION_INITIAL_PROPERTIES_MAP = 'initialPropertiesMap';
    const OPTION_INITIAL_PROPERTIES_MAP_VALUES = 'values';
    const OPTION_INITIAL_PROPERTIES_MAP_URI = 'uri';

    const PROPERTY_DELIVERY_COMPILE_TASK = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryCompileTask';

    private $deliveryResource;

    /**
     * Creates a new simple delivery
     *
     * @param core_kernel_classes_Class $deliveryClass
     * @param core_kernel_classes_Resource $test
     * @param string $label
     * @param core_kernel_classes_Resource $deliveryResource
     * @return \common_report_Report
     */
    public function create(core_kernel_classes_Class $deliveryClass, core_kernel_classes_Resource $test, $label = '', core_kernel_classes_Resource $deliveryResource = null) {

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

        if (!$deliveryResource instanceof core_kernel_classes_Resource) {
            $deliveryResource = \core_kernel_classes_ResourceFactory::create($deliveryClass);
        }

        $this->deliveryResource = $deliveryResource;

        $storage = new TrackedStorage();
        $this->propagate($storage);
        $compiler = \taoTests_models_classes_TestsService::singleton()->getCompiler($test, $storage);

        $report = $compiler->compile();
        if ($report->getType() == \common_report_Report::TYPE_SUCCESS) {
            $serviceCall = $report->getData();

            $properties = array(
                OntologyRdfs::RDFS_LABEL => $label,
                DeliveryAssemblyService::PROPERTY_DELIVERY_DIRECTORY => $storage->getSpawnedDirectoryIds(),
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
     * @param $values
     * @param core_kernel_classes_Resource $delivery
     * @return core_kernel_classes_Resource
     */
    public function setInitialProperties($values, core_kernel_classes_Resource $delivery)
    {
        $initialProperties = $this->getOption(self::OPTION_INITIAL_PROPERTIES);

        foreach ($values as $uri => $value) {
            if (in_array($uri, $initialProperties) && $value) {
                $property = $this->getProperty($uri);
                $value = is_array($value) ? current($value) : $value;
                $delivery->setPropertyValue($property, $value);
            }
        }
        return $delivery;
    }

    /**
     * @param \Request $request
     * @return array
     */
    public function getInitialPropertiesFromRequest(\Request $request)
    {
        $initialPropertiesMap = $this->getOption(self::OPTION_INITIAL_PROPERTIES_MAP);
        $requestParameters = $request->getParameters();
        $initialProperties = [];
        foreach ($requestParameters as $parameter => $value) {
            if (isset($initialPropertiesMap[$parameter]) && $value) {
                $config = $initialPropertiesMap[$parameter];
                $values = $config[self::OPTION_INITIAL_PROPERTIES_MAP_VALUES];
                if(isset($values[$value])) {
                    $initialProperties[$config[self::OPTION_INITIAL_PROPERTIES_MAP_URI]] = $values[$value];
                }
            }
        }
        return $initialProperties;
    }

    /**
     * @param array $properties
     * @return array
     */
    public function getInitialPropertiesFromArray($properties)
    {
        $initialProperties = $this->getOption(self::OPTION_INITIAL_PROPERTIES);
        $initialPropertiesResponse = [];
        foreach ($properties as $uri => $value) {
            if (in_array($uri, $initialProperties) && $value) {
                $initialPropertiesResponse[$uri] = $value;
            }
        }
        return $initialPropertiesResponse;
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
    protected function createDeliveryResource(core_kernel_classes_Class $deliveryClass, \tao_models_classes_service_ServiceCall $serviceCall,
        $container, $properties = array()) {

        $properties[DeliveryAssemblyService::PROPERTY_DELIVERY_TIME]      = time();
        $properties[DeliveryAssemblyService::PROPERTY_DELIVERY_RUNTIME]   = $serviceCall->toOntology();
        if (!isset($properties[DeliveryContainerService::PROPERTY_RESULT_SERVER])) {
            $properties[DeliveryContainerService::PROPERTY_RESULT_SERVER] = \taoResultServer_models_classes_ResultServerAuthoringService::singleton()->getDefaultResultServer();
        }
        if (!is_null($container)) {
            $properties[ContainerRuntime::PROPERTY_CONTAINER] = json_encode($container);
        }

        if ($this->deliveryResource instanceof core_kernel_classes_Resource) {
            $compilationInstance = $this->deliveryResource;
            $compilationInstance->setPropertiesValues($properties);
        } else {
            $compilationInstance = $deliveryClass->createInstanceWithProperties($properties);
        }

        $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
        $eventManager->trigger(new DeliveryCreatedEvent($compilationInstance->getUri()));
        return $compilationInstance;
    }
}
