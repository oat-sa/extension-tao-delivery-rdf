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
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\model\theme\Listener;

use oat\oatbox\service\ConfigurableService;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoDeliveryRdf\model\theme\Exception\ThemeAutoSetNotSupported;
use oat\taoDeliveryRdf\model\theme\Service\ThemeAutoSetService;
use oat\taoDeliveryRdf\model\theme\Service\ThemeDiscoverServiceInterface;
use Throwable;

class ThemeAutoSetListener extends ConfigurableService
{
    public const SERVICE_ID = 'taoDeliveryRdf/ThemeAutoSetListener';
    public const OPTION_THEME_DISCOVER_SERVICE = 'themeDiscoverService';

    public function whenDeliveryIsCreated(DeliveryCreatedEvent $event): void
    {
        try {
            $autoSetThemeService = $this->getAutoSetThemeService();
            $themeDiscoverService = $this->getThemeDiscoverService();

            if ($themeDiscoverService) {
                $autoSetThemeService->setThemeDiscoverService($themeDiscoverService);
            }

            $autoSetThemeService->setThemeByDelivery($event->getDeliveryUri());
        } catch (ThemeAutoSetNotSupported $exception) {
            $this->logInfo(sprintf('AutoSet Theme not supported: %s', $exception->getMessage()));
        } catch (Throwable $exception) {
            $this->logError(sprintf('Could not set theme: %s', $exception->getMessage()));
        }
    }

    private function getThemeDiscoverService(): ?ThemeDiscoverServiceInterface
    {
        $themeDiscoverServiceId = $this->getOption(self::OPTION_THEME_DISCOVER_SERVICE);

        return $themeDiscoverServiceId
            ? $this->getServiceManager()->getContainer()->get($themeDiscoverServiceId)
            : null;
    }

    private function getAutoSetThemeService(): ThemeAutoSetService
    {
        return $this->getServiceManager()->getContainer()->get(ThemeAutoSetService::class);
    }
}
