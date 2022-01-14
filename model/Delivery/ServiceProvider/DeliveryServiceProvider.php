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

namespace oat\taoDeliveryRdf\model\Delivery\ServiceProvider;

use oat\generis\model\DependencyInjection\ContainerServiceProviderInterface;
use oat\oatbox\event\EventManager;
use oat\tao\model\Lists\Business\Validation\DependsOnPropertyValidator;
use oat\taoDeliveryRdf\model\Delivery\Business\Service\DeliveryService;
use oat\taoDeliveryRdf\model\Delivery\DataAccess\DeliveryRepository;
use oat\taoDeliveryRdf\model\Delivery\Presentation\Web\Form\DeliveryFormFactory;
use oat\taoDeliveryRdf\model\Delivery\Presentation\Web\RequestHandler\DeliveryPatchRequestHandler;
use oat\taoDeliveryRdf\model\Delivery\Presentation\Web\RequestHandler\DeliverySearchRequestHandler;
use oat\taoDeliveryRdf\model\Delivery\Presentation\Web\RequestHandler\JsonDeliveryPatchRequestHandler;
use oat\taoDeliveryRdf\model\Delivery\Presentation\Web\RequestHandler\UrlEncodedFormDeliveryPatchRequestHandler;
use oat\taoDeliveryRdf\model\Delivery\Presentation\Web\RequestValidator\DeliverySearchRequestValidator;
use oat\taoDeliveryRdf\model\validation\DeliveryValidatorFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

class DeliveryServiceProvider implements ContainerServiceProviderInterface
{
    public function __invoke(ContainerConfigurator $configurator): void
    {
        $services = $configurator->services();

        $services
            ->set(DeliveryService::class, DeliveryService::class)
            ->public()
            ->args([
                service(DeliveryRepository::class),
                service(DeliveryFormFactory::class),
                service(EventManager::class),
            ]);

        $services
            ->set(DeliveryPatchRequestHandler::class, DeliveryPatchRequestHandler::class)
            ->public()
            ->args([
                service(DeliverySearchRequestHandler::class),
                service(UrlEncodedFormDeliveryPatchRequestHandler::class),
                service(JsonDeliveryPatchRequestHandler::class),
            ]);

        $services
            ->set(DeliveryFormFactory::class, DeliveryFormFactory::class)
            ->public()
            ->args([
                service(DeliveryValidatorFactory::class),
                service(DependsOnPropertyValidator::class),
            ]);

        $services
            ->set(DeliverySearchRequestHandler::class, DeliverySearchRequestHandler::class)
            ->args([
                service(DeliverySearchRequestValidator::class),
            ]);

        $services
            ->set(DeliveryRepository::class, DeliveryRepository::class);

        $services
            ->set(DeliverySearchRequestValidator::class, DeliverySearchRequestValidator::class);

        $services
            ->set(UrlEncodedFormDeliveryPatchRequestHandler::class, UrlEncodedFormDeliveryPatchRequestHandler::class);

        $services
            ->set(JsonDeliveryPatchRequestHandler::class, JsonDeliveryPatchRequestHandler::class);
    }
}
