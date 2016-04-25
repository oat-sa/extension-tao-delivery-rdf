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
 */

/**
 *
 * @author Absar Gilani,{absar.gilani6@gmail.com}
 */
use \Exception;

class taoDelivery_actions_RestDelivery extends tao_actions_CommonRestModule
{
        
        const TAO_TEST_ID="qtiTest";
        
	public function __construct(){
		parent::__construct();
		//The service taht implements or inherits get/getAll/getRootClass ... for that particular type of resources
		$this->service = taoDelivery_models_classes_CrudDeliveryService::singleton();
	}

	/**
	 * Optionnaly a specific rest controller may declare
	 * aliases for parameters used for the rest communication
	 */
	protected function getParametersAliases(){
	    return array_merge(parent::getParametersAliases(), array(
                "qtiTest"=> self::TAO_TEST_ID
                    
		   
	    ));
	}
	/**
	 * Optionnal Requirements for parameters to be sent on every service
	 *
	 */
	protected function getParametersRequirements() {
	    return array(
		/** you may use either the alias or the uri, if the parameter identifier
		 *  is set it will become mandatory for the method/operation in $key
		* Default Parameters Requirents are applied
		* type by default is not required and the root class type is applied
		*/
	    );
	}
        /**
        * This code snippet Creates Delivery and LTI Link
        *
        * @author  Absar , absar.gilani6@gmail.com 
        * @return returnSuccess and returnFailure
        */
        protected function post() {       
	    $parameters = $this->getParameters();
            if (isset($parameters[self::TAO_TEST_ID])){
	        $data = $this->service->createDelivery($parameters);                    
            }
            else{
                $data = new common_report_Report(common_report_Report::TYPE_ERROR, __("Incorrect Parameter Selected"));
            }
            if ($data->getType() === common_report_Report::TYPE_ERROR) {
                $e = new common_exception_Error($data->getMessage());
                return $this->returnFailure($e);
            }
            else{
                $values = $data->getData();
                $DeliveryUri=$values->getUri();                               
                $data = array(
                    'DeliveryId' => $DeliveryUri,
                    'LtiLink' => ROOT_URL.'ltiDeliveryProvider/DeliveryTool/launch/'.base64_encode($DeliveryUri));
                return $this->returnSuccess($data);
            }               		
        }
}
