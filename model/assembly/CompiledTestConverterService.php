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

use InvalidArgumentException;
use tao_models_classes_service_StorageDirectory;
use oat\oatbox\filesystem\File;
use oat\oatbox\service\ConfigurableService;
use oat\taoQtiTest\models\CompilationDataService;
use oat\taoQtiTest\models\PhpCodeCompilationDataService;
use oat\taoQtiTest\models\XmlCompilationDataService;

class CompiledTestConverterService extends ConfigurableService
{
    const SERVICE_ID = 'taoDeliveryRdf/CompiledTestConverterService';

    const OPTION_PHP_COMPILATION_SERVICE = 'php_compilation_service';
    const OPTION_XML_COMPILATION_SERVICE = 'xml_compilation_service';

    /**
     * @var CompilationDataService
     */
    private $phpCompilationService = null;

    /**
     * @var CompilationDataService
     */
    private $xmlCompilationService = null;

    /**
     * CompiledTestConverterService constructor.
     * @param array $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $this->phpCompilationService = $this->getOption(self::OPTION_PHP_COMPILATION_SERVICE);
        if (!$this->phpCompilationService instanceof PhpCodeCompilationDataService) {
            throw new InvalidArgumentException(sprintf('%s option must be an instance of %s', self::OPTION_PHP_COMPILATION_SERVICE,  PhpCodeCompilationDataService::class));
        }

        $this->xmlCompilationService = $this->getOption(self::OPTION_XML_COMPILATION_SERVICE);
        if (!$this->xmlCompilationService instanceof XmlCompilationDataService) {
            throw new InvalidArgumentException(sprintf('%s option must be an instance of %s', self::OPTION_XML_COMPILATION_SERVICE, XmlCompilationDataService::class));
        }
    }

    /**
     * @param File                                          $file
     * @param tao_models_classes_service_StorageDirectory   $directory
     * @return File
     * @throws \common_Exception
     */
    public function convertPhpToXml(File $file, tao_models_classes_service_StorageDirectory $directory)
    {
        $fileName = pathinfo($file->getBasename(), PATHINFO_FILENAME);
        $xmlFile = $directory->getFile($fileName . '.xml');
        if ($xmlFile->exists()) {
            $xmlFile->delete();
        }

        $object = $this->phpCompilationService->readCompilationData($directory, $fileName);
        $this->xmlCompilationService->writeCompilationData($directory, $fileName, $object);

        return $xmlFile;
    }

    /**
     * @param File                                          $file
     * @param tao_models_classes_service_StorageDirectory   $directory
     * @return File
     * @throws \common_Exception
     */
    public function convertXmlToPhp(File $file, tao_models_classes_service_StorageDirectory $directory)
    {
        $fileName = pathinfo($file->getBasename(), PATHINFO_FILENAME);

        $phpPath = $fileName . '.php';
        $phpFile = $directory->getFile($phpPath);

        if ($phpFile->exists()) {
            $phpFile->delete();
        }

        $object = $this->xmlCompilationService->readCompilationData($directory, $fileName);
        $this->phpCompilationService->writeCompilationData($directory, $phpPath, $object);

        return $phpFile;
    }
}