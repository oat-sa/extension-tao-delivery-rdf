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

namespace oat\taoDeliveryRdf\model\import\assemblerDataProviders\assemblerFileReaders;


use common_exception_Error;
use GuzzleHttp\Psr7\Stream;
use oat\oatbox\filesystem\File;
use tao_models_classes_service_StorageDirectory;

class AssemblerFileReaderCollection extends AssemblerFileReaderAbstract
{
    /**
     * List of readers
     * @var string stored array of AssemblerFileReaderInterface[]
     */
    const OPTION_FILE_READERS = 'fileReaders';

    /**
     * @throws common_exception_Error
     * @return array
     */
    public function getReaders()
    {
        $readers = $this->getOption(self::OPTION_FILE_READERS);
        if (!is_array($readers) || !count($readers)) {
            throw new common_exception_Error('Readers are not configured for the AssemblerFileReaderCollection');
        }

        foreach ($readers as $reader) {
            if (!($reader instanceof AssemblerFileReaderInterface)) {
                $readerStr = is_object($reader) ? get_class($reader) : $reader;
                throw new common_exception_Error(
                    sprintf('All readers of the AssemblerFileReaderCollection have to implement interface %s, reader: %s incorrect', AssemblerFileReaderInterface::class, (string) $readerStr));
            }
        }

        return $readers;
    }

    /**
     * @param File $file
     * @param tao_models_classes_service_StorageDirectory $directory
     * @return Stream|null
     * @throws common_exception_Error
     */
    protected function stream(File $file, tao_models_classes_service_StorageDirectory $directory)
    {
        $stream = null;
        /** @var AssemblerFileReaderAbstract $reader */
        foreach ($this->getReaders() as $reader) {
            $this->propagate($reader);
            $stream = $reader->getFileStream($file, $directory);
            $this->file = $reader->getFile();
        }
        return $stream;
    }

    /**
     * @throws common_exception_Error
     */
    public function clean()
    {
        /** @var AssemblerFileReaderAbstract $reader */
        foreach ($this->getReaders() as $reader) {
            $reader->clean();
        }
    }

}
