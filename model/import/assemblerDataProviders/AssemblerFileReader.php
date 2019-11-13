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
 * Copyright (c) 2019  (original work) Open Assessment Technologies SA;
 *
 * @author Oleksandr Zagovorychev <zagovorichev@gmail.com>
 */

namespace oat\taoDeliveryRdf\model\import\assemblerDataProviders;


use GuzzleHttp\Psr7\Stream;
use oat\oatbox\filesystem\File;
use Psr\Http\Message\StreamInterface;
use tao_models_classes_service_StorageDirectory;

class AssemblerFileReader extends AssemblerFileReaderAbstract
{
    /**
     * @param File $file
     * @param tao_models_classes_service_StorageDirectory $directory
     * @return StreamInterface|Stream
     */
    protected function stream(File $file, tao_models_classes_service_StorageDirectory $directory)
    {
        return $file->readPsrStream();
    }
}
