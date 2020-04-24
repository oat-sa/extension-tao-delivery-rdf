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
 * Copyright (c) 2014-2019 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoDeliveryRdf\controller;

use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\generis\model\OntologyRdfs;
use oat\oatbox\event\EventManager;
use oat\tao\helpers\Template;
use oat\tao\model\resources\ResourceWatcher;
use oat\tao\model\TaoOntology;
use oat\tao\model\taskQueue\TaskLogActionTrait;
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
use oat\taoDelivery\model\execution\Monitoring;
use tao_helpers_form_FormContainer as FormContainer;

/**
 * Controller to managed assembled deliveries
 *
 * @author CRP Henri Tudor - TAO Team - {@link http://www.tao.lu}
 * @package taoDelivery
 */
class DeliveryMgmt extends \tao_actions_SaSModule
{
    use TaskLogActionTrait;

    /**
     * @return EventManager
     */
    protected function getEventManager()
    {
        return $this->getServiceLocator()->get(EventManager::SERVICE_ID);
    }

    /**
     * (non-PHPdoc)
     * @see \tao_actions_SaSModule::getClassService()
     */
    protected function getClassService()
    {
        if (!$this->service) {
            $this->service = DeliveryAssemblyService::singleton();
        }
        return $this->service;
    }

    /**
     * Edit a delivery instance
     *
     * @access public
     * @author CRP Henri Tudor - TAO Team - {@link http://www.tao.lu}
     * @return void
     * @throws \common_exception_NoImplementation
     * @throws \common_exception_Error
     * @throws \oat\tao\model\security\SecurityException
     */
    public function editDelivery()
    {
        $this->defaultData();

        $class = $this->getCurrentClass();
        $delivery = $this->getCurrentInstance();

        $options = [
            FormContainer::CSRF_PROTECTION_OPTION => true
        ];

        $formContainer = new DeliveryForm($class, $delivery, $options);
        $myForm = $formContainer->getForm();
        $deliveryUri = $delivery->getUri();

        if ($myForm->isSubmited() && $myForm->isValid()) {
            $this->validateInstanceRoot($deliveryUri);
            $propertyValues = $myForm->getValues();

            // then save the property values as usual
            $binder = new \tao_models_classes_dataBinding_GenerisFormDataBinder($delivery);
            $delivery = $binder->bind($propertyValues);

            $this->getEventManager()->trigger(new DeliveryUpdatedEvent($deliveryUri, $propertyValues));

            $this->setData('selectNode', \tao_helpers_Uri::encode($deliveryUri));
            $this->setData('message', __('Delivery saved'));
            $this->setData('reload', true);
        }

        $this->setData('label', $delivery->getLabel());

        // history
        $this->setData('date', $this->getClassService()->getCompilationDate($delivery));
        $serviceProxy = $this->getServiceLocator()->get(ServiceProxy::SERVICE_ID);
        if ($serviceProxy instanceof Monitoring) {
            $execs = $serviceProxy->getExecutionsByDelivery($delivery);
            $this->setData('exec', count($execs));
        }

        // define the groups related to the current delivery
        $property = $this->getProperty(GroupAssignment::PROPERTY_GROUP_DELIVERY);
        $tree = \tao_helpers_form_GenerisTreeForm::buildReverseTree($delivery, $property);
        $tree->setTitle(__('Assigned to'));
        $tree->setTemplate(Template::getTemplate('widgets/assignGroup.tpl'));
        $this->setData('groupTree', $tree->render());

        // testtaker brick
        $this->setData('assemblyUri', $deliveryUri);

        // define the subjects excluded from the current delivery
        $property = $this->getProperty(DeliveryContainerService::PROPERTY_EXCLUDED_SUBJECTS);
        $excluded = $delivery->getPropertyValues($property);
        $this->setData('ttexcluded', count($excluded));

        $users = $this->getServiceLocator()->get(AssignmentService::SERVICE_ID)->getAssignedUsers($deliveryUri);
        $assigned = array_diff(array_unique($users), $excluded);
        $this->setData('ttassigned', count($assigned));
        $updatedAt = $this->getServiceLocator()->get(ResourceWatcher::SERVICE_ID)->getUpdatedAt($delivery);
        $this->setData('updatedAt', $updatedAt);
        $this->setData('formTitle', __('Properties'));
        $this->setData('myForm', $myForm->render());

        if ($this->getServiceLocator()->get(\common_ext_ExtensionsManager::SERVICE_ID)->isEnabled('taoCampaign')) {
            $this->setData('campaign', taoCampaign_helpers_Campaign::renderCampaignTree($delivery));
        }
        $this->setView('DeliveryMgmt/editDelivery.tpl');
    }

    public function excludeTesttaker()
    {
        $this->defaultData();

        $assembly = $this->getCurrentInstance();
        $this->setData('assemblyUri', $assembly->getUri());

        // define the subjects excluded from the current delivery
        $property = $this->getProperty(DeliveryContainerService::PROPERTY_EXCLUDED_SUBJECTS);
        $excluded = [];
        foreach ($assembly->getPropertyValues($property) as $uri) {
            $user = $this->getResource($uri);
            $excluded[$uri] = $user->getLabel();
        }

        $assigned = [];
        foreach ($this->getServiceLocator()->get(AssignmentService::SERVICE_ID)->getAssignedUsers($assembly->getUri()) as $userId) {
            if (!in_array($userId, array_keys($excluded))) {
                $user = $this->getResource($userId);
                $assigned[$userId] = $user->getLabel();
            }
        }

        $this->setData('assigned', $assigned);
        $this->setData('excluded', $excluded);


        $this->setView('DeliveryMgmt/excludeTesttaker.tpl');
    }

    public function saveExcluded()
    {
        if (!$this->isXmlHttpRequest()) {
            throw new \common_exception_IsAjaxAction(__FUNCTION__);
        }
        if (!$this->hasRequestParameter('excluded')) {
            throw new \common_exception_MissingParameter('excluded');
        }

        $jsonArray = json_decode($_POST['excluded']);
        if (!is_array($jsonArray)) {
            throw new \common_Exception('parameter "excluded" should be a json encoded array');
        }

        $assembly = $this->getCurrentInstance();
        $success = $assembly->editPropertyValues($this->getProperty(DeliveryContainerService::PROPERTY_EXCLUDED_SUBJECTS), $jsonArray);

        $this->getEventManager()->trigger(new DeliveryUpdatedEvent($assembly->getUri(), [DeliveryContainerService::PROPERTY_EXCLUDED_SUBJECTS => $jsonArray]));

        $this->returnJson([
            'saved' => $success
        ]);
    }

    public function wizard()
    {
        $this->defaultData();

        try {
            $formContainer = new WizardForm(['class' => $this->getCurrentClass()]);
            $myForm = $formContainer->getForm();

            if ($myForm->isValid() && $myForm->isSubmited()) {
                try {
                    $test = $this->getResource($myForm->getValue('test'));
                    $deliveryClass = $this->getClass($myForm->getValue('classUri'));
                    /** @var DeliveryFactory $deliveryFactoryResources */
                    $deliveryFactoryResources = $this->getServiceLocator()->get(DeliveryFactory::SERVICE_ID);
                    $initialProperties = $deliveryFactoryResources->getInitialPropertiesFromArray($myForm->getValues());
                    return $this->returnTaskJson(CompileDelivery::createTask($test, $deliveryClass, $initialProperties));
                } catch (\Exception $e) {
                    return $this->returnJson([
                        'success' => false,
                        'errorMsg' => $e instanceof \common_exception_UserReadableException ? $e->getUserMessage() : $e->getMessage(),
                        'errorCode' => $e->getCode(),
                    ]);
                }
            } else {
                $this->setData('myForm', $myForm->render());
                $this->setData('formTitle', __('Create a new delivery'));
                $this->setView('form.tpl', 'tao');
            }
        } catch (NoTestsException $e) {
            $this->setView('DeliveryMgmt/wizard_error.tpl');
        }
    }

    /**
     * Prepare formatted for select2 component filtered list of available for compilation tests
     * @throws \common_Exception
     * @throws \oat\oatbox\service\ServiceNotFoundException
     */
    public function getAvailableTests()
    {
        $q = $this->getRequestParameter('q');
        $tests = [];

        $testService = \taoTests_models_classes_TestsService::singleton();
        /** @var ComplexSearchService $search */
        $search = $this->getServiceLocator()->get(ComplexSearchService::SERVICE_ID);

        $queryBuilder = $search->query();
        $query = $search->searchType($queryBuilder, TaoOntology::CLASS_URI_TEST, true)
            ->add(OntologyRdfs::RDFS_LABEL)
            ->contains($q);

        $queryBuilder->setCriteria($query);

        $result = $search->getGateway()->search($queryBuilder);

        foreach ($result as $test) {
            try {
                $testItems = $testService->getTestItems($test);
                //Filter tests which has no items
                if (!empty($testItems)) {
                    $testUri = $test->getUri();
                    $tests[] = ['id' => $testUri, 'uri' => $testUri, 'text' => $test->getLabel()];
                }
            } catch (\Exception $e) {
                $this->logWarning('Unable to load items for test ' . $testUri);
            }
        }
        $this->returnJson(['total' => count($tests), 'items' => $tests]);
    }

    /**
     * overwrite the parent moveAllInstances to add the requiresRight only in Items
     * @see tao_actions_TaoModule::moveResource()
     * @requiresRight uri WRITE
     */
    public function moveResource()
    {
        return parent::moveResource();
    }
    /**
     * overwrite the parent moveAllInstances to add the requiresRight only in Items
     * @see tao_actions_TaoModule::moveAll()
     * @requiresRight ids WRITE
     */
    public function moveAll()
    {
        return parent::moveAll();
    }

    /**
     * @param array $options
     * @throws \common_exception_IsAjaxAction
     */
    protected function getTreeOptionsFromRequest($options = [])
    {
        $config = $this->getServiceLocator()->get('taoDeliveryRdf/DeliveryMgmt')->getConfig();
        $options['order'] = key($config['OntologyTreeOrder']);
        $options['orderdir'] = $config['OntologyTreeOrder'][$options['order']];
        return parent::getTreeOptionsFromRequest($options);
    }
}
