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
 */

namespace oat\taoDeliveryRdf\model\assembly;

use Generator;
use taoQtiTest_models_classes_QtiTestService;
use oat\oatbox\filesystem\Directory;
use oat\oatbox\filesystem\File;
use oat\oatbox\service\ConfigurableService;
use tao_models_classes_service_StorageDirectory;

class AssemblyFilesReader extends ConfigurableService implements AssemblyFilesReaderInterface
{
    /**
     * @var CompiledTestConverterService
     */
    private $compiledTestConverter = null;
    /**
         * @param CompiledTestConverterService $compiledTestConverter
         */
    public function setCompiledTestConverter(CompiledTestConverterService $compiledTestConverter)
    {
        $this->compiledTestConverter = $compiledTestConverter;
    }

    /**
     * @param tao_models_classes_service_StorageDirectory $directory
     * @return Generator In format $filePath => StreamInterface
     * @throws \common_Exception
     */
    public function getFiles(tao_models_classes_service_StorageDirectory $directory)
    {
        $iterator = $directory->getFlyIterator(Directory::ITERATOR_FILE | Directory::ITERATOR_RECURSIVE);
        /* @var $file File */
        foreach ($iterator as $file) {
            if ($this->isCompiledTestFile($file) && $this->compiledTestConverter !== null) {
                $file = $this->compiledTestConverter->convert($file, $directory);
                $fileStream = $file->readPsrStream();
                $file->delete();
            } else {
                $fileStream = $file->readPsrStream();
            }

            yield $file->getPrefix() => $fileStream;
        }
    }

    /**
     * @param File $file
     * @return bool
     */
    private function isCompiledTestFile(File $file)
    {
        return strpos($file->getBasename(), taoQtiTest_models_classes_QtiTestService::TEST_COMPILED_FILENAME) !== false;
    }
}
