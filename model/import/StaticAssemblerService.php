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
 * Copyright (c) 2013 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 * 
 */
namespace oat\taoDeliveryRdf\model\import;

use common_Exception;
use oat\generis\model\OntologyAwareTrait;
use oat\taoQtiTest\models\PhpCodeCompilationDataService;
use oat\taoQtiTest\models\XmlCompilationDataService;
use tao_helpers_File;
use tao_models_classes_service_ServiceCall;
use tao_models_classes_service_StorageDirectory;
use ZipArchive;

/**
 * AssemblerService Class.
 *
 * Im- and export a compiled delivery 
 *
 * @access public
 * @author Joel Bout, <joel@taotesting.com>
 * @package taoDelivery
 */
class StaticAssemblerService extends AssemblerService
{
    use OntologyAwareTrait;
    
    /**
     * @param tao_models_classes_service_ServiceCall $serviceCall
     * @return false|string
     */
    protected function getRuntime(tao_models_classes_service_ServiceCall $serviceCall)
    {
        return json_encode($serviceCall);
    }

    /**
     * @param $runtime
     * @return tao_models_classes_service_ServiceCall
     */
    protected function getRuntimeFromString($runtime)
    {
        $data = json_decode($runtime, 1);
        return tao_models_classes_service_ServiceCall::fromJSON($data);
    }

    /**
     * Adding files from the directory to the archive
     * @param tao_models_classes_service_StorageDirectory $directory
     * @param ZipArchive $toArchive
     * @throws common_Exception
     */
    protected function addFilesToZip(tao_models_classes_service_StorageDirectory $directory, ZipArchive $toArchive)
    {
        $files = $directory->getIterator();
        foreach ($files as $file) {
            $fileName = $file;

            if (strpos($file, 'compact-test.php') !== false) {
                $file = $this->getXmlFileFromPhp($directory, $file);
            }
            $source = $this->getFileSource($directory, $file);

            tao_helpers_File::addFilesToZip($toArchive, $source, $directory->getPrefix() . $file);

            if ($fileName !== $file) {
                // remove temporary file
                $directory->getFile($file)->delete();
            }
        }
    }

    /**
     * @param tao_models_classes_service_StorageDirectory $directory
     * @param string $file
     * @return string
     * @throws common_Exception
     */
    protected function getXmlFileFromPhp(tao_models_classes_service_StorageDirectory $directory, $file)
    {
        $file = rtrim($file, '.php');
        $phpDataService = new PhpCodeCompilationDataService();
        $object = $phpDataService->readCompilationData($directory, $file);
        $xmlDataService = new XmlCompilationDataService();
        $xmlDataService->writeCompilationData($directory, $file, $object);

        return $file . '.xml';
    }
}
