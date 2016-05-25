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
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\service\ServiceManager;
use tao_models_classes_service_ServiceCall;
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

    public function createAssemblyFromServiceCall(core_kernel_classes_Class $deliveryClass, tao_models_classes_service_ServiceCall $serviceCall, $properties = array()) {

        $properties[PROPERTY_COMPILEDDELIVERY_TIME]      = time();
        $properties[PROPERTY_COMPILEDDELIVERY_RUNTIME]   = $serviceCall->toOntology();
        
        if (!isset($properties[TAO_DELIVERY_RESULTSERVER_PROP])) {
            $properties[TAO_DELIVERY_RESULTSERVER_PROP] = \taoResultServer_models_classes_ResultServerAuthoringService::singleton()->getDefaultResultServer();
        }
        
        $compilationInstance = $deliveryClass->createInstanceWithProperties($properties);
        
        return $compilationInstance;
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
            return false;
        }

        if ($this->deleteDeliveryDirectory($assembly)===false) {
            \common_Logger::i('Fail to delete directories assembly, process aborted');
            return false;
        }

        return $assembly->delete();
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
        $this->getServiceManager()->get('taoDelivery/assignment')->onDelete($assembly);
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
        $fileDeleted = 0;
        $directories = $assembly->getPropertyValues(new core_kernel_classes_Property(PROPERTY_COMPILEDDELIVERY_DIRECTORY));

        foreach ($directories as $directory) {
            $instances = $this->getRootClass()->getInstances(true, array(PROPERTY_COMPILEDDELIVERY_DIRECTORY => $directory));
            unset($instances[$assembly->getUri()]);
            if (empty($instances)) {
                $success = $this->getFileStorage()->deleteDirectoryById($directory) ? $success : false;
                $fileDeleted++;
            }
        }
        \common_Logger::i('(' . (int) $fileDeleted. ') Files deleted for delivery assembly: ' . $assembly->getUri());
        return $success;
    }
    
    /**
     * Gets the service call to run this assembly
     *
     * @param core_kernel_classes_Resource $assembly
     * @return tao_models_classes_service_ServiceCall
     */
    public function getRuntime( core_kernel_classes_Resource $assembly) {
        $runtimeResource = $assembly->getUniquePropertyValue(new core_kernel_classes_Property(PROPERTY_COMPILEDDELIVERY_RUNTIME));
        return tao_models_classes_service_ServiceCall::fromResource($runtimeResource);
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