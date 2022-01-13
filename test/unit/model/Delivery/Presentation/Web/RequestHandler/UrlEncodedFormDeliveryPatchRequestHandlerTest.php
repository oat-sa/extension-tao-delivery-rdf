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

namespace oat\taoDeliveryRdf\test\unit\model\Delivery\Presentation\Web\RequestHandler;

use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\taoDeliveryRdf\model\Delivery\Presentation\Web\RequestHandler\UrlEncodedFormDeliveryPatchRequestHandler;
use Psr\Http\Message\ServerRequestInterface;

class UrlEncodedFormDeliveryPatchRequestHandlerTest extends TestCase
{
    /** @var UrlEncodedFormDeliveryPatchRequestHandler */
    private $sut;

    /** @var ServerRequestInterface|MockObject */
    private $requestMock;

    /**
     * @before
     */
    public function init(): void
    {
        $this->sut = new UrlEncodedFormDeliveryPatchRequestHandler();

        $this->requestMock = $this->createMock(ServerRequestInterface::class);
    }

    /**
     * @testWith [false, "multipart/form-data"]
     *           [false, "application/json"]
     *           [false, "application/x-json, application/json"]
     *           [true, "application/x-www-form-urlencoded"]
     */
    public function testIsApplicable(bool $expected, string $contentType): void
    {
        $this->expectContentType($contentType);

        $this->assertSame(
            $expected,
            $this->sut->isApplicable($this->requestMock)
        );
    }

    /**
     * @testWith ["property_1=value_1&property_2=value_2"]
     *           [""]
     */
    public function testHandle(string $body): void
    {
        $this->expectBody($body);

        parse_str($body, $expected);

        $this->assertSame(
            $expected,
            $this->sut->handle($this->requestMock)
        );
    }

    private function expectContentType(string $contentType): void
    {
        $this->requestMock
            ->expects(static::once())
            ->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn($contentType);
    }

    private function expectBody(string $body): void
    {
        $this->requestMock
            ->expects(static::once())
            ->method('getBody')
            ->willReturn($body);
    }
}
