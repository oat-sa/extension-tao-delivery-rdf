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
 * Copyright (c) 2013-2014 (original work) Open Assessment Technologies SA
 * 
 */

/**
 * Crud services implements basic CRUD services, orginally intended for 
 * REST controllers/ HTTP exception handlers . 
 * 
 * Consequently the signatures and behaviors is closer to REST and throwing HTTP like exceptions
 * 
 * @author Absar Gilani, absar.gilani@gmail.com
 *   
 */
class taoDelivery_models_classes_CrudDeliveryService
    extends tao_models_classes_CrudService
{

    /** (non-PHPdoc)
    * @see tao_models_classes_CrudSservice::getClassService()
    */
	protected function getClassService() {
		return taoDelivery_models_classes_DeliveryAssemblyService::singleton();
	}

    /**
     * (non-PHPdoc)
     * @see tao_models_classes_CrudService::delete()
     */    
        public function delete($uri)
        {
        $success = $this->getClassService()-> deleteInstance(new core_kernel_classes_Resource($uri));
        return $success;
        }
        
     /**
     * 
     * @author Absar Gilani, absar.gilani6@gmail.com
     * @param array $propertiesValues
     * @return core_kernel_classes_Resource
     */    
        public function createDelivery(array $propertiesValues){
            try {
                $test = new core_kernel_classes_Resource($propertiesValues['qtiTest']);
                $label = __("Delivery of %s", $test->getLabel());
                $deliveryClass = new core_kernel_classes_Class(CLASS_COMPILEDDELIVERY);
                $report = taoDelivery_models_classes_SimpleDeliveryFactory::create($deliveryClass, $test, $label);
                return $report;                         
            } 
            catch (common_exception_Exception $e) {
                return new common_report_Report(common_report_Report::TYPE_ERROR, __("Error Occured"));          
            }
          
        }
    
}


