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


use common_Exception;
use common_exception_Error;
use GuzzleHttp\Psr7\Stream;
use oat\oatbox\filesystem\File;
use oat\taoQtiTest\models\CompilationDataService;
use oat\taoQtiTest\models\PhpCodeCompilationDataService;
use oat\taoQtiTest\models\XmlCompilationDataService;
use Psr\Http\Message\StreamInterface;
use tao_models_classes_service_StorageDirectory;

/**
 * Avoid to use php files
 * Class StaticAssemblerFileReader
 * @package oat\taoDeliveryRdf\model\import\dataProviders
 */
class XmlAssemblerFileReader extends AssemblerFileReaderAbstract
{
    /**
     * Path to xml file
     * @var File
     */
    private $xmlFile;

    const OPTION_PHP_CODE_COMPILATION_DATA_SERVICE = 'phpCodeCompilationDataService';
    const OPTION_XML_CODE_COMPILATION_DATA_SERVICE = 'xmlCodeCompilationDataService';

    /**
     * @var CompilationDataService
     */
    private $phpCodeCompilationDataService;

    /**
     * @var CompilationDataService
     */
    private $xmlCodeCompilationDataService;

    /**
     * @return CompilationDataService
     * @throws common_exception_Error
     */
    private function getPhpCodeCompilationDataService()
    {
        if (!$this->phpCodeCompilationDataService) {
            if ($this->hasOption(self::OPTION_PHP_CODE_COMPILATION_DATA_SERVICE)) {
                $this->phpCodeCompilationDataService = $this->getOption(self::OPTION_PHP_CODE_COMPILATION_DATA_SERVICE);
                if (!is_a($this->phpCodeCompilationDataService, CompilationDataService::class)) {
                    throw new common_exception_Error('Incorrect configuration for the PhpCompilationDataService');
                }
            } else {
                // default data service
                $this->phpCodeCompilationDataService = new PhpCodeCompilationDataService();
            }
        }
        return $this->phpCodeCompilationDataService;
    }

    /**
     * @return CompilationDataService
     * @throws common_exception_Error
     */
    private function getXmlCodeCompilationDataService()
    {
        if (!$this->xmlCodeCompilationDataService) {
            if ($this->hasOption(self::OPTION_XML_CODE_COMPILATION_DATA_SERVICE)) {
                $this->xmlCodeCompilationDataService = $this->getOption(self::OPTION_XML_CODE_COMPILATION_DATA_SERVICE);
                if (!is_a($this->xmlCodeCompilationDataService, CompilationDataService::class)) {
                    throw new common_exception_Error('Incorrect configuration for the XmlCompilationDataService');
                }
            } else {
                // default data service
                $this->xmlCodeCompilationDataService = new XmlCompilationDataService();
            }
        }
        return $this->xmlCodeCompilationDataService;
    }

    /**
     * @param File $file
     * @param tao_models_classes_service_StorageDirectory $directory
     * @return Stream|StreamInterface
     * @throws common_Exception
     */
    protected function stream(File $file, tao_models_classes_service_StorageDirectory $directory)
    {
        if (strpos($file->getPrefix(), 'compact-test.php') !== false) {
            $this->xmlFile = $this->getXmlFileFromPhp($file, $directory);
            $this->file = $this->xmlFile;
        }

        return $this->file->readPsrStream();
    }

    /**
     * @param File $file
     * @param tao_models_classes_service_StorageDirectory $directory
     * @return File
     * @throws common_Exception
     */
    private function getXmlFileFromPhp(File $file, tao_models_classes_service_StorageDirectory $directory)
    {
        $fileName = $file->getBasename();
        $fileName = trim($fileName, '.php');

        $xmlPath = $fileName . '.xml';
        $file = $directory->getFile($xmlPath);
        // renew xml from php if exists
        if ($file->exists()) {
            $file->delete();
        }

        $object = $this->getPhpCodeCompilationDataService()->readCompilationData($directory, $fileName);
        $this->getXmlCodeCompilationDataService()->writeCompilationData($directory, $fileName, $object);

        return $directory->getFile($xmlPath);
    }

    public function clean()
    {
        parent::clean();
        if ($this->xmlFile) {
            $this->xmlFile->delete();
            $this->xmlFile = null;
        }
    }
}
