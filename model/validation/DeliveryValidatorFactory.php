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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA
 */

namespace oat\taoDeliveryRdf\model\validation;

use oat\oatbox\service\ConfigurableService;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use tao_helpers_form_FormFactory;
use tao_helpers_Uri;

class DeliveryValidatorFactory extends ConfigurableService
{
    private const VALIDATORS =
        [
            DeliveryAssemblyService::PROPERTY_ASSESSMENT_PROJECT_ID => [
                [
                    'AlphaNum',
                    [
                        'allow_punctuation' => true
                    ]
                ]
            ],
            DeliveryAssemblyService::PROPERTY_START => [
                [
                    'DateTime'
                ]
            ]
        ];

    public function createMultiple(): array
    {
        $output = [];

        foreach (self::VALIDATORS as $field => $validators) {
            $index = tao_helpers_Uri::encode($field);
            $output[$index] = [];

            foreach ($validators as $validator) {
                $output[$index][] = tao_helpers_form_FormFactory::getValidator(...$validator);
            }
        }

        return $output;
    }
}
