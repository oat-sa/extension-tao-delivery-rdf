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
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\test\unit\model\theme\Service;

use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use oat\generis\model\data\Ontology;
use oat\generis\test\TestCase;
use oat\tao\model\theme\ThemeServiceAbstract;
use oat\taoDeliveryRdf\model\theme\DeliveryThemeDetailsProvider;
use oat\taoDeliveryRdf\model\theme\Exception\ThemeAutoSetNotSupported;
use oat\taoDeliveryRdf\model\theme\Service\ThemeAutoSetService;
use oat\taoQtiTest\model\Domain\Model\QtiTest;
use oat\taoQtiTest\model\Domain\Model\QtiTestRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;

class ThemeAutoSetServiceTest extends TestCase
{
    /** @var ThemeAutoSetService */
    private $subject;

    /** @var Ontology|MockObject */
    private $ontology;

    /** @var DeliveryThemeDetailsProvider|MockObject */
    private $deliveryThemeDetailsProvider;

    /** @var QtiTestRepositoryInterface|MockObject */
    private $qtiTestRepository;

    /** @var ThemeServiceAbstract|MockObject */
    private $themeService;

    protected function setUp(): void
    {
        $this->ontology = $this->createMock(Ontology::class);
        $this->deliveryThemeDetailsProvider = $this->createMock(DeliveryThemeDetailsProvider::class);
        $this->qtiTestRepository = $this->createMock(QtiTestRepositoryInterface::class);
        $this->themeService = $this->createMock(ThemeServiceAbstract::class);

        $this->subject = new ThemeAutoSetService(
            $this->ontology,
            $this->deliveryThemeDetailsProvider,
            $this->qtiTestRepository,
            $this->themeService
        );
    }

    public function testSetThemeByDeliveryWithOriginDeliveryId(): void
    {
        $this->setUpSetThemeByDelivery(
            'deliveryUri',
            '',
            null,
            'themeId',
            'en-US'
        );

        $this->subject->setThemeByDelivery('deliveryUri');
    }

    /**
     * @dataProvider cannotSetThemeByDeliveryProvider
     */
    public function testCannotSetThemeByDeliveryWithOriginDeliveryId(
        string  $expectedException,
        string  $expectedExceptionMessage,
        string  $deliveryUri,
        ?string $originDeliveryId,
        ?string $currentThemeId,
        ?string $themeId,
        ?string $language
    ): void {
        $this->expectExceptionMessage($expectedExceptionMessage);
        $this->expectException($expectedException);

        $this->setUpSetThemeByDelivery(
            $deliveryUri,
            $originDeliveryId,
            $currentThemeId,
            $themeId,
            $language
        );
    }

    public function cannotSetThemeByDeliveryProvider(): array
    {
        return [
            'Remote published delivery is not supported' => [
                'expectedException' => ThemeAutoSetNotSupported::class,
                'expectedExceptionMessage' => 'Cannot auto-set theme for remote publishing',
                'deliveryUri' => 'myDeliveryUri',
                'originDeliveryId' => 'myOriginDeliveryId',
                'currentThemeId' => '',
                'themeId' => null,
                'language' => null,
            ],
            'Cannot override theme' => [
                'expectedException' => ThemeAutoSetNotSupported::class,
                'expectedExceptionMessage' => 'Cannot auto-set theme cause theme "myThemeId" is already set for delivery "myDeliveryUri"',
                'deliveryUri' => 'myDeliveryUri',
                'originDeliveryId' => '',
                'currentThemeId' => 'myThemeId',
                'themeId' => null,
                'language' => null,
            ],
            'Test does not have language' => [
                'expectedException' => ThemeAutoSetNotSupported::class,
                'expectedExceptionMessage' => 'Cannot auto-set theme cause test "testUri" does not have a language',
                'deliveryUri' => 'myDeliveryUri',
                'originDeliveryId' => '',
                'currentThemeId' => null,
                'themeId' => null,
                'language' => null,
            ],
            'No theme found for language' => [
                'expectedException' => ThemeAutoSetNotSupported::class,
                'expectedExceptionMessage' => 'Cannot auto-set theme cause there is not theme associated for language',
                'deliveryUri' => 'myDeliveryUri',
                'originDeliveryId' => '',
                'currentThemeId' => null,
                'themeId' => null,
                'language' => 'en-US',
            ]
        ];
    }

    private function setUpSetThemeByDelivery(
        string $deliveryUri,
        ?string $originDeliveryId,
        ?string $currentThemeId,
        ?string $themeId,
        ?string $language
    ): void {
        $originDeliveryProperty = $this->createMock(core_kernel_classes_Property::class);
        $deliveryThemeProperty = $this->createMock(core_kernel_classes_Property::class);
        $delivery = $this->createMock(core_kernel_classes_Resource::class);
        $test = new QtiTest('testUri', $language);

        $delivery
            ->expects($this->any())
            ->method('getProperty')
            ->with()
            ->willReturnCallback(
                function ($param) use ($originDeliveryProperty, $deliveryThemeProperty) {
                    if ($param === 'http://www.tao.lu/Ontologies/TAOPublisher.rdf#OriginDeliveryID') {
                        return $originDeliveryProperty;
                    }

                    if ($param === DeliveryThemeDetailsProvider::DELIVERY_THEME_ID_URI) {
                        return $deliveryThemeProperty;
                    }
                }
            );

        $delivery
            ->expects($this->any())
            ->method('setPropertyValue')
            ->with($deliveryThemeProperty, $themeId);

        $delivery
            ->method('getOnePropertyValue')
            ->with($originDeliveryProperty)
            ->willReturn($originDeliveryProperty);

        $originDeliveryProperty
            ->method('__toString')
            ->willReturn($originDeliveryId);

        $this->ontology
            ->method('getResource')
            ->with($deliveryUri)
            ->willReturn($delivery);

        $this->deliveryThemeDetailsProvider
            ->method('getDeliveryThemeIdFromDb')
            ->with($deliveryUri)
            ->willReturn($currentThemeId);

        $this->qtiTestRepository
            ->method('findByDelivery')
            ->with($deliveryUri)
            ->willReturn($test);

        $this->themeService
            ->method('getFirstThemeIdByLanguage')
            ->with($language)
            ->willReturn($themeId);

        $this->assertNull($this->subject->setThemeByDelivery($deliveryUri));
    }
}
