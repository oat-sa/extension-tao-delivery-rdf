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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *
 *
 */
namespace oat\taoDeliveryRdf\model\update;

/**
 * 
 * @author Joel Bout <joel@taotesting.com>
 */
class Updater extends \common_ext_ExtensionUpdater {
    
    /**
     * 
     * @param string $currentVersion
     * @return string $versionUpdatedTo
     */
    public function update($initialVersion) {
        
        $currentVersion = $initialVersion;
        
        //migrate ACL
        if ($currentVersion == '0.1') {

            $MngrRole = new \core_kernel_classes_Resource('http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryManagerRole');
            $accessService = \funcAcl_models_classes_AccessService::singleton();
            $accessService->grantModuleAccess($MngrRole, 'taoDeliveryRdf', 'DeliveryMgmt');
            $currentVersion = '0.2';
        }
        
        return $currentVersion;
    }
}
