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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 *
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\test\unit\model\Delivery\Presentation\Web\RequestValidator;

use common_exception_RestApi as BadRequestException;
use Exception;
use oat\generis\test\TestCase;
use oat\taoDeliveryRdf\model\Delivery\Presentation\Web\RequestValidator\DeliverySearchRequestValidator;
use Psr\Http\Message\ServerRequestInterface;

class DeliverySearchRequestValidatorTest extends TestCase
{
    /** @var DeliverySearchRequestValidator */
    private $sut;

    /**
     * @before
     */
    public function init(): void
    {
        $this->sut = new DeliverySearchRequestValidator();
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @param array $queryParameters
     * @param Exception|null $expectedException
     *
     * @dataProvider dataProvider
     */
    public function testValidate(array $queryParameters, Exception $expectedException = null): void
    {
        if (null !== $expectedException) {
            $this->expectExceptionObject($expectedException);
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->sut->validate(
            $this->createRequest($queryParameters)
        );
    }

    public function dataProvider(): array
    {
        return [
            'Valid request' => [
                [
                    'id' => 'https://example.com/path#fragment',
                ],
            ],
            'Empty request' => [
                [
                ],
                new BadRequestException('The following query parameters must be provided: "id".'),
            ],
        ];
    }

    private function createRequest(array $queryParameters): ServerRequestInterface
    {
        $request = $this->createMock(ServerRequestInterface::class);

        $request
            ->expects($this->atLeastOnce())
            ->method('getQueryParams')
            ->willReturn($queryParameters);

        return $request;
    }
}
