<?php

declare(strict_types=1);

namespace oat\taoDeliveryRdf\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\oatbox\reporting\Report;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoDeliveryRdf\install\RegisterSectionVisibilityService;

/**
 * Re-applies delivery section hiding. The original 2024 migration is a no-op on
 * fresh installs (all historical migrations are marked executed), and the
 * install action was never registered in the extension manifest.
 *
 * phpcs:disable Squiz.Classes.ValidClassName
 */
final class Version202608191410452142_taoDeliveryRdf extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Hide deliveries UI when FEATURE_FLAG_DISABLE_DELIVERIES is enabled';
    }

    public function up(Schema $schema): void
    {
        $script = new RegisterSectionVisibilityService();
        $script->setServiceLocator($this->getServiceLocator());
        $script([]);

        $this->addReport(
            Report::createSuccess(
                'Deliveries tab and Publish action are hidden when FEATURE_FLAG_DISABLE_DELIVERIES is enabled'
            )
        );
    }

    public function down(Schema $schema): void
    {
        // Intentionally left empty: feature flag paths are merged into runtime config.
    }
}
