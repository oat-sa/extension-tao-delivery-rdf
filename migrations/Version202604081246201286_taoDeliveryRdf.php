<?php

declare(strict_types=1);

namespace oat\taoDeliveryRdf\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\tao\model\resources\relation\service\ResourceRelationServiceProxy;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoDeliveryRdf\model\Resource\Service\DeliveryRelationService;
use oat\taoDeliveryRdf\model\Resource\Service\TestRelationService;
use oat\taoDeliveryRdf\scripts\install\RegisterResourceRelationsService;

/**
 * Auto-generated Migration: Please modify to your needs!
 *
 * phpcs:disable Squiz.Classes.ValidClassName
 */
final class Version202604081246201286_taoDeliveryRdf extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Register directional delivery/test relation services';
    }

    public function up(Schema $schema): void
    {
        $this->runAction(new RegisterResourceRelationsService());
    }

    public function down(Schema $schema): void
    {
        $resourceRelationService = $this->getServiceManager()->get(ResourceRelationServiceProxy::SERVICE_ID);
        $resourceRelationService->removeService('test_delivery', TestRelationService::class);
        $resourceRelationService->removeService('delivery_test', DeliveryRelationService::class);

        $this->getServiceManager()->register(ResourceRelationServiceProxy::SERVICE_ID, $resourceRelationService);
    }
}
