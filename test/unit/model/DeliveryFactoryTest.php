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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

namespace oat\taoDeliveryRdf\test\model;

use common_ext_ExtensionsManager;
use common_report_Report;
use core_kernel_classes_Class;
use core_kernel_classes_Resource;
use oat\generis\model\data\Ontology;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\taoDeliveryRdf\model\DeliveryFactory;
use tao_models_classes_Compiler;
use tao_models_classes_service_ServiceCall;
use taoTests_models_classes_TestsService;

class DeliveryFactoryTest extends TestCase
{
    public function testCreateDeliveryResource()
    {
        /** @var core_kernel_classes_Class|MockObject $deliveryClassMock */
        $deliveryClassMock = $this->createMock(core_kernel_classes_Class::class);
        /** @var core_kernel_classes_Resource|MockObject $testResourceMock */
        $testResourceMock = $this->createMock(core_kernel_classes_Resource::class);
        /** @var core_kernel_classes_Resource|MockObject $deliveryResourceMock */
        $deliveryResourceMock = $this->createMock(core_kernel_classes_Resource::class);
        /** @var taoTests_models_classes_TestsService|MockObject $testServiceMock */
        $testServiceMock = $this->createMock(taoTests_models_classes_TestsService::class);
        /** @var tao_models_classes_Compiler|MockObject $compilerMock */
        $compilerMock = $this->createMock(tao_models_classes_Compiler::class);
        /** @var common_report_Report|MockObject $reportMock */
        $reportMock = $this->createMock(common_report_Report::class);
        $serviceCallMock = $this->createMock(tao_models_classes_service_ServiceCall::class);

        $modelMock = $this->createMock(Ontology::class);
        $modelMock->expects($this->once())
            ->method('getResource')
            ->willReturn($this->deliveryMock);
        $modelMock->expects($this->once())
            ->method('getProperty')
            ->willReturn(new \core_kernel_classes_Property('PROP'));


        $extensionsManagerMock = $this->createMock(common_ext_ExtensionsManager::class);

        $testServiceMock->expects($this->once())->method('getCompiler')->willReturn($compilerMock);
        $compilerMock->expects($this->once())->method('compile')->willReturn($reportMock);

        $reportMock->method('getType')->willReturn(common_report_Report::TYPE_SUCCESS);
        $reportMock->method('getData')->willReturn($serviceCallMock);

        $deliveryFactory = new DeliveryFactory();
        $deliveryFactory->setModel($modelMock);
        $deliveryFactory->setOptions([DeliveryFactory::OPTION_PROPERTIES => ['aaaa' => DeliveryContainerService::PROPERTY_RESULT_SERVER]]);
        $deliveryFactory->setServiceLocator($this->getServiceLocatorMock([
            taoTests_models_classes_TestsService::class => $testServiceMock,
            common_ext_ExtensionsManager::SERVICE_ID => $extensionsManagerMock
        ]));

        $deliveryFactory->create($deliveryClassMock, $testResourceMock, 'label', $deliveryResourceMock);

    }
}