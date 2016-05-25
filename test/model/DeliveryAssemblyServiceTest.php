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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoDeliveryRdf\test\model;

use oat\oatbox\filesystem\FileSystemService;
use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoDeliveryRdf\model\SimpleDeliveryFactory;
use Prophecy\Argument;
use Zend\ServiceManager\ServiceLocatorInterface;

class DeliveryAssemblyServiceTest extends TaoPhpUnitTestRunner
{
    protected $adapterFixture;
    protected $sampleDir;

    public function setUp()
    {
        \common_ext_ExtensionsManager::singleton()->getExtensionById('taoDeliveryRdf');
        $this->adapterFixture = 'adapterFixture';
        $this->sampleDir = __DIR__ . '/../samples/';
    }

    /**
     * @dataProvider getResultsOfDeletion()
     *
     * @param $runtimeDelete
     * @param $directoryDelete
     * @param $resourceDelete
     * @param $expected
     */
    public function testDeleteInstance($runtimeDelete, $directoryDelete, $resourceDelete, $expected)
    {
        $smProphecy = $this->prophesize(\core_kernel_classes_Resource::class);
        $smProphecy->delete()->willReturn($resourceDelete);
        $delivery = $smProphecy->reveal();

        $deliveryAssemblyServiceMock = $this->getMockBuilder(DeliveryAssemblyService::class)
            ->disableOriginalConstructor()
            ->setMethods(array('deleteDeliveryRuntime', 'deleteDeliveryDirectory'))
            ->getMock();

        $deliveryAssemblyServiceMock
            ->method('deleteDeliveryRuntime')
            ->with($this->equalTo($delivery))
            ->will($this->returnValue($runtimeDelete));

        $deliveryAssemblyServiceMock
            ->method('deleteDeliveryDirectory')
            ->with($this->equalTo($delivery))
            ->will($this->returnValue($directoryDelete));

        $this->assertEquals($expected, $deliveryAssemblyServiceMock->deleteInstance($delivery));
    }

    /**
     * DataProvider of testDeleteInstance()
     *
     * @return array
     */
    public function getResultsOfDeletion()
    {
        return [
            [false, false, false, false],

            [false, false, true, false],
            [false, true, false, false],
            [false, true, true, false],

            [true, true, false, false],
            [true, false, true, false],
            [true, false, false, false],

            [true, true, true, true]
        ];
    }

    /**
     * Test if file storage is instance of \tao_models_classes_service_FileStorage
     */
    public function testGetFileStorage()
    {
        $assemblyService = DeliveryAssemblyService::singleton();

        $class = new \ReflectionClass(DeliveryAssemblyService::class);
        $method = $class->getMethod('getFileStorage');
        $method->setAccessible(true);

        $this->assertInstanceOf(\tao_models_classes_service_FileStorage::class, $method->invokeArgs($assemblyService, []));
    }

    /**
     * Test if root class is \core_kernel_classes_Class(CLASS_COMPILEDDELIVERY)
     */
    public function testGetRootClass()
    {
        $assemblyService = DeliveryAssemblyService::singleton();

        $class = new \core_kernel_classes_Class(CLASS_COMPILEDDELIVERY);
        $this->assertEquals($class, $assemblyService->getRootClass());
    }

    /**
     * Get delivery prophecy for testDeleteDeliveryDirectory()
     *
     * @param array $instanceToDelete
     * @return object
     */
    protected function getDelivery($instanceToDelete=[])
    {
        $smProphecy = $this->prophesize(\core_kernel_classes_Resource::class);
        $smProphecy->getPropertyValues(Argument::any())->willReturn($instanceToDelete);
        $smProphecy->getUri(Argument::any())->willReturn(key($instanceToDelete));
        return $smProphecy->reveal();
    }

    /**
     * Get assembly service mock for testDeleteDeliveryDirectory()
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getAssemblyServiceMock()
    {
        return $this->getMockBuilder(DeliveryAssemblyService::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getRootClass', 'getFileStorage'))
            ->getMock();
    }

    /**
     * Get root class prophecy for testDeleteDeliveryDirectory()
     *
     * @param array $instances
     * @return object
     */
    protected function getRootClass($instances = [])
    {
        $rootClassProphecy = $this->prophesize(\core_kernel_classes_Class::class);
        $rootClassProphecy->getInstances(Argument::any(), Argument::any())->willReturn($instances);
        return $rootClassProphecy->reveal();
    }

    /**
     * Get file storage mock for testDeleteDeliveryDirectory()
     *
     * @param $expectedDeleteCall
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getFileSystem($expectedDeleteCall)
    {
        $deliveryAssemblyServiceMock = $this->getMockBuilder(\tao_models_classes_service_FileStorage::class)
            ->disableOriginalConstructor()
            ->setMethods(array('deleteDirectoryById'))
            ->getMock();

        $deliveryAssemblyServiceMock
            ->expects($this->exactly($expectedDeleteCall))
            ->method('deleteDirectoryById')
            ->will($this->returnValue(true));

        return $deliveryAssemblyServiceMock;
    }

    /**
     * Test DeleteDeliveryDirectory()
     *
     * @dataProvider getDeleteDeliveryDirectorySamples
     * @param $instanceToDelete
     * @param $existingInstances
     * @param $expectedDeleteCall
     */
    public function testDeleteDeliveryDirectory($instanceToDelete, $existingInstances, $expectedDeleteCall)
    {
        $deliveryAssemblyServiceMock = $this->getAssemblyServiceMock();

        $deliveryAssemblyServiceMock
            ->method('getRootClass')
            ->will($this->returnValue(
                $this->getRootClass($existingInstances)
            ));

        $deliveryAssemblyServiceMock
            ->method('getFileStorage')
            ->will($this->returnValue(
                $this->getFileSystem($expectedDeleteCall)
            ));

        $delivery = $this->getDelivery($instanceToDelete);
        $this->assertTrue($deliveryAssemblyServiceMock->deleteDeliveryDirectory($delivery));
    }

    /**
     * Data provider for testDeleteDeliveryDirectory()
     *
     * @return array
     */
    public function getDeleteDeliveryDirectorySamples()
    {
        return [
            // One dir to delete, only one instance
            [
                ['dir1' => ['sample1']], ['dir1' => ['sample1']], 1
            ],
            // No file to delete, two instances for a directory
            [
                ['dir1' => ['sample1']], ['dir1' => ['sample1'], 'dir2' => ['sample1']], 0
            ],
        ];
    }
}