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
 *
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\scripts\tools;

use Exception;
use oat\generis\model\data\import\RdfImporter;
use oat\oatbox\extension\script\ScriptAction;
use oat\oatbox\reporting\Report;
use oat\oatbox\service\ServiceManager;

/**
 * Usages:
 *
 * php index.php "oat\taoDeliveryRdf\scripts\tools\AddLanguageSupportForDelivery"
 */
class AddLanguageSupportForDelivery extends ScriptAction
{
    private const RDF_FILE_PATH = ROOT_PATH . 'taoDeliveryRdf' . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'ontology' . DIRECTORY_SEPARATOR . 'taodelivery_language.rdf';

    protected function provideOptions(): array
    {
        return [];
    }

    protected function provideDescription(): array
    {
        return [];
    }

    protected function run(): Report
    {
        $serviceManager = ServiceManager::getServiceManager();

        try {
            $importer = $serviceManager->get(RdfImporter::class);
            $importer->importFile(self::RDF_FILE_PATH);
        } catch (Exception $exception) {
            return Report::createError(
                'Failed to run the script.',
                null,
                [Report::createInfo($exception->getMessage())]
            );
        }

        return Report::createSuccess('Language support for deliveries has been added successfully.');
    }
}
