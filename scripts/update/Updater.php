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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *
 *
 */
namespace oat\taoDeliveryRdf\scripts\update;

use oat\oatbox\service\ConfigurableService;
use oat\tao\scripts\update\OntologyUpdater;
use oat\tao\model\accessControl\func\AclProxy;
use oat\tao\model\accessControl\func\AccessRule;
use oat\taoDeliveryRdf\install\RegisterDeliveryFactoryService;
use oat\taoDeliveryRdf\model\DeliveryFactory;
use oat\taoDeliveryRdf\model\DeliveryPublishing;
use oat\taoDeliveryRdf\model\GroupAssignment;
use oat\taoDelivery\model\AssignmentService;
use oat\taoDeliveryRdf\install\RegisterDeliveryContainerService;
use oat\taoDeliveryRdf\model\tasks\CompileDelivery;
use oat\taoDeliveryRdf\scripts\RegisterEvents;
use oat\taoDeliveryRdf\model\ContainerRuntime;
use oat\taoDelivery\model\RuntimeService;
use oat\taoTaskQueue\model\TaskLogInterface;

/**
 *
 * @author Joel Bout <joel@taotesting.com>
 */
class Updater extends \common_ext_ExtensionUpdater {

    /**
     * @param string $initialVersion
     * @return string $versionUpdatedTo
     */
    public function update($initialVersion) {

        $currentVersion = $initialVersion;

        //migrate ACL
        if ($currentVersion == '0.1') {
            AclProxy::applyRule(new AccessRule(
                AccessRule::GRANT,
                'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryManagerRole',
                ['ext' => 'taoDeliveryRdf', 'mod' => 'DeliveryMgmt']
            ));
            $currentVersion = '0.2';
            $this->setVersion($currentVersion);
        }

        if ($this->isVersion('0.2')) {
            OntologyUpdater::syncModels();
            AclProxy::applyRule(new AccessRule(
                'grant',
                'http://www.tao.lu/Ontologies/generis.rdf#AnonymousRole',
                array('controller'=>'oat\\taoDeliveryRdf\\controller\\Guest')));

            $currentService = $this->safeLoadService(AssignmentService::CONFIG_ID);
            if (class_exists('taoDelivery_models_classes_AssignmentService', false)
                && $currentService instanceof \taoDelivery_models_classes_AssignmentService) {

                    $assignmentService = new GroupAssignment();
                    $this->getServiceManager()->register(AssignmentService::CONFIG_ID, $assignmentService);
            }

            $this->setVersion('1.0.0');
        }

        if ($this->isVersion('1.0.0')){
            $this->setVersion('1.0.1');
        }

        if ($this->isVersion('1.0.1')) {
            OntologyUpdater::syncModels();
            $this->setVersion('1.1.0');
        }

        $this->skip('1.1.0', '1.4.0');

        if ($this->isVersion('1.4.0')) {
            AclProxy::applyRule(new AccessRule(
                AccessRule::GRANT,
                'http://www.tao.lu/Ontologies/generis.rdf#taoDeliveryRdfManager',
                array('ext' => 'taoDeliveryRdf')));
            $this->setVersion('1.5.0');
        }

        $this->skip('1.5.0', '1.6.3');


        if ($this->isVersion('1.6.3')) {

            OntologyUpdater::syncModels();

            $registerService = new RegisterDeliveryContainerService();
            $registerService([]);

            $this->setVersion('1.7.0');
        }

        $this->skip('1.7.0', '1.8.1');

        if ($this->isVersion('1.8.1')) {

            OntologyUpdater::syncModels();

            $registerEvents = new RegisterEvents();
            $registerEvents->setServiceLocator($this->getServiceManager());
            $registerEvents([]);

            $this->setVersion('1.9.0');
        }

        $this->skip('1.9.0', '1.13.1');

        if ($this->isVersion('1.13.1')) {

            $deliveryFactory = new RegisterDeliveryFactoryService();
            $this->getServiceManager()->propagate($deliveryFactory);
            $deliveryFactory([]);

            $this->setVersion('1.14.0');
        }

        $this->skip('1.14.0', '2.0.1');

        if ($this->isVersion('2.0.1')) {
            OntologyUpdater::syncModels();
            $this->setVersion('2.0.2');
        }

        $this->skip('2.0.2', '3.3.1');

        if ($this->isVersion('3.3.1')) {
            OntologyUpdater::syncModels();
            $this->setVersion('3.4.0');
        }

        $this->skip('3.4.0', '3.6.1');

        if ($this->isVersion('3.6.1')) {
            OntologyUpdater::syncModels();
            $this->getServiceManager()->register(RuntimeService::SERVICE_ID, new ContainerRuntime());
            $this->setVersion('3.7.0');
        }

        $this->skip('3.7.0', '3.9.1');

        if ($this->isVersion('3.9.1')) {
            OntologyUpdater::syncModels();
            $this->setVersion('3.9.2');
        }

        $this->skip('3.9.2', '3.16.0');

        if ($this->isVersion('3.16.0')) {
            $deliveryFactory = $this->getServiceManager()->get(DeliveryFactory::SERVICE_ID);
            $options = $deliveryFactory->getOptions();
            $options[DeliveryFactory::OPTION_INITIAL_PROPERTIES] = [];
            $options[DeliveryFactory::OPTION_INITIAL_PROPERTIES_MAP] = [];
            $deliveryFactory->setOptions($options);
            $this->getServiceManager()->register(DeliveryFactory::SERVICE_ID, $deliveryFactory);
            $this->setVersion('3.17.0');
        }

        $this->skip('3.17.0', '3.17.3');

        if ($this->isVersion('3.17.3')) {
            $this->getServiceManager()->register(
                'taoDeliveryRdf/DeliveryMgmt',
                new \oat\oatbox\config\ConfigurationService([
                    'config' => [
                        'OntologyTreeOrder' => [\oat\generis\model\OntologyRdfs::RDFS_LABEL => 'asc']
                    ]
                ])
            );
            $this->setVersion('3.18.0');
        }

        if ($this->isVersion('3.18.0')) {
            /** @var TaskLogInterface|ConfigurableService $taskLogService */
            $taskLogService = $this->getServiceManager()->get(TaskLogInterface::SERVICE_ID);

            $taskLogService->linkTaskToCategory(CompileDelivery::class, TaskLogInterface::CATEGORY_DELIVERY_COMPILATION);

            $this->getServiceManager()->register(TaskLogInterface::SERVICE_ID, $taskLogService);

            $this->setVersion('3.19.0');
        }

        $this->skip('3.19.0', '3.20.0');

        if ($this->isVersion('3.20.0')) {
            OntologyUpdater::syncModels();
            $this->setVersion('3.21.0');
        }

        $this->skip('3.21.0', '3.23.0');

        if ($this->isVersion('3.23.0')){
            OntologyUpdater::syncModels();
            $this->setVersion('3.23.1');

        }

        $this->skip('3.23.1', '3.28.0');
    }
}
