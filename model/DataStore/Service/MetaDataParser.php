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

namespace oat\taoDeliveryRdf\model\DataStore\Service;

use core_kernel_classes_Resource;
use oat\taoDeliveryRdf\model\DataStore\DataStoreParser;
use oat\taoDeliveryRdf\model\DataStore\DataStoreRepositoryInterface;
use oat\taoDeliveryRdf\model\DataStore\Repository\DataStoreItemRepository;

class MetaDataParser implements DataStoreParser
{
    /** @var ItemMetaDataParser $itemMetaData */
    private $itemMetaData;

    /** @var TestMetaDataParser $testMetaData */
    private $testMetaData;

    /** @var TestMetaDataParser $testMetaData */
    private $deliveryMetaData;

    /** @var DataStoreRepositoryInterface */
    private $dataStoreRepository;

    public function __construct(
        ItemMetaDataParser $itemMetaData,
        TestMetaDataParser $testMetaData,
        DeliveryDataParser $deliveryMetaData,
        DataStoreItemRepository $dataStoreRepository
    ) {
        $this->itemMetaData = $itemMetaData;
        $this->testMetaData = $testMetaData;
        $this->deliveryMetaData = $deliveryMetaData;
        $this->dataStoreRepository = $dataStoreRepository;
    }

    public function parse(core_kernel_classes_Resource $resource): array
    {
        $items = $this->getItemsByTestId($resource);

        return [
            'items' => $this->getItemCollection($items),
            'test' => $this->testMetaData->parse($resource),
            'delivery' => $this->deliveryMetaData->parse($resource),
        ];
    }

    private function getItemCollection(iterable $items): iterable
    {
        $itemMetaData = [];
        foreach ($items as $item) {
            $itemMetaData [] = $this->itemMetaData->parse($item->getUri());
        }

        return $itemMetaData;
    }

    private function getItemsByTestId(core_kernel_classes_Resource $resource): iterable
    {
        return $this->dataStoreRepository->findByTestId($resource);
    }
}
