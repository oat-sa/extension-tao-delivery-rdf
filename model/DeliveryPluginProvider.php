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
 *
 */

namespace oat\taoDeliveryRdf\model;

use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\oatbox\service\ServiceManager;
use oat\taoTests\models\runner\plugins\TestPluginProviderInterface;
use oat\taoTests\models\runner\plugins\TestPluginService;
use oat\taoTests\models\runner\features\TestRunnerFeatureService;

/**
 * Class DeliveryPluginProvider
 * @package oat\taoDeliveryRdf\model
 * @author Aleh Hutnikau, <goodnickoff@gmail.com>
 */
class DeliveryPluginProvider implements TestPluginProviderInterface
{

    /**
     * @var DeliveryExecution
     */
    protected $deliveryExecution;

    /**
     * DeliveryPluginProvider constructor.
     * @param DeliveryExecution $deliveryExecution
     */
    public function __construct(DeliveryExecution $deliveryExecution)
    {
        $this->deliveryExecution = $deliveryExecution;
    }

    /**
     * @return array
     */
    public function getPlugins()
    {
        $delivery = $this->deliveryExecution->getDelivery();
        $serviceManager = ServiceManager::getServiceManager();

        $pluginService = $serviceManager->get(TestPluginService::CONFIG_ID);
        $testRunnerFeatureService = $serviceManager->get(TestRunnerFeatureService::SERVICE_ID);

        $allPlugins = $pluginService->getAllPlugins();

        $allTestRunnerFeatures = $testRunnerFeatureService->getAll();
        $activeTestRunnerFeaturesIds = explode(
            ',',
            $delivery->getOnePropertyValue(new \core_kernel_classes_Property(TestRunnerFeatures::TEST_RUNNER_FEATURES_PROPERTY))
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
}