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

use common_exception_Error;
use common_ext_ExtensionsManager as ExtensionsManager;
use core_kernel_classes_Resource;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\ServiceManager;
use oat\taoDelivery\model\AssignmentService;
use oat\taoDelivery\model\DeliveryContainerService as DeliveryContainerServiceInterface;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoTests\models\runner\plugins\TestPlugin;
use oat\taoTests\models\runner\plugins\TestPluginService;
use oat\taoTests\models\runner\features\TestRunnerFeatureService;
use oat\taoTests\models\runner\providers\TestProviderService;
use oat\generis\model\OntologyAwareTrait;

/**
 * RDF implementation for the Delivery container service.
 * It means the container data are retrieved into the ontology.
 *
 * TODO The actual implementation still uses serviceCall for the test definition and the test compilation
 * and the config for the bootstrap. All those infos should be added during the assemble phase.
 *
 * @author Bertrand Chevier <bertrand@taotesting.com>
 */
class DeliveryContainerService extends ConfigurableService implements DeliveryContainerServiceInterface
{
    use OntologyAwareTrait;

    const PROPERTY_EXCLUDED_SUBJECTS = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#ExcludedSubjects';
    const PROPERTY_MAX_EXEC = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#Maxexec';
    const PROPERTY_ACCESS_SETTINGS = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#AccessSettings';
    const TEST_RUNNER_FEATURES_PROPERTY = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryTestRunnerFeatures';

    /** @deprecated use DeliveryAssemblyService::PROPERTY_START  */
    const PROPERTY_START = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#PeriodStart';
    /** @deprecated use DeliveryAssemblyService::PROPERTY_END  */
    const PROPERTY_END = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#PeriodEnd';

    /**
     * @var TestPlugin[]
     */
    private $deliveryPlugins = [];

    /**
     * Get the list of providers for the current execution
     * @param DeliveryExecution $execution
     * @return array the list of providers
     */
    public function getProviders(DeliveryExecution $execution)
    {
        $serviceManager = $this->getServiceManager();
        $providerService = $serviceManager->get(TestProviderService::SERVICE_ID);
        $activeProviders = array_filter(
            $providerService->getAllProviders(),
            function ($provider) {
                return !is_null($provider) && $provider->isActive();
            }
        );
        $providers = [];
        foreach ($activeProviders as $provider) {
            $category = $provider->getCategory();
            if (!isset($providers[$category])) {
                $providers[$category] = [];
            }
            $providers[$category][] = $provider;
        }
        $providers['plugins'] = array_values($this->getPlugins($execution));
        return $providers;
    }

    /**
     * Get the list of active plugins for the current execution
     * @param DeliveryExecution $deliveryExecution
     * @return TestPlugin[] the list of plugins
     *
     * @throws \common_exception_NotFound
     */
    public function getPlugins(DeliveryExecution $deliveryExecution)
    {
        $delivery = $deliveryExecution->getDelivery();
        $deliveryUri = $delivery->getUri();
        if (!isset($this->deliveryPlugins[$deliveryUri])) {
            $this->deliveryPlugins[$deliveryUri] = [];
            $pluginService = $this->getServiceLocator()->get(TestPluginService::SERVICE_ID);
            $allPlugins = $pluginService->getAllPlugins();

            $pluginsToDisable = $this->getPluginsDisabledForDelivery($delivery);
            foreach ($allPlugins as $key => $plugin) {
                if (empty($plugin) || !$plugin instanceof TestPlugin) {
                    continue;
                }

                if ($plugin->isActive() && !in_array($plugin->getId(), $pluginsToDisable, true)) {
                    $this->deliveryPlugins[$deliveryUri][$key] = $plugin;
                }
            }
        }

        return $this->deliveryPlugins[$deliveryUri];
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
        $inputParameters = \tao_models_classes_service_ServiceCallHelper::getInputValues($runtime, []);

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
        $inputParameters = \tao_models_classes_service_ServiceCallHelper::getInputValues($runtime, []);

        return $inputParameters['QtiTestCompilation'];
    }

    /**
     * @param \core_kernel_classes_Resource $delivery
     * @return array of feature ids
     * @throws \core_kernel_persistence_Exception
     */
    protected function getActiveFeatures(\core_kernel_classes_Resource $delivery)
    {
        return explode(
            ',',
            $delivery->getOnePropertyValue($this->getProperty(self::TEST_RUNNER_FEATURES_PROPERTY))
        );
    }

    /**
     * @param core_kernel_classes_Resource $delivery
     * @return array
     */
    protected function getPluginsDisabledForDelivery(core_kernel_classes_Resource $delivery)
    {
        $disabledDeliveryPlugins = [];
        try {
            $allTestRunnerFeatures = $this->getAllAvailableFeatures();

            if (empty($allTestRunnerFeatures)) {
                return $disabledDeliveryPlugins;
            }

            $enabledFeaturesPlugins = [[]];
            $disabledFeaturesPlugins = [[]];
            $activeTestRunnerFeaturesIds = $this->getActiveFeatures($delivery);
            foreach ($allTestRunnerFeatures as $feature) {
                if (in_array($feature->getId(), $activeTestRunnerFeaturesIds, true)) {
                    $enabledFeaturesPlugins[] = $feature->getPluginsIds();
                } else {
                    $disabledFeaturesPlugins[] = $feature->getPluginsIds();
                }
            }

            $enabledPlugins = array_unique(array_merge(...$enabledFeaturesPlugins));
            $disabledPlugins = array_unique(array_merge(...$disabledFeaturesPlugins));

            // We disable only plugins which are not enabled via any other active test runner feature.
            $disabledDeliveryPlugins = array_diff($disabledPlugins, $enabledPlugins);
        } catch (common_exception_Error $e) {
            $this->logWarning(
                'Error getting plugins disabled for delivery.',
                ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]
            );
        }

        return $disabledDeliveryPlugins;
    }

    /**
     * @return array
     */
    protected function getAllAvailableFeatures()
    {
        $testRunnerFeatureService = $this->getServiceLocator()->get(TestRunnerFeatureService::SERVICE_ID);
        $allTestRunnerFeatures = $testRunnerFeatureService->getAll();

        return $allTestRunnerFeatures;
    }
}
