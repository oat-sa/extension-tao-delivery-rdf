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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoDeliveryRdf\test\integration\model;

use oat\tao\test\TaoPhpUnitTestRunner;
use common_ext_ExtensionsManager;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoDeliveryRdf\model\DeliveryContainerService;
use oat\taoDeliveryRdf\model\GroupAssignment;
use oat\generis\test\MockObject;

class DeliveryServerServiceTest extends TaoPhpUnitTestRunner
{
    /**
     * @var GroupAssignment
     */
    private $service;

    /**
     * Override the default constructor to load the extension constants
     * before dataProvider is called
     *
     * @param string $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        common_ext_ExtensionsManager::singleton()->getExtensionById('taoDeliveryRdf');
    }

    /**
     * tests initialization
     */
    public function setUp(): void
    {
        TaoPhpUnitTestRunner::initTest();
        $this->service = new GroupAssignment();
    }

    /**
     *
     * @author Lionel Lecaque, lionel@taotesting.com
     * @param string $uri
     * @return MockObject
     */
    private function getResourceMock($uri)
    {
        $resourceMock = $this->getMockBuilder('core_kernel_classes_Resource')
            ->setMockClassName('FakeResource')
            ->setConstructorArgs([$uri])
            ->getMock();

        return $resourceMock;
    }

    /**
     * @dataProvider hasDeliveryGuestAccessProvider
     * @param array $properties
     * @param bool $expected
     */
    public function testHasDeliveryGuestAccess(array $properties, $expected)
    {
        $delivery = $this->getResourceMock('fakerDeliveryAssembly');
        $delivery->method('getPropertiesValues')->willReturn($properties);

        $result = $this->invokeProtectedMethod($this->service, 'hasDeliveryGuestAccess', [$delivery]);
        $this->assertEquals($expected, $result);
    }

    public function hasDeliveryGuestAccessProvider()
    {
        return [
            'positive' => [
                [
                    DeliveryContainerService::PROPERTY_ACCESS_SETTINGS  => [
                        new \core_kernel_classes_Resource(DeliveryAssemblyService::PROPERTY_DELIVERY_GUEST_ACCESS)
                    ]
                ],
                true
            ],
            'negative' => [
                [
                    DeliveryContainerService::PROPERTY_ACCESS_SETTINGS  => []
                ],
                false
            ]
        ];
    }
}
