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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoDeliveryRdf\install;

use oat\generis\model\data\import\RdfImporter;
use oat\oatbox\service\ServiceManager;

/**
 * Installation action that register the rdf implementation for the delivery language
 */
class RegisterDeliveryLanguageService extends \common_ext_action_InstallAction
{
    /**
     * @param $params
     */
    public function __invoke($params)
    {
        $serviceManager = ServiceManager::getServiceManager();

        $importer = $serviceManager->get(RdfImporter::class);
        $importer->importFile(
            __DIR__ . DIRECTORY_SEPARATOR . 'ontology' . DIRECTORY_SEPARATOR . 'taodelivery_language.rdf'
        );
    }
}
