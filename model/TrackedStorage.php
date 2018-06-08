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
namespace oat\taoDeliveryRdf\model;

use tao_models_classes_service_FileStorage;
use oat\tao\model\service\ServiceFileStorage;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
            /**
 * A wrapper of the filestorage that tracks added directories
 *
 * @access public
 * @author Joel Bout, <joel@taotesting.com>
 * @package taoDelivery
 
 */
 class TrackedStorage implements ServiceFileStorage, ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    private $ids = array();

    private $storage;
    
    /**
     * @param boolean $public
     * @return \tao_models_classes_service_StorageDirectory
     */
    public function spawnDirectory($public = false) {
        $directory = $this->getInternalStorage()->spawnDirectory($public);
        $this->ids[] = $directory->getId();
        return $directory;
    }

    /**
     * Returns the id used for this storage
     * @return array
     */
    public function getSpawnedDirectoryIds() {
        return $this->ids;
    }

    /**
     * {@inheritDoc}
     * @see tao_models_classes_service_FileStorage::import()
     */
    public function import($id, $directoryPath)
    {
        $this->ids[] = $id;
        return $this->getInternalStorage()->import($id, $directoryPath);
    }

    /**
     * {@inheritDoc}
     * @see tao_models_classes_service_FileStorage::getDirectoryById()
     */
    public function getDirectoryById($id)
    {
        return $this->getInternalStorage()->getDirectoryById($id);
    }

    /**
     * @return ServiceFileStorage
     */
    protected function getInternalStorage()
    {
        if (is_null($this->storage)) {
            $this->storage = $this->getServiceLocator()->get(ServiceFileStorage::SERVICE_ID);
        }
        return $this->storage;
    }
 }
