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

namespace oat\taoDeliveryRdf\model\Delivery\Presentation\Web\RequestValidator;

use common_exception_RestApi as BadRequestException;
use oat\taoDeliveryRdf\model\Delivery\Presentation\Web\RequestHandler\DeliverySearchRequestHandler;
use Psr\Http\Message\ServerRequestInterface;

class DeliverySearchRequestValidator
{
    private const REQUIRED_QUERY_PARAMETERS = [
        DeliverySearchRequestHandler::ID,
    ];

    /**
     * @param ServerRequestInterface $request
     *
     * @throws BadRequestException
     */
    public function validate(ServerRequestInterface $request): void
    {
        $this->validateRequired($request);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @throws BadRequestException
     */
    private function validateRequired(ServerRequestInterface $request): void
    {
        $missingQueryParameters = array_diff_key(
            array_flip(self::REQUIRED_QUERY_PARAMETERS),
            $request->getQueryParams()
        );

        if ($missingQueryParameters) {
            throw new BadRequestException(
                sprintf(
                    'The following query parameters must be provided: "%s".',
                    implode('", "', array_keys($missingQueryParameters))
                )
            );
        }
    }
}
