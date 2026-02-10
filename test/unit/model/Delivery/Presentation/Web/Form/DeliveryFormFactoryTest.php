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
 * Copyright (c) 2026 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\test\unit\model\Delivery\Presentation\Web\Form;

use core_kernel_classes_Class as KernelClass;
use core_kernel_classes_Resource as KernelResource;
use oat\generis\test\TestCase;
use oat\tao\model\Lists\Business\Validation\DependsOnPropertyValidator;
use oat\taoDeliveryRdf\model\Delivery\Presentation\Web\Form\DeliveryFormFactory;
use oat\taoDeliveryRdf\model\validation\DeliveryValidatorFactory;
use oat\taoDeliveryRdf\view\form\DeliveryForm;
use tao_helpers_form_FormContainer as FormContainer;

class DeliveryFormFactoryTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCreateBuildsFormWithValidatorsAndOptions(): void
    {
        $this->registerDeliveryFormTestDouble();

        $validators = ['validator'];

        $validatorFactory = $this->createMock(DeliveryValidatorFactory::class);
        $validatorFactory
            ->expects($this->once())
            ->method('createMultiple')
            ->willReturn($validators);

        $dependsOnPropertyValidator = $this->createMock(DependsOnPropertyValidator::class);
        $kernelClass = $this->createMock(KernelClass::class);
        $delivery = $this->createMock(KernelResource::class);

        $factory = new TestableDeliveryFormFactory(
            $validatorFactory,
            $dependsOnPropertyValidator,
            $kernelClass
        );

        $additionalOptions = ['custom' => 'value'];

        $form = $factory->create($delivery, $additionalOptions);

        $this->assertInstanceOf(DeliveryForm::class, $form);
        $this->assertSame($additionalOptions['custom'], $form->getOptions()['custom']);
        $this->assertSame(
            $validators,
            $form->getOptions()[FormContainer::ADDITIONAL_VALIDATORS]
        );
        $this->assertSame(
            ['data-depends-on-property' => [$dependsOnPropertyValidator]],
            $form->getOptions()[FormContainer::ATTRIBUTE_VALIDATORS]
        );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCreatePassesRestrictedPropertiesOption(): void
    {
        $this->registerDeliveryFormTestDouble();

        $validatorFactory = $this->createMock(DeliveryValidatorFactory::class);
        $validatorFactory
            ->expects($this->once())
            ->method('createMultiple')
            ->willReturn([]);

        $dependsOnPropertyValidator = $this->createMock(DependsOnPropertyValidator::class);
        $kernelClass = $this->createMock(KernelClass::class);
        $delivery = $this->createMock(KernelResource::class);

        $factory = new TestableDeliveryFormFactory(
            $validatorFactory,
            $dependsOnPropertyValidator,
            $kernelClass
        );

        $restricted = ['property' => ['value']];
        $additionalOptions = [
            DeliveryFormFactory::RESTRICTED_PROPERTIES_OPTION => $restricted,
        ];

        $form = $factory->create($delivery, $additionalOptions);

        $this->assertSame(
            $restricted,
            $form->getOptions()[DeliveryFormFactory::RESTRICTED_PROPERTIES_OPTION]
        );
    }

    private function registerDeliveryFormTestDouble(): void
    {
        if (!class_exists(DeliveryForm::class, false)) {
            class_alias(TestDeliveryForm::class, DeliveryForm::class);
        }
    }
}

class TestableDeliveryFormFactory extends DeliveryFormFactory
{
    /** @var KernelClass */
    private $class;

    public function __construct(
        DeliveryValidatorFactory $validatorFactory,
        DependsOnPropertyValidator $dependsOnPropertyValidator,
        KernelClass $class
    ) {
        parent::__construct($validatorFactory, $dependsOnPropertyValidator);
        $this->class = $class;
    }

    public function getClass($uri)
    {
        return $this->class;
    }
}

class TestDeliveryForm
{
    /** @var KernelClass */
    private $clazz;

    /** @var KernelResource */
    private $instance;

    /** @var array */
    private $options;

    public function __construct(KernelClass $clazz, KernelResource $instance = null, $options = [])
    {
        $this->clazz = $clazz;
        $this->instance = $instance;
        $this->options = $options;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
