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
 * Copyright (c) 2020  (original work) Open Assessment Technologies SA;
 */

namespace oat\taoDeliveryRdf\test\unit\model;

use oat\generis\test\ServiceManagerMockTrait;
use PHPUnit\Framework\TestCase;
use oat\taoDeliveryRdf\model\ContainerRuntime;
use oat\generis\test\OntologyMockTrait;
use oat\generis\model\data\Ontology;
use common_exception_NoContent;
use tao_models_classes_service_ServiceCall;
use tao_models_classes_service_ConstantParameter;
use tao_models_classes_service_VariableParameter;
use oat\oatbox\cache\SimpleCache;
use oat\taoDelivery\model\container\delivery\DeliveryContainerRegistry;
use oat\taoDelivery\model\container\DeliveryContainer;
use oat\oatbox\log\LoggerService;

class ContainerRuntimeTest extends TestCase
{
    use ServiceManagerMockTrait;
    use OntologyMockTrait;

    public function testNoRuntime(): void
    {
        $this->expectException(common_exception_NoContent::class);
        $ontology = $this->getOntologyMock();
        $runtime = new ContainerRuntime();
        $runtime->setServiceLocator($this->getServiceManagerMock([
            Ontology::SERVICE_ID => $ontology
        ]));
        $runtime->getRuntime('https://IDoNotExist');
    }

    public function testRuntimeResourceDeserializer(): void
    {
        $ontology = $this->getOntologyMock();
        $class = $ontology->getClass('http://fakeClass');
        $delivery = $class->createInstance('Fake Delivery');

        $serviceCall = $this->getServiceCall($ontology);

        $delivery->setPropertyValue(
            $ontology->getProperty(ContainerRuntime::PROPERTY_RUNTIME),
            $serviceCall->toOntology()
        );

        $runtime = new ContainerRuntime();
        $runtime->setServiceLocator($this->getServiceManagerMock([
            Ontology::SERVICE_ID => $ontology
        ]));
        $runtime = $runtime->getRuntime($delivery->getUri());
        // adding runtime to facilitate comparison
        $runtime->setModel($ontology);
        $this->assertEquals($serviceCall, $runtime);
    }

    public function testRuntimeStringDeserializer(): void
    {
        $ontology = $this->getOntologyMock();
        $class = $ontology->getClass('http://fakeClass');
        $delivery = $class->createInstance('Fake Delivery');

        $serviceCall = $this->getServiceCall($ontology);
        $serviceCallString = json_encode(
            [
                'service' => 'http://fakeService/test#123',
                'in' => [
                    [
                            'def' => 'http://testcase/test#123',
                            'const' => 'v1',
                    ],
                    [
                        'def' => 'http://testcase/test#123',
                        'const' => 'v2',
                    ],
                ],
                'out' => [
                    'def' => 'http://testcase/test#123',
                    'proc' => 'http://testcase/test#123',
                ],
            ]
        );

        $delivery->setPropertyValue(
            $ontology->getProperty(ContainerRuntime::PROPERTY_RUNTIME),
            $serviceCallString
        );

        $runtime = new ContainerRuntime();
        $runtime->setServiceLocator($this->getServiceManagerMock([
            Ontology::SERVICE_ID => $ontology
        ]));
        $runtime = $runtime->getRuntime($delivery->getUri());
        // adding runtime to facilitate comparison
        $this->injectOntology($runtime, $ontology);
        $this->assertEquals($serviceCall, $runtime);
    }

    public function testGetDeliveryContainer(): void
    {
        $ontology = $this->getOntologyMock();
        $class = $ontology->getClass('http://fakeClass');
        $delivery = $class->createInstance('Fake Delivery');
        $delivery->setPropertyValue($ontology->getProperty(ContainerRuntime::PROPERTY_CONTAINER), 'notevenjson');
        $simpleCache = $this->createMock(SimpleCache::class);
        $deliveryContainer = $this->createMock(DeliveryContainer::class);
        $registry = $this->createMock(DeliveryContainerRegistry::class);
        $registry->method('fromJson')->with('notevenjson')->willReturn($deliveryContainer);

        $serviceLocator = $this->getServiceManagerMock([
            LoggerService::SERVICE_ID => $this->createMock(LoggerService::class),
            Ontology::SERVICE_ID => $ontology,
            SimpleCache::SERVICE_ID => $simpleCache,
            DeliveryContainerRegistry::class => $registry
        ]);
        $runtime = new ContainerRuntime();
        $runtime->setServiceLocator($serviceLocator);
        $container = $runtime->getDeliveryContainer($delivery->getUri());
        $this->assertEquals($deliveryContainer, $container);
    }

    protected function injectOntology(tao_models_classes_service_ServiceCall $serviceCall, Ontology $ontology)
    {
        $serviceCall->setModel($ontology);
        foreach ($serviceCall->getInParameters() as $param) {
            $param->getDefinition()->setModel($ontology);
        }
        $outVar = $serviceCall->jsonSerialize()['out'];
        $outVar->getDefinition()->setModel($ontology);
        $outVar->getVariable()->setModel($ontology);
    }

    protected function getServiceCall(Ontology $ontology)
    {
        $serviceCall = new tao_models_classes_service_ServiceCall('http://fakeService/test#123');
        $serviceCall->setModel($ontology);
        $serviceCall->addInParameter(new tao_models_classes_service_ConstantParameter(
            $ontology->getResource('http://testcase/test#123'),
            "v1"
        ));
        $serviceCall->addInParameter(new tao_models_classes_service_ConstantParameter(
            $ontology->getResource('http://testcase/test#123'),
            "v2"
        ));
        $serviceCall->setOutParameter(new tao_models_classes_service_VariableParameter(
            $ontology->getResource('http://testcase/test#123'),
            $ontology->getResource('http://testcase/test#123')
        ));
        return $serviceCall;
    }
}
