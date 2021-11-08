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

namespace oat\taoDeliveryRdf\scripts\e2e;

use core_kernel_classes_Class as RdfClass;
use core_kernel_classes_Resource as RdfResource;
use LogicException;
use oat\generis\model\GenerisRdf;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\extension\AbstractAction;
use oat\oatbox\reporting\Report;
use oat\oatbox\reporting\ReportInterface;
use oat\tao\model\TaoOntology;
use oat\tao\scripts\tools\e2e\models\E2eConfigDriver;
use oat\tao\scripts\tools\e2e\PrepareEnvironment;
use oat\taoDeliveryRdf\model\DeliveryFactory;
use oat\taoDeliveryRdf\model\GroupAssignment;
use oat\taoDeliveryRdf\scripts\e2e\Context\E2eTestTakerContext;
use oat\taoGroups\models\GroupsService;
use oat\taoTestTaker\models\CrudService;
use Ramsey\Uuid\Uuid;
use stdClass;
use taoQtiTest_models_classes_QtiTestService;

class BuildE2eConfiguration extends AbstractAction
{
    use OntologyAwareTrait;

    /**
     * @var E2eTestTakerContext
     */
    private $testTakerContext;

    private $e2eConfig;

    public function __invoke($params)
    {
        $this->e2eConfig = new stdClass();
        $report = Report::createInfo('RdfDeliveries Configuration Processes');

        $report->add($this->createUser());
        $report->add($this->importTestsAndCompile());

        $this->persistConfig($this->e2eConfig);

        return $report;
    }

    private function importTestsAndCompile(): ReportInterface
    {
        $report = Report::createInfo('Importing/Compiling Tests');

        $group = $this->createGroup('E2EGroup');

        if ($this->getTestTakerContext()) {
            $this->getGroupService()->addUser(
                $group,
                $this->getTestTakerContext()->getParameter(E2eTestTakerContext::PARAM_TEST_TAKER_RESOURCE)
            );
        }

        if (!is_readable($this->getManifestPath())) {
            throw new LogicException('Fixtures manifest for %s is not readable', __CLASS__);
        }
        $manifest = json_decode(file_get_contents($this->getManifestPath()));
        $targetTestClass = $this->getClass(TaoOntology::CLASS_URI_TEST)->createSubClass($this->createLabel('E2ETests'));

        $targetDeliveryClass = $this->getClass(TaoOntology::CLASS_URI_ASSEMBLED_DELIVERY)->createSubClass(
            $this->createLabel('E2EDeliveries')
        );

        $deliveryDTO = new stdClass();
        foreach ($manifest->testPackages as $package) {
            $key = $package->key;
            $path = sprintf('%s/%s', dirname($this->getManifestPath()), $package->package);
            $this->getLogger()->debug(sprintf('Importing test package %s', $path));
            $importReports = $this->getTestImportService()->importMultipleTests($targetTestClass, $path);

            if ($importReports->getType() === ReportInterface::TYPE_SUCCESS) {
                foreach ($importReports as $importReport) {
                    $test = $this->getResource($importReport->getData()->rdfsResource->getUri());
                    $delivery = $this->createDelivery($targetDeliveryClass, $test);
                    $group->setPropertyValue(
                        $this->getProperty(GroupAssignment::PROPERTY_GROUP_DELIVERY),
                        $delivery->getUri()
                    );
                    $deliveryDTO->{$key} = $delivery->getUri();
                }
            }

            $report->add(new Report($importReports->getType(), sprintf('%s - %s', $key, $importReports->getMessage())));
        }
        $this->e2eConfig->deliveryIds = $deliveryDTO;

        return $report;
    }

    private function getManifestPath(): string
    {
        return 'taoQtiTest/views/cypress/fixtures/testPackages/manifest.json';
    }

    private function createUser(): ReportInterface
    {
        $report = Report::createInfo('TesTaker Creation');

        $password = Uuid::uuid4()->toString();
        $login = $this->createLabel('E2ETestTaker');

        $testTakerResource = $this->getTestTakerService()->createFromArray([
                GenerisRdf::PROPERTY_USER_LOGIN => $login,
                GenerisRdf::PROPERTY_USER_PASSWORD => $password,
            ]
        );
        $testTakerResource->setLabel($login);

        $this->testTakerContext = new E2eTestTakerContext([
            E2eTestTakerContext::PARAM_TEST_TAKER_LOGIN => $login,
            E2eTestTakerContext::PARAM_TEST_TAKER_PASSWORD => $password,
            E2eTestTakerContext::PARAM_TEST_TAKER_RESOURCE => $testTakerResource,
        ]);

        $this->e2eConfig->testTakerUser = $this->testTakerContext->getParameter(
            E2eTestTakerContext::PARAM_TEST_TAKER_LOGIN
        );
        $this->e2eConfig->testTakerPass = $this->testTakerContext->getParameter(
            E2eTestTakerContext::PARAM_TEST_TAKER_PASSWORD
        );

        return $report;
    }

    private function createDelivery(RdfClass $targetClass, RdfResource $test): RdfResource
    {
        $deliveryCreationReport = $this->getDeliveryFactory()->create(
            $targetClass,
            $test,
            sprintf('Delivery for e2e %s', $test->getLabel())
        );

        if ($deliveryCreationReport->containsError()) {
            $this->logError('Unable to compile delivery' . $test->getUri());
            throw new LogicException($deliveryCreationReport->getMessage());
        }

        return $deliveryCreationReport->getData();
    }

    private function getTestTakerContext(): ?E2eTestTakerContext
    {
        return $this->testTakerContext;
    }

    private function createLabel(string $label): string
    {
        return $label . '_' . time();
    }

    private function createGroup(string $label): RdfResource
    {
        $groupClass = $this->getClass(TaoOntology::CLASS_URI_GROUP);
        return $groupClass->createInstance($this->createLabel($label));
    }

    private function persistConfig($config): void
    {
        $this->getConfigWriter()->setConfigPath((new PrepareEnvironment())->getConfigPath())->append($config);
    }

    private function getDeliveryFactory(): DeliveryFactory
    {
        return $this->getServiceLocator()->getContainer()->get(DeliveryFactory::SERVICE_ID);
    }

    private function getTestImportService(): taoQtiTest_models_classes_QtiTestService
    {
        return $this->getServiceLocator()->getContainer()->get(taoQtiTest_models_classes_QtiTestService::class);
    }

    private function getConfigWriter(): E2eConfigDriver
    {
        return new E2eConfigDriver();
    }

    private function getGroupService(): GroupsService
    {
        return GroupsService::singleton();
    }

    private function getTestTakerService(): CrudService
    {
        return CrudService::singleton();
    }
}
