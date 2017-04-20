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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */
namespace oat\taoDeliveryRdf\model;

use core_kernel_classes_Class;
use core_kernel_classes_Resource;
use \core_kernel_classes_Property;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoDeliveryRdf\model\event\DeliveryRemovedEvent;
use tao_models_classes_service_ServiceCall;
use oat\taoDelivery\model\container\DeliveryContainer;
use function GuzzleHttp\json_encode;
use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\RuntimeService;
use oat\generis\model\OntologyAwareTrait;
use oat\taoDelivery\model\container\ContainerFactory;
use oat\taoDelivery\model\container\DeliveryServiceContainer;
use oat\taoDelivery\model\container\ContainerService;
use oat\oatbox\event\EventManager;

/**
 * Service to manage the authoring of deliveries
 *
 * @access public
 * @author Joel Bout, <joel@taotesting.com>
 * @package taoDelivery
 */
class RuntimeAssemblyService extends ConfigurableService implements RuntimeService
{
    use OntologyAwareTrait;

    const PROPERTY_CONTAINER = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#AssembledDeliveryContainer';

    public function getDeliveryContainer($deliveryId)
    {
        $delivery = $this->getResource($deliveryId);
        $containerSerial = $delivery->getOnePropertyValue($this->getProperty(self::PROPERTY_CONTAINER));
        $containerData = \json_decode((string)$containerSerial);
        if (JSON_ERROR_NONE == json_last_error()) {
            $containerService = $this->getServiceManager()->get(ContainerService::SERVICE_ID);
            $container = $containerService->getContainer($containerData->container);
            $container->setRuntimeParams($containerData->params);
        } else {
            // fallback for backwards compatibility
            $container = $this->getServiceManager()->get(ContainerService::SERVICE_ID)->getContainer(DeliveryServiceContainer::CONTAINER_ID);
            $container->setRuntimeParams($this->getRuntime($deliveryId)->serializeToString());
        }
        return $container;
    }

    public function createAssemblyFromContainer(core_kernel_classes_Class $deliveryClass, DeliveryContainer $container, $properties = array()) {

        $properties[PROPERTY_COMPILEDDELIVERY_TIME] = time();
        $properties[self::PROPERTY_CONTAINER] = json_encode([
            'container' => $container->getId(),
            'params' => $container->getRuntimeParams()
        ]);
        if (!isset($properties[TAO_DELIVERY_RESULTSERVER_PROP])) {
            $properties[TAO_DELIVERY_RESULTSERVER_PROP] = \taoResultServer_models_classes_ResultServerAuthoringService::singleton()->getDefaultResultServer();
        }

        $delivery = $deliveryClass->createInstanceWithProperties($properties);
        $this->getServiceManager()->get(EventManager::CONFIG_ID)->trigger(new DeliveryCreatedEvent($delivery->getUri()));
        return $delivery;
    }

    /**
     * (non-PHPdoc)
     * @see \oat\taoDelivery\model\RuntimeService::getRuntime()
     */
    public function getRuntime($deliveryId)
    {
       $delivery = $this->getResource($deliveryId);
       $runtimeResource = $delivery->getUniquePropertyValue($this->getProperty(PROPERTY_COMPILEDDELIVERY_RUNTIME));
       return tao_models_classes_service_ServiceCall::fromResource($runtimeResource);
    }

}