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
 * Copyright (c) 2020  (original work) Open Assessment Technologies SA;
 */

namespace oat\taoDeliveryRdf\model\DataStore\Repository;

use core_kernel_classes_Resource;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\taoDeliveryRdf\model\DataStore\DataStoreRepositoryInterface;
use oat\taoQtiTest\models\TestModelService;
use taoTests_models_classes_TestModel;

class DataStoreItemRepository extends ConfigurableService implements DataStoreRepositoryInterface
{
    use OntologyAwareTrait;

    public const OPTION_STORAGE = 'storage';

    /** @var taoTests_models_classes_TestModel */
    private $storage;

    private function getStorage(): taoTests_models_classes_TestModel
    {
        if ($this->storage === null) {
            $this->storage = $this->getServiceLocator()->get(TestModelService::SERVICE_ID);
        }

        return $this->storage;
    }

    public function findByTestId(core_kernel_classes_Resource $test): iterable
    {
        return $this->getStorage()->getItems($test);
    }
}
