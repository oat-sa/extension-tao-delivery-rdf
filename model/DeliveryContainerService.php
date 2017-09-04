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
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\ServiceManager;
use oat\taoDelivery\model\AssignmentService;
use oat\taoDelivery\model\DeliveryContainerService as DeliveryContainerServiceInterface;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoTests\models\runner\plugins\TestPlugin;
use oat\taoTests\models\runner\plugins\TestPluginService;
use oat\taoTests\models\runner\features\TestRunnerFeatureService;

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
     * Get the list of active plugins for the current execution
     * @param DeliveryExecution $deliveryExecution
     * @return TestPlugin[] the list of plugins
     */
    public function getPlugins(DeliveryExecution $deliveryExecution)
    {
        $delivery = $deliveryExecution->getDelivery();

        $serviceManager = $this->getServiceManager();

        $pluginService = $serviceManager->get(TestPluginService::SERVICE_ID);
        $testRunnerFeatureService = $serviceManager->get(TestRunnerFeatureService::SERVICE_ID);

        $allPlugins = $pluginService->getAllPlugins();

        $allTestRunnerFeatures = $testRunnerFeatureService->getAll();
        $activeTestRunnerFeaturesIds = explode(
            ',',
            $delivery->getOnePropertyValue(new core_kernel_classes_Property(self::TEST_RUNNER_FEATURES_PROPERTY))
        );

        // If test runner features are defined, we check if we need to disable some plugins accordingly
        if (count($allTestRunnerFeatures) > 0) {
            $pluginsToDisable = [];
            foreach ($allTestRunnerFeatures as $feature) {
                if (!in_array($feature->getId(), $activeTestRunnerFeaturesIds)) {
                    $pluginsToDisable = array_merge($pluginsToDisable, $feature->getPluginsIds());
                }
            }

            foreach ($allPlugins as $plugin) {
                if (!is_null($plugin) && in_array($plugin->getId(), $pluginsToDisable)) {
                    $plugin->setActive(false);
                }
            }
        }

        // return the list of active plugins
        return array_filter($allPlugins, function ($plugin) {
            return !is_null($plugin) && $plugin->isActive();
        });
    }

    /**
     * Get the container bootstrap
     * @param DeliveryExecution $deliveryExecution
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
     * @param DeliveryExecution $deliveryExecution
     * @return string the testDefinition
     */
    public function getTestDefinition(DeliveryExecution $deliveryExecution)
    {
        //FIXME this shouldn't be a service call anymore, a delivery property instead
        $delivery = $deliveryExecution->getDelivery();
        $runtime = ServiceManager::getServiceManager()->get(AssignmentService::SERVICE_ID)->getRuntime($delivery);
        $inputParameters = \tao_models_classes_service_ServiceCallHelper::getInputValues($runtime, array());

        return $inputParameters['QtiTestDefinition'];
    }

    /**
     * Get the container test compilation
     * @param DeliveryExecution $deliveryExecution
     * @return string the  testCompilation
     */
    public function getTestCompilation(DeliveryExecution $deliveryExecution)
    {

        //FIXME this shouldn't be a service call anymore, a delivery property instead
        $delivery = $deliveryExecution->getDelivery();
        $runtime = ServiceManager::getServiceManager()->get(AssignmentService::SERVICE_ID)->getRuntime($delivery);
        $inputParameters = \tao_models_classes_service_ServiceCallHelper::getInputValues($runtime, array());

        return $inputParameters['QtiTestCompilation'];
    }
}
