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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *
 *
 */
namespace oat\taoDeliveryRdf\controller;

use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\generis\model\OntologyRdfs;
use oat\oatbox\event\EventManagerAwareTrait;
use oat\tao\helpers\Template;
use core_kernel_classes_Resource;
use core_kernel_classes_Property;
use oat\tao\model\resources\ResourceWatcher;
use oat\tao\model\TaoOntology;
use oat\taoDelivery\model\AssignmentService;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoDeliveryRdf\model\DeliveryContainerService;
use oat\taoDeliveryRdf\model\DeliveryFactory;
use oat\taoDeliveryRdf\model\event\DeliveryUpdatedEvent;
use oat\taoDeliveryRdf\model\GroupAssignment;
use oat\taoDeliveryRdf\model\tasks\CompileDelivery;
use oat\taoDeliveryRdf\view\form\WizardForm;
use oat\taoDeliveryRdf\model\NoTestsException;
use oat\taoDeliveryRdf\view\form\DeliveryForm;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use common_report_Report as Report;
use oat\taoPublishing\model\publishing\delivery\PublishingDeliveryService;
use oat\taoResultServer\models\classes\implementation\OntologyService;
use oat\taoResultServer\models\classes\ResultServerService;
use oat\taoTaskQueue\model\QueueDispatcher;
use oat\taoTaskQueue\model\TaskLogInterface;
use oat\taoTaskQueue\model\TaskLogActionTrait;
use \tao_helpers_Uri;

/**
 * Controller to publish deliveries from test
 *
 * @package taoDelivery
 */
class Publish extends \tao_actions_SaSModule
{
    use EventManagerAwareTrait;
    use TaskLogActionTrait;

    /**
     * constructor: initialize the service and the default data
     *
     * @access public
     * @author CRP Henri Tudor - TAO Team - {@link http://www.tao.lu}
     */
    public function __construct()
    {
        parent::__construct();

        // the service is initialized by default
        $this->service = DeliveryAssemblyService::singleton();

        $this->defaultData();



    }

    public function index(){
        $this->setData('formTitle', __('Publish test to'));
        $this->setData('rootClassUri', DeliveryAssemblyService::CLASS_URI);
        $this->setData('testUri', tao_helpers_Uri::decode($this->getRequestParameter('uri')));
        $this->setView('Publish/index.tpl');
    }

    public function publish(){
        try {
            $testUri = $this->getRequestParameter('testUri');
            $classUri = $this->getRequestParameter('classUri');
            $test = new core_kernel_classes_Resource($testUri);
            $deliveryClass = new \core_kernel_classes_Class($classUri);
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
