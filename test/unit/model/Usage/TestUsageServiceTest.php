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
 * Foundation, Inc., 31 Milk St # 960789 Boston, MA 02196 USA.
 *
 * Copyright (c) 2026 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\test\unit\model\Usage;

use core_kernel_classes_Resource;
use oat\generis\model\data\Ontology;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\tao\helpers\dateFormatter\DateFormatterInterface;
use oat\taoDeliveryRdf\model\Usage\TestUsageService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use tao_helpers_Date;
use tao_helpers_Uri;

class TestUsageServiceTest extends TestCase
{
    private const TEST_URI = 'http://tao.local/test';

    public static function setUpBeforeClass(): void
    {
        // ponytail: bypass tao extension bootstrap; only publicationTimestamp is asserted
        $formatter = new class implements DateFormatterInterface {
            public function format($timestamp, $format, \DateTimeZone $timeZone = null): string
            {
                $timeZone ??= new \DateTimeZone('UTC');

                return (new \DateTimeImmutable('@' . (int) $timestamp))
                    ->setTimezone($timeZone)
                    ->format('d/m/Y H:i:s');
            }

            public function getFormat($format): string
            {
                return 'd/m/Y H:i:s';
            }

            public function getJavascriptFormat($format): string
            {
                return 'DD/MM/YYYY HH:mm:ss';
            }
        };

        $property = new \ReflectionProperty(tao_helpers_Date::class, 'service');
        $property->setAccessible(true);
        $property->setValue(null, $formatter);
    }

    public static function tearDownAfterClass(): void
    {
        $property = new \ReflectionProperty(tao_helpers_Date::class, 'service');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    public function testReturnsDeliveriesForProvidedTest(): void
    {
        $deliveryAssemblyService = $this->createMock(DeliveryAssemblyService::class);
        $ontology = $this->createMock(Ontology::class);

        $deliveryOne = $this->createDelivery('http://tao.local/delivery-1', 'Delivery 1', ['http://class/a']);
        $deliveryTwo = $this->createDelivery('http://tao.local/delivery-2', 'Delivery 2', ['http://class/b']);

        $deliveryAssemblyService
            ->method('findAssembliesByOrigin')
            ->with(self::TEST_URI)
            ->willReturn([$deliveryOne, $deliveryTwo]);

        $deliveryAssemblyService
            ->method('getCompilationDate')
            ->willReturnMap([
                [$deliveryOne, '2026-04-09'],
                [$deliveryTwo, '2026-04-01'],
            ]);

        $classA = $this->createMock(\core_kernel_classes_Class::class);
        $classA->method('getLabel')->willReturn('Folder A');
        $classB = $this->createMock(\core_kernel_classes_Class::class);
        $classB->method('getLabel')->willReturn('Folder B');

        $ontology->method('getClass')->willReturnMap([
            ['http://class/a', $classA],
            ['http://class/b', $classB],
        ]);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([
            'uri' => tao_helpers_Uri::encode(self::TEST_URI),
            'rows' => 25,
            'page' => 1,
            'sortby' => 'label',
            'sortorder' => 'asc',
        ]);

        $service = new TestUsageService($deliveryAssemblyService, $ontology);

        $result = $service->getDeliveriesWhereTestUsed($request);

        $this->assertSame(2, $result['totalResults']);
        $this->assertCount(2, $result['data']);
        $this->assertSame('http://tao.local/delivery-1', $result['data'][0]['deliveryId']);
        $this->assertSame('Delivery 1', $result['data'][0]['label']);
        $this->assertSame('Folder A', $result['data'][0]['classPath']);
        $this->assertSame(strtotime('2026-04-09'), $result['data'][0]['publicationTimestamp']);
        $this->assertSame('http://tao.local/delivery-2', $result['data'][1]['deliveryId']);
    }

    public function testSortsDeliveriesByLocation(): void
    {
        $deliveryAssemblyService = $this->createMock(DeliveryAssemblyService::class);
        $ontology = $this->createMock(Ontology::class);

        $deliveryOne = $this->createDelivery('http://tao.local/delivery-1', 'Delivery 1', ['http://class/z']);
        $deliveryTwo = $this->createDelivery('http://tao.local/delivery-2', 'Delivery 2', ['http://class/a']);

        $deliveryAssemblyService
            ->method('findAssembliesByOrigin')
            ->with(self::TEST_URI)
            ->willReturn([$deliveryOne, $deliveryTwo]);

        $deliveryAssemblyService
            ->method('getCompilationDate')
            ->willReturn('2026-04-09');

        $classZ = $this->createMock(\core_kernel_classes_Class::class);
        $classZ->method('getLabel')->willReturn('Zulu Folder');
        $classA = $this->createMock(\core_kernel_classes_Class::class);
        $classA->method('getLabel')->willReturn('Alpha Folder');

        $ontology->method('getClass')->willReturnMap([
            ['http://class/z', $classZ],
            ['http://class/a', $classA],
        ]);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([
            'uri' => tao_helpers_Uri::encode(self::TEST_URI),
            'rows' => 25,
            'page' => 1,
            'sortby' => 'location',
            'sortorder' => 'asc',
        ]);

        $service = new TestUsageService($deliveryAssemblyService, $ontology);
        $result = $service->getDeliveriesWhereTestUsed($request);

        $this->assertSame('http://tao.local/delivery-2', $result['data'][0]['deliveryId']);
        $this->assertSame('http://tao.local/delivery-1', $result['data'][1]['deliveryId']);
    }

    public function testFallsBackToPublicationTimeSortingWhenSortByIsUnsupported(): void
    {
        $deliveryAssemblyService = $this->createMock(DeliveryAssemblyService::class);
        $ontology = $this->createMock(Ontology::class);

        $deliveryOne = $this->createDelivery('http://tao.local/delivery-1', 'Delivery 1', ['http://class/z']);
        $deliveryTwo = $this->createDelivery('http://tao.local/delivery-2', 'Delivery 2', ['http://class/a']);

        $deliveryAssemblyService
            ->method('findAssembliesByOrigin')
            ->with(self::TEST_URI)
            ->willReturn([$deliveryOne, $deliveryTwo]);

        $deliveryAssemblyService
            ->method('getCompilationDate')
            ->willReturnMap([
                [$deliveryOne, '2026-04-09 00:00:00'],
                [$deliveryTwo, '2026-04-10 00:00:00'],
            ]);

        $classZ = $this->createMock(\core_kernel_classes_Class::class);
        $classZ->method('getLabel')->willReturn('Zulu Folder');
        $classA = $this->createMock(\core_kernel_classes_Class::class);
        $classA->method('getLabel')->willReturn('Alpha Folder');

        $ontology->method('getClass')->willReturnMap([
            ['http://class/z', $classZ],
            ['http://class/a', $classA],
        ]);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([
            'uri' => tao_helpers_Uri::encode(self::TEST_URI),
            'rows' => 25,
            'page' => 1,
            'sortby' => 'invalidField',
            'sortorder' => 'desc',
        ]);

        $service = new TestUsageService($deliveryAssemblyService, $ontology);
        $result = $service->getDeliveriesWhereTestUsed($request);

        $this->assertSame('http://tao.local/delivery-2', $result['data'][0]['deliveryId']);
        $this->assertSame('http://tao.local/delivery-1', $result['data'][1]['deliveryId']);
    }

    public function testSupportsCamelCaseSortParamsAndNumericSortOrder(): void
    {
        $deliveryAssemblyService = $this->createMock(DeliveryAssemblyService::class);
        $ontology = $this->createMock(Ontology::class);

        $deliveryOne = $this->createDelivery('http://tao.local/delivery-1', 'A Delivery', ['http://class/a']);
        $deliveryTwo = $this->createDelivery('http://tao.local/delivery-2', 'Z Delivery', ['http://class/a']);

        $deliveryAssemblyService
            ->method('findAssembliesByOrigin')
            ->with(self::TEST_URI)
            ->willReturn([$deliveryOne, $deliveryTwo]);

        $deliveryAssemblyService
            ->method('getCompilationDate')
            ->willReturn('2026-04-09');

        $classA = $this->createMock(\core_kernel_classes_Class::class);
        $classA->method('getLabel')->willReturn('Folder A');
        $ontology->method('getClass')->willReturn($classA);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([
            'uri' => tao_helpers_Uri::encode(self::TEST_URI),
            'rows' => 25,
            'page' => 1,
            'sortBy' => 'label',
            'sortOrder' => '-1',
        ]);

        $service = new TestUsageService($deliveryAssemblyService, $ontology);
        $result = $service->getDeliveriesWhereTestUsed($request);

        $this->assertSame('http://tao.local/delivery-2', $result['data'][0]['deliveryId']);
        $this->assertSame('http://tao.local/delivery-1', $result['data'][1]['deliveryId']);
    }

    public function testSortsDeliveriesByPublicationTimeUsingRawTimestamp(): void
    {
        $deliveryAssemblyService = $this->createMock(DeliveryAssemblyService::class);
        $ontology = $this->createMock(Ontology::class);

        $deliveryOne = $this->createDelivery('http://tao.local/delivery-1', 'Delivery 1', ['http://class/a']);
        $deliveryTwo = $this->createDelivery('http://tao.local/delivery-2', 'Delivery 2', ['http://class/a']);

        $deliveryAssemblyService
            ->method('findAssembliesByOrigin')
            ->with(self::TEST_URI)
            ->willReturn([$deliveryOne, $deliveryTwo]);

        $deliveryAssemblyService
            ->method('getCompilationDate')
            ->willReturnMap([
                [$deliveryOne, '1775833260000'],
                [$deliveryTwo, '1775746860000'],
            ]);

        $classA = $this->createMock(\core_kernel_classes_Class::class);
        $classA->method('getLabel')->willReturn('Folder A');
        $ontology->method('getClass')->willReturn($classA);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([
            'uri' => tao_helpers_Uri::encode(self::TEST_URI),
            'rows' => 25,
            'page' => 1,
            'sortby' => 'publicationTime',
            'sortorder' => 'desc',
        ]);

        $service = new TestUsageService($deliveryAssemblyService, $ontology);
        $result = $service->getDeliveriesWhereTestUsed($request);

        $this->assertSame('http://tao.local/delivery-1', $result['data'][0]['deliveryId']);
        $this->assertSame('http://tao.local/delivery-2', $result['data'][1]['deliveryId']);
    }

    public function testFiltersDeliveriesByLabel(): void
    {
        $deliveryAssemblyService = $this->createMock(DeliveryAssemblyService::class);
        $ontology = $this->createMock(Ontology::class);

        $deliveryOne = $this->createDelivery('http://tao.local/delivery-1', 'Alpha Delivery', []);
        $deliveryTwo = $this->createDelivery('http://tao.local/delivery-2', 'Beta Delivery', []);

        $deliveryAssemblyService
            ->method('findAssembliesByOrigin')
            ->with(self::TEST_URI)
            ->willReturn([$deliveryOne, $deliveryTwo]);

        $deliveryAssemblyService
            ->method('getCompilationDate')
            ->willReturn('2026-04-09');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([
            'uri' => tao_helpers_Uri::encode(self::TEST_URI),
            'rows' => 25,
            'page' => 1,
            'filterquery' => 'alpha',
        ]);

        $service = new TestUsageService($deliveryAssemblyService, $ontology);
        $result = $service->getDeliveriesWhereTestUsed($request);

        $this->assertSame(1, $result['totalResults']);
        $this->assertCount(1, $result['data']);
        $this->assertSame('http://tao.local/delivery-1', $result['data'][0]['deliveryId']);
    }

    public function testSupportsCamelCaseFilterQueryParam(): void
    {
        $deliveryAssemblyService = $this->createMock(DeliveryAssemblyService::class);
        $ontology = $this->createMock(Ontology::class);

        $deliveryOne = $this->createDelivery('http://tao.local/delivery-1', 'Alpha Delivery', []);
        $deliveryTwo = $this->createDelivery('http://tao.local/delivery-2', 'Beta Delivery', []);

        $deliveryAssemblyService
            ->method('findAssembliesByOrigin')
            ->with(self::TEST_URI)
            ->willReturn([$deliveryOne, $deliveryTwo]);

        $deliveryAssemblyService
            ->method('getCompilationDate')
            ->willReturn('2026-04-09');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([
            'uri' => tao_helpers_Uri::encode(self::TEST_URI),
            'rows' => 25,
            'page' => 1,
            'filterQuery' => 'beta',
        ]);

        $service = new TestUsageService($deliveryAssemblyService, $ontology);
        $result = $service->getDeliveriesWhereTestUsed($request);

        $this->assertSame(1, $result['totalResults']);
        $this->assertSame('http://tao.local/delivery-2', $result['data'][0]['deliveryId']);
    }

    public function testFiltersReturnsNoMatch(): void
    {
        $deliveryAssemblyService = $this->createMock(DeliveryAssemblyService::class);
        $ontology = $this->createMock(Ontology::class);

        $deliveryOne = $this->createDelivery('http://tao.local/delivery-1', 'Alpha Delivery', []);
        $deliveryTwo = $this->createDelivery('http://tao.local/delivery-2', 'Beta Delivery', []);

        $deliveryAssemblyService
            ->method('findAssembliesByOrigin')
            ->with(self::TEST_URI)
            ->willReturn([$deliveryOne, $deliveryTwo]);

        $deliveryAssemblyService
            ->method('getCompilationDate')
            ->willReturn('2026-04-09');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([
            'uri' => tao_helpers_Uri::encode(self::TEST_URI),
            'rows' => 25,
            'page' => 1,
            'filterquery' => 'no-match',
        ]);

        $service = new TestUsageService($deliveryAssemblyService, $ontology);
        $result = $service->getDeliveriesWhereTestUsed($request);

        $this->assertSame(0, $result['totalResults']);
        $this->assertSame([], $result['data']);
    }

    public function testThrowsBadRequestWhenUriMissing(): void
    {
        $deliveryAssemblyService = $this->createMock(DeliveryAssemblyService::class);
        $ontology = $this->createMock(Ontology::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([]);

        $service = new TestUsageService($deliveryAssemblyService, $ontology);

        $this->expectException(\common_exception_BadRequest::class);
        $service->getDeliveriesWhereTestUsed($request);
    }

    public function testUsesFallbackValuesWhenCompilationDateAndClassLookupFail(): void
    {
        $deliveryAssemblyService = $this->createMock(DeliveryAssemblyService::class);
        $ontology = $this->createMock(Ontology::class);

        $delivery = $this->createDelivery('http://tao.local/delivery-3', 'Delivery 3', ['http://class/missing']);

        $deliveryAssemblyService
            ->method('findAssembliesByOrigin')
            ->with(self::TEST_URI)
            ->willReturn([$delivery]);

        $deliveryAssemblyService
            ->method('getCompilationDate')
            ->with($delivery)
            ->willThrowException(new \RuntimeException('Missing compilation date'));

        $ontology
            ->method('getClass')
            ->with('http://class/missing')
            ->willThrowException(new \RuntimeException('Missing class'));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([
            'uri' => tao_helpers_Uri::encode(self::TEST_URI),
            'rows' => 25,
            'page' => 1,
        ]);

        $service = new TestUsageService($deliveryAssemblyService, $ontology);
        $result = $service->getDeliveriesWhereTestUsed($request);

        $this->assertSame(1, $result['totalResults']);
        $this->assertCount(1, $result['data']);
        $this->assertSame('', $result['data'][0]['classPath']);
        $this->assertSame('', $result['data'][0]['publicationTime']);
        $this->assertSame('', $result['data'][0]['publicationDate']);
    }

    private function createDelivery(string $uri, string $label, array $parents): core_kernel_classes_Resource
    {
        $delivery = $this->createMock(core_kernel_classes_Resource::class);
        $delivery->method('getUri')->willReturn($uri);
        $delivery->method('getLabel')->willReturn($label);
        $delivery->method('getParentClassesIds')->willReturn($parents);

        return $delivery;
    }
}
