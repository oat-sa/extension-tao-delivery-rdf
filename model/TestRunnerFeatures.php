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

use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\oatbox\service\ServiceManager;
use oat\taoTests\models\runner\features\TestRunnerFeatureService;

/**
 * Event handler for Delivery Test Runner Features
 *
 * @author Christophe Noël <christophe@taotesting.com>
 */
class TestRunnerFeatures
{

    /**
     * Set the default Test Runner Features for a newly created delivery
     * xx
     * @param DeliveryCreatedEvent $event
     */
    public static function enableDefaultFeatures(DeliveryCreatedEvent $event)
    {
        $serviceManager = ServiceManager::getServiceManager();
        $testRunnerFeatureService = $serviceManager->get(TestRunnerFeatureService::SERVICE_ID);

        $allFeatures = $testRunnerFeatureService->getAll();
        $defaultFeatures = [];

        foreach($allFeatures as $feature) {
            if ($feature->isEnabledByDefault() === true) {
                $defaultFeatures[] = $feature->getId();
            }
        }

        $delivery = new \core_kernel_classes_Resource($event->getDeliveryUri());
        $delivery->setPropertiesValues([
            DeliveryContainerService::TEST_RUNNER_FEATURES_PROPERTY => implode(',', $defaultFeatures)
        ]);
    }
}
