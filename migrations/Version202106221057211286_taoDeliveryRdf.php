<?php

declare(strict_types=1);

namespace oat\taoDeliveryRdf\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\oatbox\reporting\Report;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoDeliveryRdf\model\Delivery\Business\Contract\DeliveryNamespaceRegistryInterface;
use oat\taoDeliveryRdf\scripts\tools\SetDeliveryNamespace;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version202106221057211286_taoDeliveryRdf extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Register ' . DeliveryNamespaceRegistryInterface::SERVICE_ID;
    }

    public function up(Schema $schema): void
    {
        $this->addReport(
            $this->propagate(new SetDeliveryNamespace())([])
        );

        $this->addReport(
            Report::createInfo(
                sprintf(
                    'You may run `php index.php \'%s\' -n <delivery_namespace>` in order to override a Delivery namespace',
                    SetDeliveryNamespace::class
                )
            )
        );
    }

    public function down(Schema $schema): void
    {
        $this->getServiceManager()->unregister(DeliveryNamespaceRegistryInterface::SERVICE_ID);

        $this->addReport(
            Report::createSuccess('Unregistered ' . DeliveryNamespaceRegistryInterface::SERVICE_ID)
        );
    }
}
