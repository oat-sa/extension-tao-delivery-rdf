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


use common_Exception;
use GuzzleHttp\Psr7\Stream;
use oat\oatbox\filesystem\File;
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
        $phpDataService = new PhpCodeCompilationDataService();
        $object = $phpDataService->readCompilationData($directory, $fileName);
        $xmlDataService = new XmlCompilationDataService();
        $xmlDataService->writeCompilationData($directory, $fileName, $object);

        return $directory->getFile($fileName . '.xml');
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
