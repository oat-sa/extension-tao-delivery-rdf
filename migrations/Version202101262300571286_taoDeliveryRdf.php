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

namespace oat\taoDeliveryRdf\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\oatbox\event\EventManager;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoDeliveryRdf\model\DataStore\DeliveryMetadataListener;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;

final class Version202101262300571286_taoDeliveryRdf extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Registering DataStoreService for metaData processing';
    }

    public function up(Schema $schema): void
    {
        $eventManager = $this->getEventManger();
        $this->getServiceLocator()->register(
            DeliveryMetadataListener::SERVICE_ID,
            new DeliveryMetadataListener()
        );
        $eventManager->attach(
            DeliveryCreatedEvent::class,
            [DeliveryMetadataListener::class, 'whenDeliveryIsPublished']
        );

        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
    }

    public function down(Schema $schema): void
    {
        $eventManager = $this->getEventManger();
        $this->getServiceLocator()->unregister(
            DeliveryMetadataListener::SERVICE_ID
        );
        $eventManager->detach(
            DeliveryCreatedEvent::class,
            [DeliveryMetadataListener::class, 'whenDeliveryIsPublished']
        );

        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
    }

    private function getEventManger(): EventManager
    {
        return $this->getServiceManager()->get(EventManager::SERVICE_ID);
    }
}
