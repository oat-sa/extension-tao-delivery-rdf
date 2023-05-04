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

namespace oat\taoDeliveryRdf\model\theme\Service;

use core_kernel_classes_Resource;
use oat\generis\model\data\Ontology;
use oat\tao\model\theme\ThemeServiceAbstract;
use oat\taoDeliveryRdf\model\theme\DeliveryThemeDetailsProvider;
use oat\taoDeliveryRdf\model\theme\Exception\ThemeAutoSetNotSupported;
use oat\taoQtiTest\model\Domain\Model\QtiTestRepositoryInterface;

class ThemeAutoSetService
{
    private const PUBLISHING_ORIGINAL_ID = 'http://www.tao.lu/Ontologies/TAOPublisher.rdf#OriginDeliveryID';

    /** @var Ontology */
    private $ontology;

    /** @var DeliveryThemeDetailsProvider */
    private $deliveryThemeDetailsProvider;

    /** @var QtiTestRepositoryInterface */
    private $qtiTestRepository;

    /** @var ThemeServiceAbstract */
    private $themeService;

    /** @var ThemeDiscoverServiceInterface */
    private $themeDiscoverService;

    public function __construct(
        Ontology $ontology,
        DeliveryThemeDetailsProvider $deliveryThemeDetailsProvider,
        QtiTestRepositoryInterface $qtiTestRepository,
        ThemeServiceAbstract $themeService
    ) {
        $this->ontology = $ontology;
        $this->deliveryThemeDetailsProvider = $deliveryThemeDetailsProvider;
        $this->qtiTestRepository = $qtiTestRepository;
        $this->themeService = $themeService;
    }

    public function setThemeDiscoverService(ThemeDiscoverServiceInterface $themeDiscoverService): self
    {
        $this->themeDiscoverService = $themeDiscoverService;

        return $this;
    }

    /**
     * @throws ThemeAutoSetNotSupported
     */
    public function setThemeByDelivery(string $deliveryUri): void
    {
        $delivery = $this->ontology->getResource($deliveryUri);
        $originDeliveryId = $this->getOriginDeliveryId($delivery);

        if (!empty($originDeliveryId)) {
            throw new ThemeAutoSetNotSupported(
                sprintf(
                    'Cannot auto-set theme for remote publishing [delivery:%s, original: %s]',
                    $deliveryUri,
                    $originDeliveryId
                )
            );
        }

        $currentThemeId = $this->deliveryThemeDetailsProvider->getDeliveryThemeIdFromDb($deliveryUri);

        if (!empty($currentThemeId)) {
            throw new ThemeAutoSetNotSupported(
                sprintf(
                    'Cannot auto-set theme cause theme "%s" is already set for delivery "%s"',
                    $currentThemeId,
                    $deliveryUri
                )
            );
        }

        $themeId = isset($this->themeDiscoverService)
            ? $this->themeDiscoverService->discoverByDelivery($deliveryUri)
            : $this->discoverThemeId($deliveryUri);

        $delivery->setPropertyValue(
            $delivery->getProperty(DeliveryThemeDetailsProvider::DELIVERY_THEME_ID_URI),
            $themeId
        );
    }

    private function discoverThemeId(string $deliveryUri): string
    {
        $test = $this->qtiTestRepository->findByDelivery($deliveryUri);

        if (!$test) {
            throw new ThemeAutoSetNotSupported(
                sprintf(
                    'Cannot auto-set theme cause delivery "%s" does not have a test',
                    $deliveryUri
                )
            );
        }

        $language = $test->getLanguage();

        if (empty($language)) {
            throw new ThemeAutoSetNotSupported(
                sprintf(
                    'Cannot auto-set theme cause test "%s" does not have a language',
                    $test->getUri()
                )
            );
        }

        $themeId = $this->themeService->getFirstThemeIdByLanguage($language)
            ?? $this->themeService->getCurrentThemeId();

        if ($themeId === null) {
            throw new ThemeAutoSetNotSupported(
                sprintf(
                    'Cannot auto-set theme cause there is not theme associated for language "%s"',
                    $language
                )
            );
        }

        return $themeId;
    }

    private function getOriginDeliveryId(core_kernel_classes_Resource $delivery): string
    {
        $propertyValue = $delivery->getOnePropertyValue($delivery->getProperty(self::PUBLISHING_ORIGINAL_ID));

        return $propertyValue ? $propertyValue->getUri() : '';
    }
}
