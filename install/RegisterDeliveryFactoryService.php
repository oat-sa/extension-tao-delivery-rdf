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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoDeliveryRdf\install;

use oat\oatbox\extension\InstallAction;
use oat\taoDeliveryRdf\model\DeliveryFactory;

/**
 * Installation action that register the rdf implementation for the delivery container service
 *
 * @author Bertrand Chevrier <bertrand@taotesting.com>
 */
class RegisterDeliveryFactoryService extends InstallAction
{
    /**
     * @param $params
     */
    public function __invoke($params)
    {
        $serviceManager = $this->getServiceManager();

        $deliveryFactoryService = new DeliveryFactory([
            DeliveryFactory::OPTION_PROPERTIES => [],
            DeliveryFactory::OPTION_INITIAL_PROPERTIES => [],
            DeliveryFactory::OPTION_INITIAL_PROPERTIES_MAP => []
        ]);
        $serviceManager->propagate($deliveryFactoryService);
        $serviceManager->register(DeliveryFactory::SERVICE_ID, $deliveryFactoryService);
    }
}

