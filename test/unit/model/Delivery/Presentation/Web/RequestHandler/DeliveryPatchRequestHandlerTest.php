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

use common_exception_RestApi as BadRequestException;
use oat\generis\test\TestCase;
use oat\taoDeliveryRdf\model\Delivery\Business\Domain\DeliverySearchRequest;
use oat\taoDeliveryRdf\model\Delivery\Presentation\Web\RequestHandler\DeliveryPatchRequestHandler;
use oat\taoDeliveryRdf\model\Delivery\Presentation\Web\RequestHandler\DeliveryPatchRequestHandlerInterface;
use oat\taoDeliveryRdf\model\Delivery\Presentation\Web\RequestHandler\DeliverySearchRequestHandler;
use Psr\Http\Message\ServerRequestInterface;

class DeliveryPatchRequestHandlerTest extends TestCase
{
    /** @var DeliverySearchRequestHandler */
    private $searchRequestHandlerMock;

    /** @var DeliverySearchRequest */
    private $searchRequestMock;

    /** @var ServerRequestInterface */
    private $requestMock;

    /**
     * @before
     */
    public function init(): void
    {
        $this->searchRequestHandlerMock = $this->createMock(DeliverySearchRequestHandler::class);
        $this->searchRequestMock = $this->createMock(DeliverySearchRequest::class);
        $this->requestMock = $this->createMock(ServerRequestInterface::class);

        $this->searchRequestHandlerMock
            ->expects(static::once())
            ->method('handle')
            ->with($this->requestMock)
            ->willReturnReference($this->searchRequestMock);
    }

    public function testHandleWithNoRawRequestHandlers(): void
    {
        $this->expectUnsupportedMediaTypeException();

        (new DeliveryPatchRequestHandler($this->searchRequestHandlerMock))
            ->handle($this->requestMock);
    }

    public function testHandleWithInapplicableRawRequestHandlers(): void
    {
        $this->expectUnsupportedMediaTypeException();

        (new DeliveryPatchRequestHandler(
            $this->searchRequestHandlerMock,
            $this->createRawRequestHandler(),
            $this->createRawRequestHandler()
        ))->handle($this->requestMock);
    }

    public function testHandleWithApplicableRawRequestHandler(): void
    {
        $properties = ['test' => 'value'];

        $input = (new DeliveryPatchRequestHandler(
            $this->searchRequestHandlerMock,
            $this->createRawRequestHandler(true, $properties)
        ))->handle($this->requestMock);

        $this->assertSame($this->searchRequestMock, $input->getSearchRequest());
        $this->assertSame($properties, $input->getProperties());
    }

    public function testHandleWithMultipleApplicableRawRequestHandler(): void
    {
        $properties = ['test' => 'value'];

        $input = (new DeliveryPatchRequestHandler(
            $this->searchRequestHandlerMock,
            $this->createRawRequestHandler(true, $properties),
            $this->createRawRequestHandler(true, ['other' => 'value'])
        ))->handle($this->requestMock);

        $this->assertSame($this->searchRequestMock, $input->getSearchRequest());
        $this->assertSame($properties, $input->getProperties());
    }

    public function testHandleWithMultipleApplicableAndInapplicableRawRequestHandler(): void
    {
        $properties = ['test' => 'value'];

        $input = (new DeliveryPatchRequestHandler(
            $this->searchRequestHandlerMock,
            $this->createRawRequestHandler(),
            $this->createRawRequestHandler(true, $properties)
        ))->handle($this->requestMock);

        $this->assertSame($this->searchRequestMock, $input->getSearchRequest());
        $this->assertSame($properties, $input->getProperties());
    }

    private function expectUnsupportedMediaTypeException(): void
    {
        $this->expectExceptionObject(new BadRequestException('Unsupported Media Type', 415));
    }

    private function createRawRequestHandler(
        bool $isApplicable = false,
        $properties = []
    ): DeliveryPatchRequestHandlerInterface {
        $rawRequestHandler = $this->createMock(DeliveryPatchRequestHandlerInterface::class);

        $rawRequestHandler
            ->method('isApplicable')
            ->willReturn($isApplicable);
        $rawRequestHandler
            ->expects($isApplicable ? static::any() : static::never())
            ->method('handle')
            ->with($this->requestMock)
            ->willReturn($properties);

        return $rawRequestHandler;
    }
}
