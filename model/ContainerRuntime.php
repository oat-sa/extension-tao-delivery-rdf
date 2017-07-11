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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 * 
 */
namespace oat\taoDeliveryRdf\model;

use oat\taoDelivery\model\container\LegacyRuntime;
use oat\generis\model\OntologyAwareTrait;
use oat\taoDelivery\model\container\delivery\DeliveryServiceContainer;
use oat\taoDelivery\model\container\delivery\DeliveryClientContainer;
use oat\taoDelivery\model\container\delivery\DeliveryContainerRegistry;
/**
 * Service to select the correct container based on delivery
 *
 * @access public
 * @author Joel Bout, <joel@taotesting.com>
 * @package taoDelivery
 */
class ContainerRuntime extends LegacyRuntime
{
    use OntologyAwareTrait;
    
    const PROPERTY_RUNTIME = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#AssembledDeliveryRuntime';
    
    const PROPERTY_CONTAINER = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#AssembledDeliveryContainer';

    public function getDeliveryContainer($deliveryId)
    {
        $delivery = $this->getResource($deliveryId);
        $containerJson = (string)$delivery->getOnePropertyValue($this->getProperty(self::PROPERTY_CONTAINER));
        if (!empty($containerJson)) {
            $registry = DeliveryContainerRegistry::getRegistry();
            $registry->setServiceLocator($this->getServiceLocator());
            $container = $registry->fromJson($containerJson);
            return $container;
        } else {
            return parent::getDeliveryContainer($deliveryId);
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see \oat\taoDelivery\model\RuntimeService::getRuntime()
     */
    public function getRuntime($deliveryId)
    {
        $delivery = $this->getResource($deliveryId);
        if (!$delivery->exists()) {
            throw new \common_exception_NoContent('Unable to load runtime associated for delivery ' . $deliveryId .
                ' Delivery probably deleted.');
        }
        $runtimeResource = $delivery->getUniquePropertyValue($this->getProperty(self::PROPERTY_RUNTIME));
        return \tao_models_classes_service_ServiceCall::fromResource($runtimeResource);
    }
}
