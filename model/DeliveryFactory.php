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

use common_report_Report as Report;
use core_kernel_classes_ResourceFactory as ResourceFactory;
use DomainException;
use oat\generis\model\data\event\ResourceCreated;
use oat\generis\model\OntologyAwareTrait;
use oat\generis\model\OntologyRdf;
use oat\generis\model\OntologyRdfs;
use oat\oatbox\service\ConfigurableService;
use core_kernel_classes_Resource as KernelResource;
use core_kernel_classes_Class as KernelClass;
use oat\tao\helpers\form\ValidationRuleRegistry;
use oat\oatbox\event\EventManager;
use oat\taoDelivery\model\container\delivery\AbstractContainer;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoDelivery\model\container\delivery\ContainerProvider;
use RuntimeException;

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

    public const SERVICE_ID = 'taoDeliveryRdf/DeliveryFactory';

    public const OPTION_PROPERTIES = 'properties';

    /**
     * 'initialProperties' => array(
     *      'uri_of_property'
     * )
     */
    public const OPTION_INITIAL_PROPERTIES = 'initialProperties';

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
    public const OPTION_INITIAL_PROPERTIES_MAP = 'initialPropertiesMap';
    public const OPTION_INITIAL_PROPERTIES_MAP_VALUES = 'values';
    public const OPTION_INITIAL_PROPERTIES_MAP_URI = 'uri';
    public const OPTION_NAMESPACE = 'namespace';

    public const PROPERTY_DELIVERY_COMPILE_TASK = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryCompileTask';

    private $deliveryResource;

    public function create(
        KernelClass $deliveryClass,
        KernelResource $test,
        string $label = '',
        KernelResource $deliveryResource = null,
        array $additionalParameters = []
    ): Report {

        \common_Logger::i('Creating ' . $label . ' with ' . $test->getLabel() . ' under ' . $deliveryClass->getLabel());

        // checking on properties
        foreach ($this->getOption(self::OPTION_PROPERTIES) as $deliveryProperty => $testProperty) {
            $testPropretyInstance = new \core_kernel_classes_Property($testProperty);
            $validationValue = (string) $testPropretyInstance->getOnePropertyValue(new \core_kernel_classes_Property(ValidationRuleRegistry::PROPERTY_VALIDATION_RULE));

            $propertyValues = $test->getPropertyValues($testPropretyInstance);

            if ($validationValue === 'notEmpty' && empty($propertyValues)) {
                $report = Report::createFailure(__('Test publishing failed because "%s" is empty.', $testPropretyInstance->getLabel()));

                return $report;
            }
        }

        if (!$deliveryResource instanceof KernelResource) {
            $deliveryResource = $this->hasNamespace() && $additionalParameters
                ? $this->createNamespacedDeliveryResource(
                    $deliveryClass,
                    $additionalParameters
                )
                : ResourceFactory::create($deliveryClass);
        }

        $this->deliveryResource = $deliveryResource;

        $storage = new TrackedStorage();
        $this->propagate($storage);
        $compiler = $this->getServiceLocator()->get(\taoTests_models_classes_TestsService::class)->getCompiler($test, $storage);

        $report = $compiler->compile();
        if ($report->getType() == Report::TYPE_SUCCESS) {
            $serviceCall = $report->getData();

            $properties = [
                OntologyRdfs::RDFS_LABEL => $label,
                DeliveryAssemblyService::PROPERTY_DELIVERY_DIRECTORY => $storage->getSpawnedDirectoryIds(),
                DeliveryAssemblyService::PROPERTY_ORIGIN => $test,
            ];

            foreach ($this->getOption(self::OPTION_PROPERTIES) as $deliveryProperty => $testProperty) {
                $properties[$deliveryProperty] = $test->getPropertyValues(new \core_kernel_classes_Property($testProperty));
            }

            $container = null;
            if ($compiler instanceof ContainerProvider) {
                $container = $compiler->getContainer();
            }
            $compilationInstance = $this->createDeliveryResource($deliveryClass, $serviceCall, $container, $properties);

            $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
            $eventManager->trigger(new DeliveryCreatedEvent($compilationInstance));

            $report->setData($compilationInstance);
        }

        return $report;
    }

    /**
     * @param $values
     * @param KernelResource $delivery
     *
     * @return KernelResource
     */
    public function setInitialProperties($values, KernelResource $delivery)
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
     *
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
                if (isset($values[$value])) {
                    $initialProperties[$config[self::OPTION_INITIAL_PROPERTIES_MAP_URI]] = $values[$value];
                }
            }
        }
        return $initialProperties;
    }

    /**
     * @param array $properties
     *
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

    protected function createDeliveryResource(
        KernelClass $deliveryClass,
        \tao_models_classes_service_ServiceCall $serviceCall,
        AbstractContainer $container = null,
        array $properties = []
    ): KernelResource {
        $properties[DeliveryAssemblyService::PROPERTY_DELIVERY_TIME]    = time();
        $properties[DeliveryAssemblyService::PROPERTY_DELIVERY_RUNTIME] = json_encode($serviceCall);
        if (!is_null($container)) {
            $properties[ContainerRuntime::PROPERTY_CONTAINER] = json_encode($container);
        }

        if ($this->deliveryResource instanceof KernelResource) {
            $compilationInstance = $this->deliveryResource;
            $compilationInstance->setPropertiesValues($properties);
        } else {
            $compilationInstance = $deliveryClass->createInstanceWithProperties($properties);
        }

        return $compilationInstance;
    }

    private function createNamespacedDeliveryResource(
        KernelClass $deliveryClass,
        array $additionalParameters
    ): KernelResource {
        $deliveryId = trim($additionalParameters[DeliveryAssemblyService::PROPERTY_ASSESSMENT_PROJECT_ID] ?? '');

        if (!$deliveryId) {
            throw new RuntimeException(
                sprintf('%s must not be empty.', DeliveryAssemblyService::PROPERTY_ASSESSMENT_PROJECT_ID)
            );
        }

        $delivery = $deliveryClass->getResource("{$this->getNamespace()}#$deliveryId");

        $delivery->setPropertiesValues([OntologyRdf::RDF_TYPE => $deliveryClass]);

        $this->getEventManager()->trigger(new ResourceCreated($delivery));

        return $delivery;
    }

    private function hasNamespace(): bool
    {
        return $this->hasOption(static::OPTION_NAMESPACE);
    }

    private function getNamespace(): string
    {
        $namespace = rtrim($this->getOption(static::OPTION_NAMESPACE, ''), '#');

        if ($namespace === LOCAL_NAMESPACE) {
            throw new DomainException(
                "Overridden namespace value must be different from a local one, $namespace given"
            );
        }

        return $namespace;
    }

    private function getEventManager(): EventManager
    {
        return $this->getServiceLocator()->get(EventManager::class);
    }
}
