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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoDeliveryRdf\scripts\install;

use oat\oatbox\extension\InstallAction;
use oat\taoBackOffice\model\routing\ResourceUrlBuilder;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;

/**
 * @author Gyula Szucs <gyula@taotesting.com>
 */
class RegisterClassToResourceUrlBuilder extends InstallAction
{
    public function __invoke($params)
    {
        /** @var ResourceUrlBuilder $urlBuilder */
        $urlBuilder = $this->getServiceManager()->get(ResourceUrlBuilder::SERVICE_ID);

        $urlBuilder->addUrlConfig(DeliveryAssemblyService::CLASS_URI, 'taoDeliveryRdf');

        $this->getServiceManager()->register(ResourceUrlBuilder::SERVICE_ID, $urlBuilder);

        return \common_report_Report::createSuccess('Class name successfully registered.');
    }
}