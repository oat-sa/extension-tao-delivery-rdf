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
use oat\taoDelivery\model\RuntimeService;

/**
 * Service to manage the authoring of deliveries
 *
 * @access public
 * @author Joel Bout, <joel@taotesting.com>
 * @package taoDelivery
 */
class DeliveryAssemblyService extends \tao_models_classes_ClassService
{
    const PROPERTY_ORIGIN = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#AssembledDeliveryOrigin';

    /**
     * @var \tao_models_classes_service_FileStorage
     */
    protected $storageService;

    /**
     * (non-PHPdoc)
     * 
     * @see tao_models_classes_ClassService::getRootClass()
     */
    public function getRootClass()
    {
        return new core_kernel_classes_Class(CLASS_COMPILEDDELIVERY);
    }

    /**
     * Return the file storage
     *
     * @return \tao_models_classes_service_FileStorage
     */
    protected function getFileStorage()
    {
        if (!$this->storageService) {
            $this->storageService = $this->getServiceManager()->get(\tao_models_classes_service_FileStorage::SERVICE_ID);
        }
        return $this->storageService;
    }

    /**
     * @deprecated please use DeliveryFactory
     * 
     * @param core_kernel_classes_Class $deliveryClass
     * @param tao_models_classes_service_ServiceCall $serviceCall
     * @param array $properties
     * @return \core_kernel_classes_Resource
     */
    public function createAssemblyFromServiceCall(core_kernel_classes_Class $deliveryClass, tao_models_classes_service_ServiceCall $serviceCall, $properties = array()) {
        throw new \common_exception_Error("Call to deprecated ".__FUNCTION__);
    }
    
    /**
     * Returns all assemblies marked as active
     * 
     * @return array
     */
    public function getAllAssemblies() {
        return $this->getRootClass()->getInstances(true);
    }

    /**
     * Delete delivery by deleting runtime, directories & ontology record
     * @param core_kernel_classes_Resource $assembly
     * @return bool
     */
    public function deleteInstance(core_kernel_classes_Resource $assembly)
    {
        if ($this->deleteDeliveryRuntime($assembly)===false) {
            \common_Logger::i('Fail to delete runtimes assembly, process aborted');
        }

        if ($this->deleteDeliveryDirectory($assembly)===false) {
            \common_Logger::i('Fail to delete directories assembly, process aborted');
        }

        return $assembly->delete();
    }

    public function deleteResource(core_kernel_classes_Resource $resource)
    {
        $result = parent::deleteResource($resource);

        $this->getEventManager()->trigger(new DeliveryRemovedEvent($resource->getUri()));

        return $result;
    }


    /**
     * Delete a runtime of a delivery
     *
     * @param core_kernel_classes_Resource $assembly
     * @return bool
     * @throws \core_kernel_classes_EmptyProperty
     * @throws \core_kernel_classes_MultiplePropertyValuesException
     */
    protected function deleteDeliveryRuntime(core_kernel_classes_Resource $assembly)
    {
        /** @var GroupAssignment $deliveryAssignement */
        $deliveryAssignement = $this->getServiceManager()->get(GroupAssignment::CONFIG_ID);
        $deliveryAssignement->onDelete($assembly);
        /** @var core_kernel_classes_Resource $runtimeResource */
        $runtimeResource = $assembly->getUniquePropertyValue(new core_kernel_classes_Property(PROPERTY_COMPILEDDELIVERY_RUNTIME));
        return $runtimeResource->delete();
    }

    /**
     * Delete directories related to a delivery, don't remove if dir is used by another delivery
     *
     * @param core_kernel_classes_Resource $assembly
     * @return bool
     */
    public function deleteDeliveryDirectory(core_kernel_classes_Resource $assembly)
    {
        $success = true;
        $deleted = 0;
        $directories = $assembly->getPropertyValues(new core_kernel_classes_Property(PROPERTY_COMPILEDDELIVERY_DIRECTORY));

        foreach ($directories as $directory) {
            $instances = $this->getRootClass()->getInstances(true, array(PROPERTY_COMPILEDDELIVERY_DIRECTORY => $directory));
            unset($instances[$assembly->getUri()]);
            if (empty($instances)) {
                $success = $this->getFileStorage()->deleteDirectoryById($directory) ? $success : false;
                $deleted++;
            }
        }
        \common_Logger::i('(' . (int) $deleted. ') deletions for delivery assembly: ' . $assembly->getUri());
        return $success;
    }
    
    /**
     * Gets the service call to run this assembly
     *
     * @param core_kernel_classes_Resource $assembly
     * @return tao_models_classes_service_ServiceCall
     */
    public function getRuntime( core_kernel_classes_Resource $assembly) {
        return $this->getServiceLocator()->get(RuntimeService::SERVICE_ID)->getRuntime($assembly->getUri());
    }
    
    /**
     * Returns the date of the compilation of an assembly as a timestamp
     *
     * @param core_kernel_classes_Resource $assembly
     * @return string
     */
    public function getCompilationDate( core_kernel_classes_Resource $assembly) {
        return (string)$assembly->getUniquePropertyValue(new core_kernel_classes_Property(PROPERTY_COMPILEDDELIVERY_TIME));
    }
    
    public function getOrigin( core_kernel_classes_Resource $assembly) {
        return (string)$assembly->getUniquePropertyValue(new core_kernel_classes_Property(self::PROPERTY_ORIGIN));
    }

}