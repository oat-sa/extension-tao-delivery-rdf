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

namespace oat\taoDeliveryRdf\model\DataStore;

use oat\oatbox\service\ConfigurableService;
use ZipArchive;

class ProcessDataService extends ConfigurableService
{

    private const DELIVERY_META_DATA_JSON = 'deliveryMetaData.json';
    private const TEST_META_DATA_JSON = 'testMetaData.json';
    private const ITEM_META_DATA_JSON = 'itemMetaData.json';
    public const  OPTION_ZIP_ARCHIVE_SERVICE = 'zipArchive';


    public function process(string $zipFile, array $metaData): void
    {
        $zipArchive = $this->getZipArchive();

        $zipArchive->open($zipFile);

        $this->saveMetaData($zipArchive, self::DELIVERY_META_DATA_JSON, json_encode($metaData['deliveryMetaData']));
        $this->saveMetaData($zipArchive, self::TEST_META_DATA_JSON, json_encode($metaData['testMetaData']));
        $this->saveMetaData($zipArchive, self::ITEM_META_DATA_JSON, json_encode($metaData['itemMetaData']));

        $zipArchive->close();
    }

    private function saveMetaData(ZipArchive $zipFile, string $fileNameToAdd, string $content): void
    {
        $zipFile->addFromString($fileNameToAdd, $content);
    }

    private function getZipArchive(): ZipArchive
    {
        $zipArchive = $this->getOption(self::OPTION_ZIP_ARCHIVE_SERVICE);

        return $zipArchive ?? new ZipArchive();
    }
}
