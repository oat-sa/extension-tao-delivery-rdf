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

namespace oat\taoDeliveryRdf\model\import\assemblerDataProviders\serviceCallConverters;


use GuzzleHttp\Psr7\Stream;
use oat\oatbox\filesystem\File;
use oat\oatbox\service\ConfigurableService;
use tao_models_classes_service_StorageDirectory;

abstract class AssemblerFileReaderAbstract extends ConfigurableService implements AssemblerFileReaderInterface
{
    /**
     * @var Stream
     */
    protected $stream;

    /**
     * @var File
     */
    protected $file;

    /**
     * Getting content of the readable file
     * @param File $file
     * @param tao_models_classes_service_StorageDirectory $directory
     * @return Stream
     */
    public function getFileStream(File $file, tao_models_classes_service_StorageDirectory $directory) {
        $this->file = $file;
        $this->stream = $this->stream($this->file, $directory);
        return $this->stream;
    }

    /**
     * @param File $file
     * @param tao_models_classes_service_StorageDirectory $directory
     * @return Stream
     */
    abstract protected function stream(File $file, tao_models_classes_service_StorageDirectory $directory);

    /**
     * Keep storage clean
     * (close handlers, remove redundant files)
     * @return void
     */
    public function clean() {
        // clean reader and close stream handler
        $this->stream->close();
        $this->stream = null;
        $this->file = null;
    }

    /**
     * @return File
     */
    public function getFile()
    {
        return $this->file;
    }
}
