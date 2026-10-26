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
 */

namespace oat\taoDeliveryRdf\view\form;

use core_kernel_classes_Property;
use oat\oatbox\service\ServiceManager;
use oat\tao\model\controller\SignedFormInstance;
use oat\tao\model\theme\ThemeService;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoDeliveryRdf\model\DeliveryContainerService;
use oat\taoDeliveryRdf\model\theme\DeliveryThemeDetailsProvider;
use tao_helpers_form_FormFactory;
use tao_helpers_Uri;

/**
 * Create a form from a  resource of your ontology.
 * Each property will be a field, regarding it's widget.
 *
 * @access public
 * @package taoDelivery

 */
class DeliveryForm extends SignedFormInstance
{
    protected function initForm()
    {
        parent::initForm();

        $saveELt = tao_helpers_form_FormFactory::getElement('Save', 'Free');
        $saveELt->setValue(
            '<button class="form-submitter btn-success small" type="button"><span class="icon-save"></span>'
                . __('Save') . '</button>'
        );

        $actions = [$saveELt];
        if ($this->getInstance()) {
            $originTestUris = $this->getInstance()->getPropertyValues(
                new core_kernel_classes_Property(DeliveryAssemblyService::PROPERTY_ORIGIN)
            );
            $originTestUri = current($originTestUris);

            if ($originTestUri) {
                $goToTestElt = tao_helpers_form_FormFactory::getElement('go-to-test', 'Free');
                $goToTestElt->setValue(
                    '<button class="go-to-test btn-info small" type="button" data-test-uri="'
                        . htmlspecialchars((string) $originTestUri, ENT_QUOTES, 'UTF-8')
                        . '"><span class="icon-test"></span>'
                        . htmlspecialchars(__('Go to test'), ENT_QUOTES, 'UTF-8')
                        . '</button>'
                );
                $actions[] = $goToTestElt;
            }
        }

        $this->form->setActions([], 'top');
        $this->form->setActions($actions, 'bottom');
    }

    protected function initElements()
    {
        parent::initElements();
        $maxExecElt = $this->form->getElement(tao_helpers_Uri::encode(DeliveryContainerService::PROPERTY_MAX_EXEC));
        if (! is_null($maxExecElt)) {
            $maxExecElt->addValidators([
                tao_helpers_form_FormFactory::getValidator('Integer', [
                    'min' => 1
                ])
            ]);
            $this->form->addElement($maxExecElt);
        }

        $periodEndElt = $this->form->getElement(tao_helpers_Uri::encode(DeliveryContainerService::PROPERTY_END));
        if (! is_null($periodEndElt)) {
            $periodEndElt->addValidators([
                tao_helpers_form_FormFactory::getValidator('DateTime', [
                    'comparator' => '>=',
                    'datetime2_ref' => $this->form->getElement(
                        tao_helpers_Uri::encode(DeliveryContainerService::PROPERTY_START)
                    ),
                ])
            ]);
            $this->form->addElement($periodEndElt);
        }

        $resultServerElt = $this->form->getElement(
            tao_helpers_Uri::encode(DeliveryContainerService::PROPERTY_RESULT_SERVER)
        );

        if (! is_null($resultServerElt)) {
            $resultServerElt->addValidators([
                tao_helpers_form_FormFactory::getValidator('NotEmpty')
            ]);
            $this->form->addElement($resultServerElt);
        }

        $this->setThemeNameSelectorOptions();
    }

    /**
     * Sets the theme name selector options.
     *
     * @return bool
     */
    protected function setThemeNameSelectorOptions()
    {
        $elementUri = tao_helpers_Uri::encode(DeliveryThemeDetailsProvider::DELIVERY_THEME_ID_URI);
        if (!$this->form->hasElement($elementUri)) {
            return false;
        }

        /** @var ThemeService $themeService */
        $themeService = ServiceManager::getServiceManager()->get(ThemeService::SERVICE_ID);
        $allThemes    = $themeService->getAllThemes();
        $options      = [];
        foreach ($allThemes as $currentThemeId => $currentTheme) {
            $options[$currentThemeId] = $currentThemeId;
            if (method_exists($currentTheme, 'getLabel')) {
                $options[$currentThemeId] = $currentTheme->getLabel();
            }
        }

        $this->form->getElement($elementUri)->setOptions($options);

        return true;
    }
}
