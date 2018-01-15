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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoDeliveryRdf\scripts\install;

use oat\oatbox\extension\InstallAction;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\search\dataProviders\DataProvider;
use oat\tao\model\search\dataProviders\OntologyDataProvider;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoDeliveryRdf\model\tasks\CompileDelivery;
use oat\taoTaskQueue\model\TaskLogInterface;

/**
 * Class SetUpOntologyDataProvider
 * @package oat\taoDeliveryRdf\scripts\install
 */
class SetUpOntologyDataProvider extends InstallAction
{
    public function __invoke($params)
    {
        $ontologyDataProvider = $this->getServiceLocator()->get(OntologyDataProvider::SERVICE_ID);
        $options = $ontologyDataProvider->getOptions();
        $options[DataProvider::INDEXES_MAP_OPTION][DeliveryAssemblyService::CLASS_URI] = [
                DataProvider::FIELDS_OPTION => 'label'
            ];
        $ontologyDataProvider->setOptions($options);
        $this->getServiceManager()->register(OntologyDataProvider::SERVICE_ID, $ontologyDataProvider);

        return \common_report_Report::createSuccess('Setup Ontology data provider');
    }
}