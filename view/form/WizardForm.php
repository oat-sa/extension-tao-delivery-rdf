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
 * Copyright (c) 2013 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *               
 * 
 */
namespace oat\taoDeliveryRdf\view\form;

use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\oatbox\service\ServiceManager;
use oat\tao\helpers\form\WidgetRegistry;
use oat\tao\model\WidgetDefinitions;
use oat\taoDeliveryRdf\model\DeliveryFactory;
use oat\taoDeliveryRdf\model\DeliveryPublishing;
use oat\tao\model\TaoOntology;
use oat\taoDeliveryRdf\model\NoTestsException;
/**
 * Create a form from a  resource of your ontology. 
 * Each property will be a field, regarding it's widget.
 *
 * @access public
 * @author Bertrand Chevrier, <bertrand.chevrier@tudor.lu>
 * @package tao
 
 */
class WizardForm extends \tao_helpers_form_FormContainer
{

    protected function initForm()
    {
        $this->form = new \tao_helpers_form_xhtml_Form('simpleWizard');
        
        $createElt = \tao_helpers_form_FormFactory::getElement('create', 'Free');
		$createElt->setValue('<button class="form-submitter btn-success small" type="button"><span class="icon-publish"></span> ' .__('Publish').'</button>');
        $this->form->setDecorators([
            'actions-bottom' => new \tao_helpers_form_xhtml_TagWrapper(['tag' => 'div', 'cssClass' => 'form-toolbar']),
        ]);
		$this->form->setActions(array(), 'top');
		$this->form->setActions(array($createElt), 'bottom');

    }

    /*
    * Short description of method initElements
    *
    * @access public
    * @author Joel Bout, <joel.bout@tudor.lu>
    * @return mixed
    */
    public function initElements()
    {
        $class = $this->data['class'];
        if(!$class instanceof \core_kernel_classes_Class) {
            throw new \common_Exception('missing class in simple delivery creation form');
        }
        
        $classUriElt = \tao_helpers_form_FormFactory::getElement('classUri', 'Hidden');
        $classUriElt->setValue($class->getUri());
        $this->form->addElement($classUriElt);
        
        /** @var \tao_helpers_form_elements_xhtml_Hidden $testElt */
        $testElt = \tao_helpers_form_FormFactory::getElement('test', 'Hidden');
        /** @var ComplexSearchService $search */
        $search = $this->getServiceManager()->get(ComplexSearchService::SERVICE_ID);
        $queryBuilder = $search->query();
        $query = $search->searchType($queryBuilder , TaoOntology::TEST_CLASS_URI, true);
        $queryBuilder->setCriteria($query);

        $count = $search->getGateway()->count($queryBuilder);

        if (0 === $count) {
            throw new NoTestsException();
        }

        $selectElt = \tao_helpers_form_FormFactory::getElement('selectelt', 'Free');
        $selectElt->setValue('<div class="test-select-container"></div>');
        $this->form->addElement($selectElt);

        $testElt->addValidator(\tao_helpers_form_FormFactory::getValidator('NotEmpty'));
        $this->form->addElement($testElt);

        $this->initCustomElements();
    }


    public function initCustomElements()
    {
        $deliveryPublishingService = $this->getServiceManager()->get(DeliveryFactory::SERVICE_ID);
        if ($deliveryPublishingService->hasOption(DeliveryFactory::OPTION_INITIAL_PROPERTIES)) {
            $initialProperties  = $deliveryPublishingService->getOption(DeliveryFactory::OPTION_INITIAL_PROPERTIES);
            foreach ($initialProperties as $uri) {
                $property = new \core_kernel_classes_Property($uri);
                $element = \tao_helpers_form_GenerisFormFactory::elementMap($property);
                $this->form->addElement($element);
            }
        }

    }
    /**r
     * @return ServiceManager
     */
    private function getServiceManager()
    {
        return ServiceManager::getServiceManager();
    }
}