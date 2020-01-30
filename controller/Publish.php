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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoDeliveryRdf\controller;

use common_exception_UserReadableException as UserReadableException;
use Exception;
use oat\generis\model\OntologyRdfs;
use oat\oatbox\event\EventManager;
use oat\tao\model\taskQueue\TaskLogActionTrait;
use oat\taoDeliveryRdf\model\DeliveryFactory;
use oat\taoDeliveryRdf\model\tasks\CompileDelivery;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use RuntimeException;
use \tao_helpers_Uri;
use taoTests_models_classes_TestsService as TestsService;

/**
 * Controller to publish delivery by test
 *
 * @package taoDeliveryRdf
 */
class Publish extends \tao_actions_SaSModule
{
    use TaskLogActionTrait;

    /**
     * @return EventManager
     */
    protected function getEventManager()
    {
        return $this->getServiceLocator()->get(EventManager::SERVICE_ID);
    }

    public function index()
    {
        $testUri = tao_helpers_Uri::decode($this->getRequestParameter('uri'));
        $test = $this->getResource($testUri);
        $this->setData('label', $test->getLabel());
        $this->setData('rootClassUri', DeliveryAssemblyService::CLASS_URI);
        $this->setData('testUri', $testUri);
        $this->setView('Publish/index.tpl');
    }

    public function publish()
    {
        try {
            $parsedBody = $this->getPsrRequest()->getParsedBody();
            $testUri = $parsedBody['testUri'] ?? null;
            $classUri = $parsedBody['classUri'] ?? null;
            $test = $this->getResource($testUri);

            $testService = $this->getTestService();
            $testItems = $testService->getTestItems($test);
            if (empty($testItems)) {
                throw new RuntimeException(
                    __('The test "%s" does not contain any items and cannot be published.', $test->getLabel())
                );
            }

            $deliveryClass = $this->getClass($classUri);
            $deliveryFactoryResources = $this->getDeliveryFactory();
            $initialProperties = $deliveryFactoryResources->getInitialPropertiesFromArray([OntologyRdfs::RDFS_LABEL => 'new delivery']);
            return $this->returnTaskJson(CompileDelivery::createTask($test, $deliveryClass, $initialProperties));
        } catch (Exception $e) {
            return $this->returnJson([
                'success' => false,
                'errorMsg' => $e instanceof UserReadableException ? $e->getUserMessage() : $e->getMessage(),
                'errorCode' => $e->getCode(),
            ]);
        }
    }

    /**
     * @return TestsService
     */
    private function getTestService()
    {
        return $this->getServiceLocator()->get(TestsService::class);
    }

    /**
     * @return DeliveryFactory
     */
    private function getDeliveryFactory()
    {
        return $this->getServiceLocator()->get(DeliveryFactory::SERVICE_ID);
        ;
    }
}
