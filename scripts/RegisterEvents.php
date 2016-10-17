<?php
namespace oat\taoDeliveryRdf\scripts;

use oat\oatbox\event\EventManager;

class RegisterEvents extends \common_ext_action_InstallAction
{
    public function __invoke($params)
    {
        $eventManager = $this->getServiceManager()->get(EventManager::CONFIG_ID);

        $eventManager->attach(
            'oat\\taoDeliveryRdf\\model\\event\\DeliveryCreatedEvent',
            ['oat\\taoDeliveryRdf\\model\\DeliveryFeaturesSettings', 'enableDefaultFeatures']
        );

        $this->getServiceManager()->register(EventManager::CONFIG_ID, $eventManager);

        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, 'Events attached');
    }
}