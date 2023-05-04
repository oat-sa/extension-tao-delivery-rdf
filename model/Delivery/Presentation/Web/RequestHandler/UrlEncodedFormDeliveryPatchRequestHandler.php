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

use Psr\Http\Message\ServerRequestInterface;

final class UrlEncodedFormDeliveryPatchRequestHandler implements DeliveryPatchRequestHandlerInterface
{
    public function isApplicable(ServerRequestInterface $request): bool
    {
        return stripos($request->getHeaderLine('Content-Type'), 'application/x-www-form-urlencoded') !== false;
    }

    public function handle(ServerRequestInterface $request): array
    {
        $data = preg_replace_callback('/(?:^|(?<=&))[^=[]+/', static function (array $match) {
            return bin2hex(urldecode($match[0]));
        }, (string)$request->getBody());

        parse_str($data, $result);

        return array_combine(array_map('hex2bin', array_keys($result)), $result);
    }
}
