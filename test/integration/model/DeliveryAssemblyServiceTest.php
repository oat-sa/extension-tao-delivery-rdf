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

namespace oat\taoDeliveryRdf\test\integration\model;

use oat\oatbox\filesystem\FileSystemService;
use oat\tao\test\integration\FileStorageTestCase;
use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use Prophecy\Argument;

require_once dirname(__FILE__) .'/../../../../tao/includes/raw_start.php';

class DeliveryAssemblyServiceTest extends FileStorageTestCase
{

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
            ->expects($this->once())
            ->method('deleteDeliveryRuntime')
            ->with($this->equalTo($delivery))
            ->will($this->returnValue($runtimeDelete));

        $deliveryAssemblyServiceMock
            ->expects($this->once())
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

            [false, false, true, true],
            [false, true, false, false],
            [false, true, true, true],

            [true, true, false, false],
            [true, false, true, true],
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
     * Test if root class is \core_kernel_classes_Class(DeliveryAssemblyService::CLASS_ID)
     */
    public function testGetRootClass()
    {
        $assemblyService = DeliveryAssemblyService::singleton();

        $class = new \core_kernel_classes_Class(DeliveryAssemblyService::CLASS_URI);
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
     * Test DeleteDeliveryDirectory()
     *
     * @dataProvider getDeleteDeliveryDirectorySamples
     * @param $instanceToDelete
     * @param $existingInstances
     * @param $expectedDeletion
     */
    public function testDeleteDeliveryDirectory($instanceToDelete, $existingInstances, $expectedDeletion)
    {

        $deliveryAssemblyServiceMock = $this->getAssemblyServiceMock();
        $fileStorage = $this->getFileStorage();

        $directoryStorage = $fileStorage->getDirectoryById('sample-');
        mkdir($directoryStorage->getPath(), 0700, true);

        $deliveryAssemblyServiceMock
            ->method('getRootClass')
            ->will($this->returnValue(
                $this->getRootClass($existingInstances)
            ));

        $deliveryAssemblyServiceMock
            ->method('getFileStorage')
            ->will($this->returnValue($fileStorage));

        $delivery = $this->getDelivery($instanceToDelete);
        $this->assertTrue($deliveryAssemblyServiceMock->deleteDeliveryDirectory($delivery));

        foreach ($instanceToDelete as $sampleDirectory) {
            if ($expectedDeletion) {
                $this->assertFileNotExists($directoryStorage->getPath());
            } else {
                $this->assertTrue($fileStorage->deleteDirectoryById('sample-'));
            }
        }
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
                ['dir1' => 'sample-'], ['dir1' => ['sample-']], true
            ],
            // No file to delete, two instances for a directory
            [
                ['dir1' => 'sample-'], ['dir1' => ['sample-'], 'dir2' => ['sample2-']], false
            ],
        ];
    }
}
