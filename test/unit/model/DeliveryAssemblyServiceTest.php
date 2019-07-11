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
}
