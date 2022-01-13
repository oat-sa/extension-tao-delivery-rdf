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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA;
 *
 * @author Sergei Mikhailov <sergei.mikhailov@taotesting.com>
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\model\Delivery\Business\Service;

use common_exception_ResourceNotFound as ResourceNotFoundException;
use oat\taoDeliveryRdf\model\Delivery\Business\Input\DeliveryUpdateInput;
use oat\taoDeliveryRdf\model\Delivery\DataAccess\DeliveryRepository;
use oat\taoDeliveryRdf\model\validation\DeliveryValidatorFactory;
use oat\taoDeliveryRdf\view\form\DeliveryForm;

class DeliveryService
{
    /** @var DeliveryRepository */
    private $repository;

    /** @var DeliveryValidatorFactory */
    private $validatorFactory;

    public function __construct(DeliveryRepository $repository, DeliveryValidatorFactory $validatorFactory)
    {
        $this->repository = $repository;
        $this->validatorFactory = $validatorFactory;
    }

    /**
     * @throws ResourceNotFoundException
     */
    public function update(DeliveryUpdateInput $input): void
    {
        $delivery = $this->repository->findOrFail(
            $input->getSearchRequest()
        );

        //TODO implement properties' modification
    }
}
