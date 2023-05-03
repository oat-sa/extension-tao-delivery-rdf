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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\scripts\e2e\Context;

use oat\tao\model\Context\AbstractContext;

class E2eTestTakerContext extends AbstractContext
{
    public const PARAM_TEST_TAKER_RESOURCE = 'resource';
    public const PARAM_TEST_TAKER_PASSWORD = 'password';
    public const PARAM_TEST_TAKER_LOGIN = 'login';

    protected function getSupportedParameters(): array
    {
        return [
            self::PARAM_TEST_TAKER_RESOURCE,
            self::PARAM_TEST_TAKER_PASSWORD,
            self::PARAM_TEST_TAKER_LOGIN,
        ];
    }

    protected function validateParameter(string $parameter, $parameterValue): void
    {
    }
}
