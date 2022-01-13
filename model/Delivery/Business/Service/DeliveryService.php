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

namespace oat\taoDeliveryRdf\model\Delivery\Business\Service;

use common_exception_ResourceNotFound as ResourceNotFoundException;
use oat\tao\model\Lists\Business\Validation\DependsOnPropertyValidator;
use oat\taoDeliveryRdf\model\Delivery\Business\Input\DeliveryUpdateInput;
use oat\taoDeliveryRdf\model\Delivery\DataAccess\DeliveryRepository;
use oat\taoDeliveryRdf\model\validation\DeliveryValidatorFactory;
use oat\taoDeliveryRdf\view\form\DeliveryForm;
use tao_helpers_form_Form as Form;
use tao_helpers_form_FormContainer as FormContainer;
use tao_helpers_Uri as UriHelper;

class DeliveryService
{
    /** @var DeliveryRepository */
    private $repository;

    /** @var DeliveryValidatorFactory */
    private $validatorFactory;
    /** @var DependsOnPropertyValidator */
    private $dependsOnPropertyValidator;

    public function __construct(
        DeliveryRepository $repository,
        DeliveryValidatorFactory $validatorFactory,
        DependsOnPropertyValidator $dependsOnPropertyValidator
    ) {
        $this->repository = $repository;
        $this->validatorFactory = $validatorFactory;
        $this->dependsOnPropertyValidator = $dependsOnPropertyValidator;
    }

    /**
     * @throws ResourceNotFoundException
     */
    public function update(DeliveryUpdateInput $input): void
    {
        $deliveryForm = $this->createDeliveryForm($input);
        /** @var Form $form */
        $form = $deliveryForm->getForm();

        foreach ($input->getProperties() as $property => $value) {
            $formElement = $form->getElement(UriHelper::encode($property));

            if (null === $formElement) {
                continue;
            }

            $formElement->setValue($value);
        }

        $form->evaluate();

        // TODO implement Delivery persistence
    }

    /**
     * @throws ResourceNotFoundException
     */
    private function createDeliveryForm(DeliveryUpdateInput $input): DeliveryForm
    {
        $delivery = $this->repository->findOrFail(
            $input->getSearchRequest()
        );

        return new DeliveryForm(
            $this->repository->getRootClass(),
            $delivery,
            [
                FormContainer::ADDITIONAL_VALIDATORS => $this->validatorFactory->createMultiple(),
                FormContainer::ATTRIBUTE_VALIDATORS => [
                    'data-depends-on-property' => [
                        $this->dependsOnPropertyValidator
                    ],
                ],
            ]
        );
    }
}
