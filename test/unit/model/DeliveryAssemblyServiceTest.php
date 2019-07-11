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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA;
 *
 */

use oat\generis\test\TestCase;
use oat\tao\model\TaoOntology;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use tao_models_classes_service_FileStorage;
use tao_models_classes_service_ServiceCall;
use oat\taoDelivery\model\RuntimeService;

class DeliveryAssemblyServiceTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    public function testGetRootClass()
    {
        $service = new DeliveryAssemblyService();
        $this->assertInstanceOf(core_kernel_classes_Class::class, $service->getRootClass());
    }

    public function testGetAllAssemblies()
    {
        $class = $this->getMockBuilder(core_kernel_classes_Class::class)
            ->setConstructorArgs(['test'])
            ->setMethods(['getInstances'])
            ->getMock();
        $class->method('getInstances')->willReturn(true);

        $service = $this->getMockBuilder(DeliveryAssemblyService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRootClass'])
            ->getMock();
        $service->method('getRootClass')->willReturn($class);
        $this->assertTrue($service->getAllAssemblies());
    }

    public function testDeleteInstance()
    {
        $resource = $this->getMockBuilder(core_kernel_classes_Resource::class)
            ->setConstructorArgs(['test'])
            ->setMethods(['delete'])
            ->getMock();
        $resource->method('delete')->willReturn(true);

        $service = $this->getMockBuilder(DeliveryAssemblyService::class)
            ->disableOriginalConstructor()
            ->setMethods(['deleteDeliveryRuntime', 'deleteDeliveryDirectory'])
            ->getMock();

        $service->method('deleteDeliveryRuntime')->willReturn(true);
        $service->method('deleteDeliveryDirectory')->willReturn(true);

        $this->assertTrue($service->deleteInstance($resource));
    }

    public function testDeleteDeliveryDirectory()
    {
        $class = $this->getMockBuilder(core_kernel_classes_Class::class)
            ->setConstructorArgs(['test'])
            ->setMethods(['getInstances'])
            ->getMock();
        $class->method('getInstances')->willReturn(['test' => 'test']);

        $property1 = $this->getMockBuilder(core_kernel_classes_Property::class)
            ->setConstructorArgs(['test1'])
            ->setMethods(['delete'])
            ->getMock();
        $property1->method('delete')->willReturn(true);

        $property2 = $this->getMockBuilder(core_kernel_classes_Property::class)
            ->setConstructorArgs(['test2'])
            ->setMethods(['delete'])
            ->getMock();
        $property2->method('delete')->willReturn(true);

        $assembly = $this->getMockBuilder(core_kernel_classes_Resource::class)
            ->setConstructorArgs(['test'])
            ->setMethods(['getPropertyValues'])
            ->getMock();
        $assembly->method('getPropertyValues')->willReturn(['test1' => $property1, 'test2' => $property2]);

        $fileStorage = $this->getMockBuilder(tao_models_classes_service_FileStorage::class)
            ->disableOriginalConstructor()
            ->setMethods(['deleteDirectoryById'])
            ->getMock();

        $service = $this->getMockBuilder(DeliveryAssemblyService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFileStorage', 'getRootClass'])
            ->getMock();
        $service->method('getFileStorage')->willReturn($fileStorage);
        $service->method('getRootClass')->willReturn($class);

        $fileStorage->method('deleteDirectoryById')->willReturn(true);
        $this->assertTrue($service->deleteDeliveryDirectory($assembly));

        $fileStorage = $this->getMockBuilder(tao_models_classes_service_FileStorage::class)
            ->disableOriginalConstructor()
            ->setMethods(['deleteDirectoryById'])
            ->getMock();

        $service = $this->getMockBuilder(DeliveryAssemblyService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFileStorage', 'getRootClass'])
            ->getMock();
        $service->method('getFileStorage')->willReturn($fileStorage);
        $service->method('getRootClass')->willReturn($class);

        $fileStorage->method('deleteDirectoryById')->willReturn(false);
        $this->assertFalse($service->deleteDeliveryDirectory($assembly));
    }

    public function testGetRuntime()
    {
        $assembly = $this->getMockBuilder(core_kernel_classes_Resource::class)
            ->setConstructorArgs(['test'])
            ->getMock();

        $runtimeService = $this->getMockBuilder(RuntimeService::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRuntime', 'getDeliveryContainer'])
            ->getMock();
        $runtimeService->method('getRuntime')->willReturn(true);
        $runtimeService->method('getDeliveryContainer')->willReturn(true);

        $serviceLocator = $this->getServiceLocatorMock([
            RuntimeService::SERVICE_ID => $runtimeService,
        ]);

        $service = new DeliveryAssemblyService();
        $service->setServiceLocator($serviceLocator);
        $this->assertTrue($service->getRuntime($assembly));
    }

    public function testGetCompilationDate() {
        $assembly = $this->getMockBuilder(core_kernel_classes_Resource::class)
            ->setConstructorArgs(['test'])
            ->setMethods(['getUniquePropertyValue'])
            ->getMock();
        $assembly->method('getUniquePropertyValue')->willReturn('test');

        $service = new DeliveryAssemblyService();
        $this->assertEquals('test', $service->getCompilationDate($assembly));
    }

    public function testGetOrigin()
    {
        $assembly = $this->getMockBuilder(core_kernel_classes_Resource::class)
            ->setConstructorArgs(['test'])
            ->setMethods(['getUniquePropertyValue'])
            ->getMock();
        $assembly->method('getUniquePropertyValue')->willReturn('test');

        $service = new DeliveryAssemblyService();
        $this->assertEquals('test', $service->getOrigin($assembly));
    }
}
