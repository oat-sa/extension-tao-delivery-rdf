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

use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use oat\generis\model\data\Ontology;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\oatbox\log\LoggerService;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDeliveryRdf\model\DeliveryContainerService;
use oat\taoTests\models\runner\features\TestRunnerFeatureInterface;
use oat\taoTests\models\runner\features\TestRunnerFeatureService;
use oat\taoTests\models\runner\plugins\TestPlugin;
use oat\taoTests\models\runner\plugins\TestPluginService;

class DeliveryContainerServiceTest extends TestCase
{
    /**
     * @var DeliveryContainerService
     */
    public $object;

    /**
     * @var TestPluginService|MockObject
     */
    public $testPluginServiceMock;

    /**
     * @var LoggerService|MockObject
     */
    public $loggerMock;

    /**
     * @var TestRunnerFeatureService|MockObject
     */
    public $testRunnerFeatureServiceMock;

    /**
     * @var Ontology|MockObject
     */
    public $ontologyModelMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testPluginServiceMock = $this->createMock(TestPluginService::class);
        $this->testRunnerFeatureServiceMock = $this->createMock(TestRunnerFeatureService::class);
        $this->loggerMock = $this->createMock(LoggerService::class);

        $ontologyModelMock = $this->createMock(Ontology::class);
        $ontologyModelMock->method('getProperty')
            ->willReturn($this->createMock(core_kernel_classes_Property::class));
        $slMock = $this->getServiceLocatorMock(
            [
                TestPluginService::SERVICE_ID => $this->testPluginServiceMock,
                TestRunnerFeatureService::SERVICE_ID => $this->testRunnerFeatureServiceMock,
                LoggerService::SERVICE_ID => $this->loggerMock
            ]
        );

        $this->object = new DeliveryContainerService();
        $this->object->setServiceLocator($slMock);
        $this->object->setModel($ontologyModelMock);
    }

    public function testGetPluginsNoActivePlugins()
    {
        $expectedResult = [];
        $this->testPluginServiceMock->method('getAllPlugins')
            ->willReturn([]);

        $deliveryFeatures = '';
        $deliveryExecutionMock = $this->getDeliveryExecutionMock($deliveryFeatures);

        $this->testRunnerFeatureServiceMock->method('getAll')
            ->willReturn([]);

        $result = $this->object->getPlugins($deliveryExecutionMock);

        $this->assertTrue(is_array($result), 'Method must return an array.');
        $this->assertEquals($expectedResult, $result, 'Returned list of plugins must be as expected.');
    }

    public function testGetPluginsNoConfiguredFeatures()
    {
        $allPlugins = $this->getPluginsMocks(['plugin1' => true, 'plugin2' => false, 'plugin3' => true]);
        $expectedResult = $this->getPluginsMocks(['plugin1' => true, 'plugin3' => true]);

        $this->testPluginServiceMock->method('getAllPlugins')
            ->willReturn($allPlugins);

        $deliveryFeatures = '';
        $deliveryExecutionMock = $this->getDeliveryExecutionMock($deliveryFeatures);

        $this->testRunnerFeatureServiceMock->method('getAll')
            ->willReturn([]);

        $result = $this->object->getPlugins($deliveryExecutionMock);

        $this->assertTrue(is_array($result), 'Method must return an array.');
        $this->assertEquals($expectedResult, $result, 'Method must return only active plugins.');

        // Test plugins caching - getAllPlugins method should not be called again
        $this->testPluginServiceMock->expects($this->never())
            ->method('getAllPlugins');

        $resultFromCache = $this->object->getPlugins($deliveryExecutionMock);
        $this->assertEquals($expectedResult, $resultFromCache, 'On consecutive calls method must return the same plugins.');
    }

    /**
     * @param string $deliveryFeatures
     * @param array $expectedEnabledPlugins
     *
     * @dataProvider dataProviderTestGetPluginsDeliveryWithFeatures
     */
    public function testGetPluginsDeliveryWithFeatures($deliveryFeatures, array $expectedEnabledPlugins)
    {
        $allPlugins = $this->getPluginsMocks(['plugin1' => true, 'plugin2' => false, 'plugin3' => true, 'plugin4' => true]);
        $expectedEnabledPlugins = $this->getPluginsMocks($expectedEnabledPlugins);

        $this->testPluginServiceMock->method('getAllPlugins')
            ->willReturn($allPlugins);

        // Mock features activated for delivery
        $deliveryExecutionMock = $this->getDeliveryExecutionMock($deliveryFeatures);

        // Mock all features
        $featureMock1 = $this->createMock(TestRunnerFeatureInterface::class);
        $featureMock1->method('getId')->willReturn('feature1');
        $featureMock1->method('getPluginsIds')->willReturn(['plugin1', 'plugin2', 'plugin4']);

        $featureMock2 = $this->createMock(TestRunnerFeatureInterface::class);
        $featureMock2->method('getId')->willReturn('feature2');
        $featureMock2->method('getPluginsIds')->willReturn(['plugin2', 'plugin3', 'plugin4']);

        $this->testRunnerFeatureServiceMock->method('getAll')
            ->willReturn([$featureMock1, $featureMock2]);

        $result = $this->object->getPlugins($deliveryExecutionMock);

        $this->assertTrue(is_array($result), 'Method must return an array.');
        $this->assertEquals($expectedEnabledPlugins, $result, 'Method must return only active plugins.');
    }

    /**
     * @return array
     */
    public function dataProviderTestGetPluginsDeliveryWithFeatures()
    {
        return [
            'All features enabled' => [
                'deliveryFeatures' => 'feature1,feature2',
                'expectedEnabledPlugins' => ['plugin1' => true, 'plugin3' => true, 'plugin4' => true],
            ],
            'All features disabled' => [
                'deliveryFeatures' => '',
                'expectedEnabledPlugins' => [],
            ],
            'Feature1 enabled' => [
                'deliveryFeatures' => 'feature1',
                'expectedEnabledPlugins' => ['plugin1' => true, 'plugin4' => true],
            ],
            'Feature2 enabled' => [
                'deliveryFeatures' => 'feature2',
                'expectedEnabledPlugins' => ['plugin3' => true, 'plugin4' => true],
            ],
        ];
    }

    /**
     * @param array $plugins Format ['pluginId' => true|false]
     * @return array
     */
    protected function getPluginsMocks(array $plugins)
    {
        $pluginsMocks = [];
        foreach ($plugins as $pluginId => $active) {
            $pluginMock = $this->getMockBuilder(TestPlugin::class)
                ->disableOriginalConstructor()
                ->disableOriginalClone()
                ->disableArgumentCloning()
                ->setMethods(['getId'])
                ->getMock();
            $pluginMock->method('getId')
                ->willReturn($pluginId);
            $pluginMock->setActive($active);

            $pluginsMocks[$pluginId] = $pluginMock;
        }

        return $pluginsMocks;
    }

    /**
     * @param string $deliveryFeatures
     * @return DeliveryExecution|MockObject
     */
    protected function getDeliveryExecutionMock($deliveryFeatures)
    {
        $deliveryMock = $this->createMock(core_kernel_classes_Resource::class);
        $deliveryMock->method('getOnePropertyValue')
            ->willReturn($deliveryFeatures);
        $deliveryExecutionMock = $this->createMock(DeliveryExecution::class);
        $deliveryExecutionMock->method('getDelivery')
            ->willReturn($deliveryMock);
        return $deliveryExecutionMock;
    }
}
