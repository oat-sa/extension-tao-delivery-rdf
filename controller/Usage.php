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

namespace oat\taoDeliveryRdf\controller;

use common_exception_ResourceNotFound;
use core_kernel_classes_Resource;
use oat\generis\model\OntologyAwareTrait;
use GuzzleHttp\Psr7\Response;
use oat\tao\model\http\formatter\ResponseFormatter;
use oat\taoDeliveryRdf\model\Usage\DeliveryUsageService;
use oat\taoDeliveryRdf\model\Usage\TestUsageService;
use tao_actions_CommonModule;
use tao_helpers_Uri;

class Usage extends tao_actions_CommonModule
{
    use OntologyAwareTrait;

    public function test(): void
    {
        $uri = $this->getRequiredUri();
        $resource = $this->getExistingResource($uri);

        $this->setData('mode', 'test');
        $this->setData('uri', $uri);
        $this->setData('label', $resource->getLabel());
        $this->setView('Usage/index.tpl');
    }

    public function delivery(): void
    {
        $uri = $this->getRequiredUri();
        $resource = $this->getExistingResource($uri);

        $this->setData('mode', 'delivery');
        $this->setData('uri', $uri);
        $this->setData('label', $resource->getLabel());
        $this->setView('Usage/index.tpl');
    }

    public function getTestDeliveriesUsageData(): void
    {
        $this->formatResponse($this->getTestUsageService()->getDeliveriesWhereTestUsed($this->getPsrRequest()));
    }

    public function getDeliverySourceTestData(): void
    {
        $this->formatResponse($this->getDeliveryUsageService()->getSourceTestByDelivery($this->getPsrRequest()));
    }

    private function formatResponse(array $body): void
    {
        $responseFormatter = $this->getServiceManager()->getContainer()->get(ResponseFormatter::class);

        $this->setResponse(
            $responseFormatter
                ->withJsonHeader()
                ->withStatusCode(200)
                ->withBody($body)
                ->format(new Response())
        );
    }

    private function getTestUsageService(): TestUsageService
    {
        return $this->getServiceManager()->getContainer()->get(TestUsageService::class);
    }

    private function getDeliveryUsageService(): DeliveryUsageService
    {
        return $this->getServiceManager()->getContainer()->get(DeliveryUsageService::class);
    }

    private function getExistingResource(string $uri): core_kernel_classes_Resource
    {
        $resource = $this->getResource($uri);

        if (!$resource->exists()) {
            throw new common_exception_ResourceNotFound($uri);
        }

        return $resource;
    }

    private function getRequiredUri(): string
    {
        $decodedUri = trim(tao_helpers_Uri::decode((string) $this->getRequestParameter('uri')));

        if ($decodedUri === '') {
            throw new \common_exception_BadRequest('Missing resource id (uri)', 400);
        }

        return $decodedUri;
    }
}
