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
 * Copyright (c) 2019  (original work) Open Assessment Technologies SA;
 *
 * @author Oleksandr Zagovorychev <zagovorichev@gmail.com>
 */

namespace oat\taoDeliveryRdf\model\import\assemblerDataProviders\serviceCallConverters;


use core_kernel_classes_Resource;
use tao_models_classes_service_ServiceCall;

abstract class AbstractServiceCallConverter implements ServiceCallConverterInterface
{
    /**
     * @param core_kernel_classes_Resource $resource
     * @return mixed|void
     */
    public function getServiceCallFromResource(core_kernel_classes_Resource $resource)
    {
        return tao_models_classes_service_ServiceCall::fromResource($resource);
    }
}
