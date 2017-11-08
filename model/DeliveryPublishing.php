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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */
namespace oat\taoDeliveryRdf\model;

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use core_kernel_classes_Resource;

/**
 * Services to manage Deliveries
 *
 * @access public
 * @author Aleksej Tikhanovich, <aleksej@taotesting.com>
 * @package taoDelivery
 */
class DeliveryPublishing extends ConfigurableService
{
    use OntologyAwareTrait;

    const SERVICE_ID = 'taoDeliveryRdf/DeliveryPublishing';

    const OPTION_PUBLISH_OPTIONS = 'publish_options';
    const OPTION_PUBLISH_OPTIONS_ELEMENTS = 'elements';
    const OPTION_PUBLISH_OPTIONS_DESCRIPTION = 'description';

    public function checkRequestParameters(\Request $request, core_kernel_classes_Resource $delivery)
    {
        return $delivery;
    }

    public function setPublishOptions(array $publishOptions, core_kernel_classes_Resource $deliveryResource)
    {
        if ($publishOptions) {
            $publishConfig = $this->getOption(self::OPTION_PUBLISH_OPTIONS);
            if (isset($publishConfig[self::OPTION_PUBLISH_OPTIONS_ELEMENTS])) {
                foreach ($publishOptions as $publishOption) {
                    if (isset($publishConfig[self::OPTION_PUBLISH_OPTIONS_ELEMENTS][$publishOption])) {
                        $propertyConfig = $publishConfig[self::OPTION_PUBLISH_OPTIONS_ELEMENTS][$publishOption];
                        $property = $this->getProperty($publishOption);
                        $deliveryResource->setPropertyValue($property, $propertyConfig['value']);
                    }
                }
            }
        }
        return $deliveryResource;
    }

}
