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
use oat\taoDeliveryRdf\model\Usage\UsageRowActionsProvider;
use tao_actions_CommonModule;
use tao_helpers_Uri;

class Usage extends tao_actions_CommonModule
{
    use OntologyAwareTrait;

    public function test(): void
    {
        $uri = $this->getRequiredUri();
        $resource = $this->getExistingResource($uri);

        $this->renderUsageView('test', $uri, $resource);
    }

    public function delivery(): void
    {
        $uri = $this->getRequiredUri();
        $resource = $this->getExistingResource($uri);

        $this->renderUsageView('delivery', $uri, $resource);
    }

    private function renderUsageView(string $mode, string $uri, core_kernel_classes_Resource $resource): void
    {
        $this->setData('mode', $mode);
        $this->setData('uri', $uri);
        $this->setData('label', $resource->getLabel());
        $this->setData(
            'rowActionsExtensionEnabled',
            $this->getUsageRowActionsProvider()->isExtensionEnabled()
        );
        $this->setView('Usage/index.tpl');
    }

    public function getTestDeliveriesUsageData(): void
    {
        $response = $this->getTestUsageService()->getDeliveriesWhereTestUsed(
            $this->getPsrRequest(),
            $this->getCurrentUserId()
        );
        $this->formatResponse($response);
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

    private function getUsageRowActionsProvider(): UsageRowActionsProvider
    {
        return $this->getServiceManager()->getContainer()->get(UsageRowActionsProvider::class);
    }

    private function getTestUsageService(): TestUsageService
    {
        return $this->getServiceManager()->getContainer()->get(TestUsageService::class);
    }

    private function getCurrentUserId(): string
    {
        $user = \common_session_SessionManager::getSession()->getUser();

        return $user !== null ? (string) $user->getIdentifier() : '';
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
