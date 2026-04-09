<?php

declare(strict_types=1);

namespace oat\taoDeliveryRdf\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\tao\model\resources\relation\service\ResourceRelationServiceProxy;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoDeliveryRdf\model\Resource\Service\DeliveryRdfRelationService;
use oat\taoDeliveryRdf\scripts\install\RegisterDeliveryRdfRelationsService;

/**
 * Auto-generated Migration: Please modify to your needs!
 *
 * phpcs:disable Squiz.Classes.ValidClassName
 */
final class Version202604081246201286_taoDeliveryRdf extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Register delivery RDF relation fallback service';
    }

    public function up(Schema $schema): void
    {
        $this->runAction(new RegisterDeliveryRdfRelationsService());
    }

    public function down(Schema $schema): void
    {
        $resourceRelationService = $this->getServiceManager()->get(ResourceRelationServiceProxy::SERVICE_ID);
        $resourceRelationService->removeService('delivery_rdf', DeliveryRdfRelationService::class);

        $this->getServiceManager()->register(ResourceRelationServiceProxy::SERVICE_ID, $resourceRelationService);
    }
}
