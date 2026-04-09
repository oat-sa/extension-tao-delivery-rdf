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
 * Copyright (c) 2026 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\test\unit\model\DataStore;

use oat\generis\model\data\Ontology;
use oat\generis\test\OntologyMockTrait;
use oat\generis\test\TestCase;
use oat\tao\model\featureFlag\FeatureFlagChecker;
use oat\tao\model\featureFlag\FeatureFlagCheckerInterface;
use oat\tao\model\metadata\compiler\ResourceJsonMetadataCompiler;
use oat\taoDeliveryRdf\model\DataStore\PrepareDataService;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use PHPUnit\Framework\MockObject\MockObject;
use taoQtiTest_models_classes_QtiTestService;

class PrepareDataServiceTest extends TestCase
{
    use OntologyMockTrait;

    private const DELIVERY_URI = 'http://tao.local/delivery';
    private const TEST_URI = 'http://tao.local/test';

    /** @var FeatureFlagCheckerInterface|MockObject */
    private $featureFlagChecker;

    /** @var ResourceJsonMetadataCompiler|MockObject */
    private $resourceJsonMetadataCompiler;

    /** @var taoQtiTest_models_classes_QtiTestService|MockObject */
    private $qtiTestService;

    /** @var DeliveryAssemblyService|MockObject */
    private $deliveryAssemblyService;

    private $ontology;

    private PrepareDataService $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->featureFlagChecker = $this->createMock(FeatureFlagCheckerInterface::class);
        $this->featureFlagChecker->method('isEnabled')->willReturn(false);

        $this->resourceJsonMetadataCompiler = $this->createMock(ResourceJsonMetadataCompiler::class);
        $this->resourceJsonMetadataCompiler
            ->method('compile')
            ->willReturnCallback(static function ($resource): array {
                return ['uri' => $resource->getUri()];
            });

        $this->qtiTestService = $this->createMock(taoQtiTest_models_classes_QtiTestService::class);
        $this->qtiTestService->method('getItems')->willReturn([]);

        $this->deliveryAssemblyService = $this->createMock(DeliveryAssemblyService::class);

        $this->ontology = $this->getOntologyMock();

        $serviceLocator = $this->getServiceLocatorMock(
            [
                Ontology::SERVICE_ID => $this->ontology,
                FeatureFlagChecker::class => $this->featureFlagChecker,
                ResourceJsonMetadataCompiler::SERVICE_ID => $this->resourceJsonMetadataCompiler,
                taoQtiTest_models_classes_QtiTestService::class => $this->qtiTestService,
                DeliveryAssemblyService::class => $this->deliveryAssemblyService,
            ]
        );

        $this->subject = new PrepareDataService();
        $this->subject->setServiceLocator($serviceLocator);
    }

    public function testReturnsTestUriWhenOriginExists(): void
    {
        $delivery = $this->createOntologyResource(self::DELIVERY_URI);
        $test = $this->createOntologyResource(self::TEST_URI);

        $this->deliveryAssemblyService
            ->expects($this->once())
            ->method('getOrigin')
            ->with($delivery)
            ->willReturn($test);

        $dto = $this->subject->getResourceSyncData(
            self::DELIVERY_URI,
            3,
            true,
            'dataStore',
            false,
            'tenant-1'
        );

        $this->assertSame(self::TEST_URI, $dto->getTestUri());
        $this->assertSame(
            'tenant-1',
            $dto->getMetadata()['testMetaData']['first-tenant-id']
        );
    }

    public function testReturnsNullWhenOriginIsMissing(): void
    {
        $delivery = $this->createOntologyResource(self::DELIVERY_URI);

        $this->deliveryAssemblyService
            ->expects($this->once())
            ->method('getOrigin')
            ->with($delivery)
            ->willThrowException(new \RuntimeException('Missing origin'));

        $this->qtiTestService->expects($this->never())->method('getItems');

        $dto = $this->subject->getResourceSyncData(
            self::DELIVERY_URI,
            3,
            true,
            'dataStore'
        );

        $this->assertNull($dto->getTestUri());
        $this->assertSame([], $dto->getMetadata()['testMetaData']);
        $this->assertSame([], $dto->getMetadata()['itemMetaData']);
    }

    public function testReturnsNullWhenOriginIsMalformed(): void
    {
        $delivery = $this->createOntologyResource(self::DELIVERY_URI);

        $this->deliveryAssemblyService
            ->expects($this->once())
            ->method('getOrigin')
            ->with($delivery)
            ->willThrowException(new \UnexpectedValueException('Malformed origin'));

        $dto = $this->subject->getResourceSyncData(
            self::DELIVERY_URI,
            3,
            true,
            'dataStore'
        );

        $this->assertNull($dto->getTestUri());
    }

    private function createOntologyResource(string $uri)
    {
        return $this->ontology->getResource($uri);
    }
}
