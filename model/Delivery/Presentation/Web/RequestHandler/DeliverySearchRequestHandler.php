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

namespace oat\taoDeliveryRdf\model\Delivery\Presentation\Web\RequestHandler;

use common_exception_RestApi as BadRequestException;
use oat\taoDeliveryRdf\model\Delivery\Business\Domain\DeliverySearchRequest;
use oat\taoDeliveryRdf\model\Delivery\Presentation\Web\RequestValidator\DeliverySearchRequestValidator;
use Psr\Http\Message\ServerRequestInterface;

class DeliverySearchRequestHandler
{
    public const ID = 'id';
    /** @var DeliverySearchRequestValidator */
    private $validator;

    public function __construct(DeliverySearchRequestValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param ServerRequestInterface $request
     * @return DeliverySearchRequest
     *
     * @throws BadRequestException
     */
    public function handle(ServerRequestInterface $request): DeliverySearchRequest
    {
        $this->validator->validate($request);

        $queryParameters = $request->getQueryParams();

        return new DeliverySearchRequest(
            $queryParameters[self::ID]
        );
    }
}
