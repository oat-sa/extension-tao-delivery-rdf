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

use common_ext_ExtensionsManager as ExtensionsManager;
use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\ServiceManager;
use oat\taoDelivery\model\AssignmentService;
use oat\taoDelivery\model\DeliveryContainerService as DeliveryContainerServiceInterface;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoTests\models\runner\plugins\TestPluginService;
use oat\taoTests\models\runner\plugins\TestRunnerFeatureService;

/**
 * RDF implementation for the Delivery container service.
 * It means the container data are retrieved into the ontology.
 *
 * TODO The actual implementation still uses serviceCall for the test definition and the test compilation
 * and the config for the bootstrap. All those infos should be added during the assemble phase.
 *
 * @author Bertrand Chevier <bertrand@taotesting.com>
 */
class DeliveryContainerService  extends ConfigurableService implements DeliveryContainerServiceInterface
{

    const TEST_RUNNER_FEATURES_PROPERTY = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryTestRunnerFeatures';

    /**
     * Get the list of plugins for the current execution
     * @param DeliveryExecution $execution
     * @return array the list of plugins
     */
    public function getPlugins(DeliveryExecution $deliveryExecution)
    {
        $delivery = $deliveryExecution->getDelivery();

        $serviceManager = $this->getServiceManager();
        $pluginService = $serviceManager->get(TestPluginService::CONFIG_ID);
        $testRunnerFeatureService = $serviceManager->get(TestRunnerFeatureService::SERVICE_ID);

        $defaultActivePlugins = array_filter($pluginService->getAllPlugins(), function($plugin){
            return !is_null($plugin) && $plugin->isActive();
        });

        $testRunnerFeaturesData = $delivery->getOnePropertyValue(new core_kernel_classes_Property(self::TEST_RUNNER_FEATURES_PROPERTY));
        $allTestRunnerFeatures = $testRunnerFeatureService->getAll();

        // No test runner features are defined, we just return the default active plugins
        if (count($allTestRunnerFeatures) == 0) {
            return $defaultActivePlugins;
        }

        // filter active plugin according to test runner features status
        // todo: write a unit test for this
        $activeTestRunnerFeaturesIds = explode(',', $testRunnerFeaturesData);

        $pluginsToDisable = [];
        foreach($allTestRunnerFeatures as $feature) {
            if (!in_array($feature->getId(), $activeTestRunnerFeaturesIds)) {
                $pluginsToDisable = array_merge($pluginsToDisable, $feature->getPluginsIds());
            }
        }

        $filteredPlugins = [];
        foreach($defaultActivePlugins as $plugin) {
            if (! in_array($plugin->getId(), $pluginsToDisable)) {
                $filteredPlugins[$plugin->getModule()] = $plugin;
            }
        }

        return $filteredPlugins;
        /*

        if(is_null($pluginPropData) || empty($pluginPropData)) {
            //fallback to the default values
            return array_filter($pluginService->getAllPlugins(), function($plugin){
                return !is_null($plugin) && $plugin->isActive();
            });
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
        */
    }

    /**
     * Get the container bootstrap
     * @param DeliveryExecution $execution
     * @return string the bootstrap
     */
    public function getBootstrap(DeliveryExecution $deliveryExecution)
    {
        //FIXME this config is misplaced, this should be a delivery property
        $config = ExtensionsManager::singleton()->getExtensionById('taoQtiTest')->getConfig('testRunner');
        return $config['bootstrap'];
    }

    /**
     * Get the container testDefinition
     * @param DeliveryExecution $execution
     * @return string the testDefinition
     */
    public function getTestDefinition(DeliveryExecution $deliveryExecution)
    {
        //FIXME this shouldn't be a service call anymore, a delivery property instead
        $delivery = $deliveryExecution->getDelivery();
        $runtime = ServiceManager::getServiceManager()->get(AssignmentService::CONFIG_ID)->getRuntime($delivery);
        $inputParameters = \tao_models_classes_service_ServiceCallHelper::getInputValues($runtime, array());

        return $inputParameters['QtiTestDefinition'];
    }

    /**
     * Get the container test compilation
     * @param DeliveryExecution $execution
     * @return string the  testCompilation
     */
    public function getTestCompilation(DeliveryExecution $deliveryExecution)
    {

        //FIXME this shouldn't be a service call anymore, a delivery property instead
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
