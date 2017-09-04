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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 * @author Christophe Noël <christophe@taotesting.com>
 */
namespace oat\taoDeliveryRdf\helper;

use oat\oatbox\service\ServiceManager;
use oat\taoTests\models\runner\features\TestRunnerFeatureService;

/**
 * Allow the selection of the Test Runner Features wanted for a specific delivery
 */
class TestRunnerFeatureWidget extends \tao_helpers_form_FormElement
{
    const WIDGET_TPL = 'views/templates/widgets/testRunnerFeature.tpl.php';

    /**
     * A reference to the Widget Definition URI.
     *
     * @var string
     */
    protected $widget = 'http://www.tao.lu/datatypes/WidgetDefinitions.rdf#DeliveryTestRunnerFeature';

    /**
     * Data is stored as a coma-separated list of active test runner features ids
     * ex: progressBar,accessibility,security
     */
    public function feed() {
        $activeFeatures = [];

        $expression = "/^".preg_quote($this->name, "/")."(.)*[0-9]+$/";
        foreach($_POST as $key => $value){
            if(preg_match($expression, $key)){
                $activeFeatures[] = $value;
            }
        }
        $this->setValue(implode(',', $activeFeatures));
    }

    /**
     * Render the Widget to allow Test Runner Features selection
     *
     * @return string
     */
    public function render()
    {
        $serviceManager = ServiceManager::getServiceManager();
        $testRunnerFeatureService = $serviceManager->get(TestRunnerFeatureService::SERVICE_ID);

        $allFeatures = $testRunnerFeatureService->getAll();

        $activeFeatures = explode(',', $this->value);

        $choicesList = [];
        $i = 0;

        if (count($allFeatures) > 0) {
            foreach($allFeatures as $feature){
                $choicesList[] = [
                    "title"     => $feature->getDescription(),
                    "value"     => $feature->getId(),
                    "id"        => $this->name . "_" . $i,
                    "checked"   => (in_array($feature->getId(), $activeFeatures)) ? ' checked="checked" ' : '',
                    "label"     => _dh($feature->getLabel())
                ];
                $i++;
            }
        }

        $tpl = \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDeliveryRdf')->getDir() . self::WIDGET_TPL ;
        $templateRenderer = new \taoItems_models_classes_TemplateRenderer($tpl, array(
            'propLabel'   => _dh($this->getDescription()),
            'choicesList' => $choicesList
        ));

        return $templateRenderer->render();
    }
}
