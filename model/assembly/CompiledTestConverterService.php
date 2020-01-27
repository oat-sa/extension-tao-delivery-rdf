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

use common_Exception;
use tao_models_classes_service_StorageDirectory;
use oat\oatbox\filesystem\File;
use oat\taoQtiTest\models\CompilationDataService;

class CompiledTestConverterService
{
    /**
     * @var CompilationDataService
     */
    private $compilationDataReader = null;
    /**
         * @var CompilationDataService
         */
    private $compilationDataWriter = null;
    /**
         * CompiledTestConverterService constructor.
         * @param CompilationDataService $compilationDataReader
         * @param CompilationDataService $compilationDataWriter
         */
    public function __construct(CompilationDataService $compilationDataReader, CompilationDataService $compilationDataWriter)
    {
        $this->compilationDataReader = $compilationDataReader;
        $this->compilationDataWriter = $compilationDataWriter;
    }

    /**
     * @param File                                          $file
     * @param tao_models_classes_service_StorageDirectory   $directory
     * @return File
     * @throws common_Exception
     */
    public function convert(File $file, tao_models_classes_service_StorageDirectory $directory)
    {
        $fileName = pathinfo($file->getBasename(), PATHINFO_FILENAME);
        $resultFile = $this->getNewFile($directory, $fileName);
        $object = $this->compilationDataReader->readCompilationData($directory, $fileName);
        $this->compilationDataWriter->writeCompilationData($directory, $fileName, $object);
        return $resultFile;
    }

    /**
     * @param tao_models_classes_service_StorageDirectory $directory
     * @param string $outputFileName
     * @return File
     */
    private function getNewFile(tao_models_classes_service_StorageDirectory $directory, $outputFileName)
    {
        $outputFileName .= '.' . $this->compilationDataWriter->getOutputFileType();
        $resultFile = $directory->getFile($outputFileName);
        if ($resultFile->exists()) {
            $resultFile->delete();
        }
        return $resultFile;
    }
}
