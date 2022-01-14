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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA;
 *
 * @author Sergei Mikhailov <sergei.mikhailov@taotesting.com>
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\model\Delivery\Presentation\Web\Form;

use core_kernel_classes_Resource as KernelResource;
use oat\generis\model\OntologyAwareTrait;
use oat\tao\model\Lists\Business\Validation\DependsOnPropertyValidator;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoDeliveryRdf\model\validation\DeliveryValidatorFactory;
use oat\taoDeliveryRdf\view\form\DeliveryForm;
use tao_helpers_form_FormContainer as FormContainer;

class DeliveryFormFactory
{
    use OntologyAwareTrait;

    /** @var DeliveryValidatorFactory */
    private $validatorFactory;

    /** @var DependsOnPropertyValidator */
    private $dependsOnPropertyValidator;

    public function __construct(
        DeliveryValidatorFactory $validatorFactory,
        DependsOnPropertyValidator $dependsOnPropertyValidator
    ) {
        $this->validatorFactory = $validatorFactory;
        $this->dependsOnPropertyValidator = $dependsOnPropertyValidator;
    }

    public function create(KernelResource $delivery, array $additionalOptions = []): DeliveryForm
    {
        return new DeliveryForm(
            $this->getClass(DeliveryAssemblyService::CLASS_URI),
            $delivery,
            $additionalOptions + [
                FormContainer::ADDITIONAL_VALIDATORS => $this->validatorFactory->createMultiple(),
                FormContainer::ATTRIBUTE_VALIDATORS  => [
                    'data-depends-on-property' => [
                        $this->dependsOnPropertyValidator
                    ],
                ],
            ]
        );
    }
}
