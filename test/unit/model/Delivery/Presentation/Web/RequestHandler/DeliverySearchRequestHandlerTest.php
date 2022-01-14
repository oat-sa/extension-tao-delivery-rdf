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

namespace oat\taoDeliveryRdf\test\unit\model\Delivery\Presentation\Web\RequestHandler;

use Exception;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\taoDeliveryRdf\model\Delivery\Business\Domain\DeliverySearchRequest;
use oat\taoDeliveryRdf\model\Delivery\Presentation\Web\RequestHandler\DeliverySearchRequestHandler;
use oat\taoDeliveryRdf\model\Delivery\Presentation\Web\RequestValidator\DeliverySearchRequestValidator;
use Psr\Http\Message\ServerRequestInterface;

class DeliverySearchRequestHandlerTest extends TestCase
{
    /** @var DeliverySearchRequestValidator|MockObject */
    private $requestValidatorMock;

    /** @var DeliverySearchRequestHandler|MockObject */
    private $sut;

    /**
     * @before
     */
    public function init(): void
    {
        $this->requestValidatorMock = $this->createMock(DeliverySearchRequestValidator::class);

        $this->sut = new DeliverySearchRequestHandler($this->requestValidatorMock);
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @param DeliverySearchRequest $expectedRequest
     * @param array $queryParameters
     *
     * @dataProvider dataProvider
     */
    public function testHandle(DeliverySearchRequest $expectedRequest, array $queryParameters): void
    {
        $request = $this->createRequest($queryParameters);

        $this->requestValidatorMock
            ->expects($this->once())
            ->method('validate')
            ->with($request);

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertEquals(
            $expectedRequest,
            $this->sut->handle($request)
        );
    }

    public function testValidationException(): void
    {
        $request = $this->createRequest();

        $exception = new Exception();

        $this->expectExceptionObject($exception);

        $this->requestValidatorMock
            ->expects($this->once())
            ->method('validate')
            ->with($request)
            ->willThrowException($exception);

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->sut->handle($request);
    }

    public function dataProvider(): array
    {
        return [
            'Search request' => [
                new DeliverySearchRequest('https://example.com/path#fragment'),
                [
                    'id' => 'https://example.com/path#fragment',
                ],
            ],
        ];
    }

    private function createRequest(array $queryParameters = []): ServerRequestInterface
    {
        $request = $this->createMock(ServerRequestInterface::class);

        $request
            ->expects($queryParameters ? $this->once() : $this->never())
            ->method('getQueryParams')
            ->willReturn($queryParameters);

        return $request;
    }
}
