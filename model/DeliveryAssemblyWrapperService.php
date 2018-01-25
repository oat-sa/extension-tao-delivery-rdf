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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */
namespace oat\taoDeliveryRdf\model;


use oat\oatbox\service\ConfigurableService;
use oat\taoDeliveryRdf\model\Delete\DeliveryDelete;
use oat\taoDeliveryRdf\model\Delete\DeliveryDeleteRequest;

class DeliveryAssemblyWrapperService extends ConfigurableService implements DeliveryDelete
{
    const SERVICE_ID = 'taoDeliveryRdf/DeliveryAssemblyWrapper';

    /** @var DeliveryAssemblyService */
    private $deliveryAssemblyService;

    /**
     * DeliveryAssemblyWrapperService constructor.
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);

        $this->deliveryAssemblyService = DeliveryAssemblyService::singleton();
    }

    /**
     * @inheritdoc
     */
    public function deleteDeliveryData(DeliveryDeleteRequest $request)
    {
        return $this->deliveryAssemblyService->deleteInstance($request->getDeliveryResource());
    }
}