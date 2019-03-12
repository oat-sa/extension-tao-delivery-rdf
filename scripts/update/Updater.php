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

use oat\oatbox\event\EventManager;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\taskQueue\TaskLogInterface;
use oat\tao\model\user\TaoRoles;
use oat\tao\scripts\update\OntologyUpdater;
use oat\tao\model\accessControl\func\AclProxy;
use oat\tao\model\accessControl\func\AccessRule;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionReactivated;
use oat\taoDeliveryRdf\helper\SessionStateHelper;
use oat\taoDeliveryRdf\install\RegisterDeliveryFactoryService;
use oat\taoDeliveryRdf\model\AssemblerServiceInterface;
use oat\taoDeliveryRdf\model\Delete\DeliveryDeleteService;
use oat\taoDeliveryRdf\model\DeliveryAssemblyWrapperService;
use oat\taoDeliveryRdf\model\DeliveryFactory;
use oat\taoDeliveryRdf\install\RegisterDeliveryContainerService;
use oat\taoDeliveryRdf\model\import\AssemblerService;
use oat\taoDeliveryRdf\model\tasks\CompileDelivery;
use oat\taoDeliveryRdf\scripts\RegisterEvents;
use oat\taoDeliveryRdf\model\ContainerRuntime;
use oat\taoDelivery\model\RuntimeService;

/**
 ** @author Joel Bout <joel@taotesting.com>
 */
class Updater extends \common_ext_ExtensionUpdater {

    /**
     * @param $initialVersion
     * @return string|void
     * @throws \common_Exception
     * @throws \common_exception_Error
     */
    public function update($initialVersion) {

        if ($this->isBetween('0.0.0', '1.1.0')) {
            throw new \common_exception_NotImplemented('Updates from versions prior to Tao 3.1 are not longer supported, please update to Tao 3.1 first');
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

        $this->skip('3.18.0', '3.20.0');

        if ($this->isVersion('3.20.0')) {
            OntologyUpdater::syncModels();
            $this->setVersion('3.21.0');
        }

        $this->skip('3.21.0', '3.23.0');

        if ($this->isVersion('3.23.0')){
            OntologyUpdater::syncModels();
            $this->setVersion('3.23.1');

        }

        $this->skip('3.23.1', '3.29.0');

        if ($this->isVersion('3.29.0')) {
            $deliveryAssemblerWrapper = new DeliveryAssemblyWrapperService();
            $this->getServiceManager()->register(DeliveryAssemblyWrapperService::SERVICE_ID, $deliveryAssemblerWrapper);

            $this->setVersion('4.0.0');
        }

        $this->skip('4.0.0', '4.6.0');

        if ($this->isVersion('4.6.0') || $this->isVersion('4.6.0.1')) {
            AclProxy::applyRule(new AccessRule('grant', TaoRoles::REST_PUBLISHER, array('ext'=>'taoDeliveryRdf', 'mod' => 'RestDelivery')));
            AclProxy::applyRule(new AccessRule('grant', TaoRoles::REST_PUBLISHER, array('ext'=>'taoDeliveryRdf', 'mod' => 'RestTest')));
            $this->setVersion('4.7.0');
        }

        $this->skip('4.7.0', '4.14.0');

        if ($this->isVersion('4.14.0')) {
            $serviceManager = $this->getServiceManager();

            $defaultFileSystemId = 'deliveryAssemblyExport';
            /** @var FileSystemService $service */
            $service = $serviceManager->get(FileSystemService::SERVICE_ID);
            $service->createFileSystem($defaultFileSystemId);
            $serviceManager->register(FileSystemService::SERVICE_ID, $service);

            $assemblerService = new AssemblerService([AssemblerService::OPTION_FILESYSTEM_ID => $defaultFileSystemId]);
            $serviceManager->register(AssemblerServiceInterface::SERVICE_ID, $assemblerService);

            $this->setVersion('5.0.0');
        }

        if ($this->isVersion('5.0.0')) {
            // To avoid breaking the updater, this part has been moved in advance

            /** @var TaskLogInterface|ConfigurableService $taskLogService */
            $taskLogService = $this->getServiceManager()->get(TaskLogInterface::SERVICE_ID);

            $taskLogService->linkTaskToCategory(CompileDelivery::class, TaskLogInterface::CATEGORY_DELIVERY_COMPILATION);

            $this->getServiceManager()->register(TaskLogInterface::SERVICE_ID, $taskLogService);

            $this->setVersion('5.1.0');
        }

        $this->skip('5.1.0', '5.2.1');

        if ($this->isVersion('5.2.1')){
            if (!$this->getServiceManager()->has(DeliveryDeleteService::SERVICE_ID)){
                $deleteService = new DeliveryDeleteService([
                    'deleteDeliveryDataServices' => array(
                        'taoDeliveryRdf/DeliveryAssemblyWrapper'
                    )
                ]);

                $this->getServiceManager()->register(DeliveryDeleteService::SERVICE_ID, $deleteService);
            }

            $this->setVersion('5.2.2');
        }

        $this->skip('5.2.2', '6.0.0');

        if ($this->isVersion('6.0.0')) {
            $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
            $eventManager->attach(DeliveryExecutionReactivated::class, [SessionStateHelper::class, 'onExecutionReactivation']);
            $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
            $this->setVersion('7.0.0');
        }

        $this->skip('7.0.0', '7.4.6');
    }
}
