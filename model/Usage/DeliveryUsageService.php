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

namespace oat\taoDeliveryRdf\model\Usage;

use core_kernel_classes_Resource;
use oat\generis\model\data\Ontology;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use Psr\Http\Message\ServerRequestInterface;
use tao_helpers_Uri;

class DeliveryUsageService
{
    private const DEFAULT_ROWS = 25;

    public function __construct(
        private DeliveryAssemblyService $deliveryAssemblyService,
        private Ontology $ontology
    ) {
    }

    public function getSourceTestByDelivery(ServerRequestInterface $request): array
    {
        $params = $request->getQueryParams();
        $deliveryUri = $this->getRequiredUri($params);
        $filter = mb_strtolower(trim((string) ($params['filterquery'] ?? '')));
        $rows = $this->getRows($params);
        $page = $this->getPage($params);
        $sortBy = $this->normalizeSortBy((string) ($params['sortby'] ?? $params['sortBy'] ?? 'label'));
        $sortOrder = $this->normalizeSortOrder((string) ($params['sortorder'] ?? $params['sortOrder'] ?? 'asc'));

        $rowsData = [];
        try {
            $delivery = $this->ontology->getResource($deliveryUri);
            $origin = $this->deliveryAssemblyService->getOrigin($delivery);

            if ($origin instanceof core_kernel_classes_Resource) {
                $row = [
                    'id' => $origin->getUri(),
                    'sourceTestUri' => $origin->getUri(),
                    'sourceTestLabel' => $origin->getLabel(),
                    'label' => $origin->getLabel(),
                    'location' => $this->resolveClassPath($origin),
                ];

                if ($filter === '' || mb_strpos(mb_strtolower((string) $row['label']), $filter) !== false) {
                    $rowsData[] = $row;
                }
            }
        } catch (\Exception) {
            $rowsData = [];
        }

        usort($rowsData, function (array $left, array $right) use ($sortBy, $sortOrder): int {
            $leftValue = (string) ($left[$sortBy] ?? '');
            $rightValue = (string) ($right[$sortBy] ?? '');
            $result = strcmp($leftValue, $rightValue);

            return $sortOrder === 'desc' ? -$result : $result;
        });

        $totalResults = count($rowsData);
        $offset = ($page - 1) * $rows;
        $pagedData = array_slice($rowsData, $offset, $rows);

        return [
            'totalResults' => $totalResults,
            'data' => array_values($pagedData),
            'page' => $page,
            'total' => $rows > 0 ? (int) ceil($totalResults / $rows) : 0,
            'totalCount' => $totalResults,
            'records' => count($pagedData),
        ];
    }

    private function resolveClassPath(core_kernel_classes_Resource $resource): string
    {
        $classIds = array_reverse($resource->getParentClassesIds());
        $labels = [];

        foreach ($classIds as $classId) {
            try {
                $labels[] = $this->ontology->getClass($classId)->getLabel();
            } catch (\Exception) {
                continue;
            }
        }

        return implode('/', $labels);
    }

    private function getRequiredUri(array $params): string
    {
        if (!array_key_exists('uri', $params)) {
            throw new \common_exception_BadRequest('Missing resource id (uri)', 400);
        }

        $decodedUri = trim(tao_helpers_Uri::decode((string) $params['uri']));

        if ($decodedUri === '') {
            throw new \common_exception_BadRequest('Missing resource id (uri)', 400);
        }

        return $decodedUri;
    }

    private function normalizeSortBy(string $sortBy): string
    {
        $normalized = strtolower(trim($sortBy));

        return in_array($normalized, ['label', 'location'], true) ? $normalized : 'label';
    }

    private function normalizeSortOrder(string $sortOrder): string
    {
        $normalized = strtolower(trim($sortOrder));

        if (in_array($normalized, ['desc', '-1', 'descending'], true)) {
            return 'desc';
        }

        if (in_array($normalized, ['asc', '1', 'ascending'], true)) {
            return 'asc';
        }

        return 'asc';
    }

    private function getRows(array $params): int
    {
        $rows = (int) ($params['rows'] ?? self::DEFAULT_ROWS);

        return max(1, min($rows, self::DEFAULT_ROWS));
    }

    private function getPage(array $params): int
    {
        return max((int) ($params['page'] ?? 1), 1);
    }
}
