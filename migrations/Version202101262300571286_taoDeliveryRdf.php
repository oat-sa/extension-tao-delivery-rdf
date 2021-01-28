<?php

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
    }

    private function getEventManger(): EventManager
    {
        return $this->getServiceManager()->get(EventManager::SERVICE_ID);
    }
}
