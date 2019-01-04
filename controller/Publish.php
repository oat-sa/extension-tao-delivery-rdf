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

use oat\generis\model\OntologyRdfs;
use oat\oatbox\event\EventManagerAwareTrait;
use oat\tao\model\taskQueue\TaskLogActionTrait;
use oat\taoDeliveryRdf\model\DeliveryFactory;
use oat\taoDeliveryRdf\model\tasks\CompileDelivery;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use \tao_helpers_Uri;

/**
 * Controller to publish delivery by test
 *
 * @package taoDeliveryRdf
 */
class Publish extends \tao_actions_SaSModule
{
    use EventManagerAwareTrait;
    use TaskLogActionTrait;

    public function index(){
        $testUri = tao_helpers_Uri::decode($this->getRequestParameter('uri'));
        $test = $this->getResource($testUri);
        $this->setData('label', $test->getLabel());
        $this->setData('rootClassUri', DeliveryAssemblyService::CLASS_URI);
        $this->setData('testUri', $testUri);
        $this->setView('Publish/index.tpl');
    }

    public function publish(){
        try {
            $testUri = $this->getRequestParameter('testUri');
            $classUri = $this->getRequestParameter('classUri');
            $test = $this->getResource($testUri);
            $deliveryClass = $this->getClass($classUri);
            /** @var DeliveryFactory $deliveryFactoryResources */
            $deliveryFactoryResources = $this->getServiceManager()->get(DeliveryFactory::SERVICE_ID);
            $initialProperties = $deliveryFactoryResources->getInitialPropertiesFromArray([OntologyRdfs::RDFS_LABEL => 'new delivery']);
            return $this->returnTaskJson(CompileDelivery::createTask($test, $deliveryClass, $initialProperties));
        }catch(\Exception $e){
            return $this->returnJson([
                'success' => false,
                'errorMsg' => $e instanceof \common_exception_UserReadableException ? $e->getUserMessage() : $e->getMessage(),
                'errorCode' => $e->getCode(),
            ]);
        }
    }
}
