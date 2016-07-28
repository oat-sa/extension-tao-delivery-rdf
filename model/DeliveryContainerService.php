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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 */

namespace oat\taoDeliveryRdf\model;

use common_ext_ExtensionManager as ExtensionsManager;
use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\ServiceManager;
use oat\taoDelivery\model\AssignmentService;
use oat\taoDelivery\model\DeliveryContainerService as DeliveryContainerServiceInterface;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoTests\model\runner\plugins\TestPluginService;

/**
 *
 */
class DeliveryContainerService  extends ConfigurableService implements DeliveryContainerServiceInterface
{

    const DELIVERY_PLUGINS_PROPERTY = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryPlugins';


    public function getPlugins(DeliveryExecution $deliveryExecution)
    {
        $plugins = [];

        $pluginServie = $this->getServiceManager()->get(TestPluginService::CONFIG_ID);

        $delivery = $deliveryExecution->getDelivery();

        $pluginPropData = $delivery->getOnePropertyValue(new core_kernel_classes_Property(self::DELIVERY_PLUGINS_PROPERTY));

        if(is_null($pluginPropData) || empty($pluginPropData)) {
            //fallback to the default values
            return $this->pluginService->getAllPlugins();
        }

        //otherwise decode the data from [ pluginId => active] to TestPlugins
        $pluginList = json_decode($pluginPropData, true);
        if(is_array($pluginList)){
            foreach($pluginList as $id => $active){
                $plugin = $this->pluginService->getPlugin($id);
                if(!is_null($plugin)){
                    $plugin->setActive((boolean) $active);
                    $plugins[] = $plugin;
                }
            }
        }
        return $plugins;
    }

    public function getBootstrap(DeliveryExecution $deliveryExecution)
    {
        //FIXME this config is misplaced.
        $config = ExtensionsManager::singleton()->getExtensionById('taoQtiTest')->getConfig('testRunner');
        return $config['bootstrap'];
    }


    public function getTestDefinition(DeliveryExecution $execution)
    {
        //FIXME this shouldn't be a service call anymore
        $delivery = $deliveryExecution->getDelivery();
        $runtime = ServiceManager::getServiceManager()->get(AssignmentService::CONFIG_ID)->getRuntime($delivery);
        $inputParameters = \tao_models_classes_service_ServiceCallHelper::getInputValues($runtime, array());

        return $inputParameters['QtiTestDefinition'];
    }

    public function getTestCompilation(DeliveryExecution $execution)
    {

        //FIXME this shouldn't be a service call anymore
        $delivery = $deliveryExecution->getDelivery();
        $runtime = ServiceManager::getServiceManager()->get(AssignmentService::CONFIG_ID)->getRuntime($delivery);
        $inputParameters = \tao_models_classes_service_ServiceCallHelper::getInputValues($runtime, array());

        return $inputParameters['QtiTestCompilation'];
    }

    public function setTestPlugins(core_kernel_classes_Resource $delivery, $plugins = [])
    {
        $pluginList = [];
        foreach($plugins as $plugin){
            if($plugin instanceof TestPlugin){
                $pluginList[$plugin->getId()] = $plugin->isActive();
            }
        }
        $delivery->editPropertyValue(new core_kernel_classes_Property(self::DELIVERY_PLUGINS_PROPERTY), json_encode($pluginList));
    }
}
