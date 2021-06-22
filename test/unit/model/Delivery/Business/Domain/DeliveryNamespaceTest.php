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
 *
 * @author Sergei Mikhailov <sergei.mikhailov@taotesting.com>
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\test\unit\model\Delivery\Business\Domain;

use oat\generis\test\TestCase;
use oat\taoDeliveryRdf\model\Delivery\Business\Domain\DeliveryNamespace;

class DeliveryNamespaceTest extends TestCase
{
    /**
     * @param string            $expected
     * @param DeliveryNamespace $sut
     *
     * @dataProvider validDeliveryNamespaces
     */
    public function testNamespaceValueNormalizationAndComparison(string $expected, DeliveryNamespace $sut): void
    {
        static::assertEquals($expected, $sut);
        static::assertTrue($sut->equals(new DeliveryNamespace($expected)));
    }

    public function validDeliveryNamespaces(): array
    {
        return [
            'uri'                   => ['https://taotesting.com', new DeliveryNamespace('https://taotesting.com')],
            'fragmentContainingUri' => ['https://taotesting.com', new DeliveryNamespace('https://taotesting.com#')],
        ];
    }
}
