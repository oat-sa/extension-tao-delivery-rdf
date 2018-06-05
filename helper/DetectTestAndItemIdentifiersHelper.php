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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 *
 */
namespace oat\taoDeliveryRdf\helper;

use oat\generis\model\OntologyAwareTrait;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoQtiItem\model\qti\ImportService;
use taoQtiTest_models_classes_QtiTestService as QtiTestService;

class DetectTestAndItemIdentifiersHelper
{
    use OntologyAwareTrait;

    /**
     * @param $deliveryId
     * @param $test
     * @param $item
     * @return array
     * @throws \core_kernel_persistence_Exception
     */
    public function detect($deliveryId, $test = null, $item = null)
    {
        $remoteNamespace = explode('#', $deliveryId);
        $testIdentifier = null;
        if (isset($test)) {
            $delivery = $this->getResource($deliveryId);
            $test = $this->getResource($delivery->getOnePropertyValue($this->getProperty(DeliveryAssemblyService::PROPERTY_ORIGIN)));
            $qtiTestIdentifier = (string) $test->getOnePropertyValue($this->getProperty(QtiTestService::PROPERTY_QTI_TEST_IDENTIFIER));
            $testIdentifier = $qtiTestIdentifier ? implode('#', [$remoteNamespace[0], $qtiTestIdentifier]) : null;
        }

        $itemIdentifier = null;
        if (isset($item)) {
            $item = $this->getResource($item);
            $qtiItemIdentifier = (string) $item->getOnePropertyValue($this->getProperty(ImportService::PROPERTY_QTI_ITEM_IDENTIFIER));
            $itemIdentifier = $qtiItemIdentifier ? implode('#', [$remoteNamespace[0], $qtiItemIdentifier]) : null;
        }

        return [$testIdentifier, $itemIdentifier];
    }
}