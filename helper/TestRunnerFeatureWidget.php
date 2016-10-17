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
 * Copyright (c) 2008-2010 (original work) Deutsche Institut für Internationale Pädagogische Forschung (under the project TAO-TRANSFER);
 *               2009-2012 (update and modification) Public Research Centre Henri Tudor (under the project TAO-SUSTAIN & TAO-DEV);
 *               2013
 */
namespace oat\taoDeliveryRdf\helper;

use oat\oatbox\service\ServiceManager;
use oat\tao\scripts\update\OntologyUpdater;
use oat\taoTests\models\runner\features\TestRunnerFeatureService;

/**
 * xxxxxxxx
 *
 */
class TestRunnerFeatureWidget extends \tao_helpers_form_FormElement
{
    /**
     * A reference to the Widget Definition URI.
     *
     * @var string
     */
    protected $widget = 'http://www.tao.lu/datatypes/WidgetDefinitions.rdf#DeliveryTestRunnerFeature';

    /**
     * xxxxxx
     *
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
     * xxxxxx
     *
     * @return string
     */
    public function render()
    {
        //fixme: remove me
        OntologyUpdater::syncModels();

        $returnValue = (string) '';

        $serviceManager = ServiceManager::getServiceManager();
        $testRunnerFeatureService = $serviceManager->get(TestRunnerFeatureService::SERVICE_ID);

        $allFeatures = $testRunnerFeatureService->getAll();

        if (count($allFeatures) > 0) {

            // Label
            if(!isset($this->attributes['noLabel'])){
                $returnValue .= "<span class='form_desc'>"._dh($this->getDescription())."</span>";
            }
            else{
                unset($this->attributes['noLabel']);
            }

            // Options list
            $i = 0;
            $activeFeatures = explode(',', $this->value);

            $returnValue .= '<div class="form_radlst form_checklst">';
            foreach($allFeatures as $feature){
                $returnValue .= "<input " .
                    "type='checkbox' " .
                    "title='" . _dh($feature->getDescription()) . "' " .
                    "value='{$feature->getId()}' " .
                    "name='{$this->name}_{$i}' " .
                    "id='{$this->name}_{$i}' ";

                $returnValue .= $this->renderAttributes();

                if(in_array($feature->getId(), $activeFeatures)){
                    $returnValue .= " checked='checked' ";
                }
                $returnValue .=
                    " />&nbsp;" .
                    "<label class='elt_desc' " .
                    "for='{$this->name}_{$i}' " .
                    "title='" . _dh($feature->getDescription()) . "' " .
                    ">"._dh($feature->getLabel())."</label><br />";
                $i++;
            }
            $returnValue .= "</div>";
        }

        return $returnValue;
    }
}
