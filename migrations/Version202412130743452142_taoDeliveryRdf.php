<?php

declare(strict_types=1);

namespace oat\taoDeliveryRdf\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\IrreversibleMigration;
use oat\oatbox\reporting\Report;
use oat\tao\model\menu\SectionVisibilityFilter;
use oat\tao\scripts\tools\migrations\AbstractMigration;

final class Version202412130743452142_taoDeliveryRdf extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Hide deliveries functionality via feature flag';
    }

    public function up(Schema $schema): void
    {
        /** @var SectionVisibilityFilter $sectionVisibilityFilter */
        $sectionVisibilityFilter = $this->getServiceManager()->get(SectionVisibilityFilter::SERVICE_ID);
        $sectionVisibilityFilter->hideSectionByFeatureFlag(
            $sectionVisibilityFilter->createSectionPath(
                [
                    'manage_tests',
                    'test-publish'
                ]
            ),
            'FEATURE_FLAG_DISABLE_DELIVERIES'
        );
        $sectionVisibilityFilter->hideSectionByFeatureFlag(
            'delivery',
            'FEATURE_FLAG_DISABLE_DELIVERIES'
        );
        $this->getServiceManager()->register(SectionVisibilityFilter::SERVICE_ID, $sectionVisibilityFilter);

        $this->addReport(
            Report::createSuccess('Hide deliveries feature based on feature flag FEATURE_FLAG_DISABLE_DELIVERIES')
        );
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration();
    }
}
