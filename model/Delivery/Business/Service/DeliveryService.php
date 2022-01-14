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
use core_kernel_classes_Resource as KernelResource;
use DomainException;
use oat\oatbox\event\EventManager;
use oat\taoDeliveryRdf\model\Delivery\Business\Input\DeliveryUpdateInput;
use oat\taoDeliveryRdf\model\Delivery\DataAccess\DeliveryRepository;
use oat\taoDeliveryRdf\model\Delivery\Presentation\Web\Form\DeliveryFormFactory;
use oat\taoDeliveryRdf\model\event\DeliveryUpdatedEvent;
use RuntimeException;
use tao_helpers_form_elements_MultipleElement as MultipleValueElement;
use tao_helpers_form_Form as Form;
use tao_helpers_Uri as UriHelper;
use tao_models_classes_dataBinding_GenerisFormDataBinder as FormDataBinder;
use tao_models_classes_dataBinding_GenerisFormDataBindingException as FormDataBindingException;

class DeliveryService
{
    /** @var DeliveryRepository */
    private $repository;

    /** @var DeliveryFormFactory */
    private $formFactory;

    /** @var EventManager */
    private $eventManager;

    public function __construct(
        DeliveryRepository $repository,
        DeliveryFormFactory $formFactory,
        EventManager $eventManager
    ) {
        $this->repository = $repository;
        $this->formFactory = $formFactory;
        $this->eventManager = $eventManager;
    }

    /**
     * @throws ResourceNotFoundException
     * @throws DomainException
     * @throws FormDataBindingException
     */
    public function update(DeliveryUpdateInput $input): KernelResource
    {
        $delivery = $this->repository->findOrFail(
            $input->getSearchRequest()
        );

        /** @var Form $form */
        $form = $this->formFactory->create($delivery)->getForm();
        $this->setProperties($input, $form);

        $propertyValues = $form->getValues();
        (new FormDataBinder($delivery))->bind($propertyValues);
        $this->eventManager->trigger(new DeliveryUpdatedEvent($delivery->getUri(), $propertyValues));

        return $delivery;
    }

    /**
     * @throws RuntimeException
     */
    private function setProperties(DeliveryUpdateInput $input, Form $form): void
    {
        $errors = [];

        foreach ($input->getProperties() as $property => $value) {
            $errors[$this->assignPropertyValue($form, $property, $value)] = $property;
        }

        $this->handleValidationErrors($errors);
    }

    private function assignPropertyValue(Form $form, string $property, $value): string
    {
        $formElement = $form->getElement(UriHelper::encode($property));

        if (null === $formElement) {
            return '';
        }

        $this->assignValueToFormElement($formElement, $value);
        $formElement->validate();

        return $formElement->getError();
    }

    private function assignValueToFormElement(\tao_helpers_form_FormElement $formElement, $value): void
    {
        if (!$formElement instanceof MultipleValueElement) {
            $formElement->setValue($value);

            return;
        }

        $formElement->setValues([]);
        if ($value) {
            $formElement->setValue($value);
        }
    }

    /**
     * @throws RuntimeException
     */
    private function handleValidationErrors(array $errors): void
    {
        $validationErrors = [];

        unset($errors['']);
        foreach ($errors as $error => $property) {
            $validationErrors[] = "[$property] $error";
        }

        if ($validationErrors) {
            throw new RuntimeException(implode('; ', $validationErrors));
        }
    }
}
