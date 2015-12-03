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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 * 
 */
namespace oat\taoDeliveryRdf\install;

use oat\tao\scripts\update\OntologyUpdater;
use oat\tao\model\accessControl\func\AclProxy;
use oat\tao\model\accessControl\func\AccessRule;
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
        
        if ($this->isVersion('0.1')) {
            OntologyUpdater::syncModels();
            AclProxy::applyRule(new AccessRule(
                'grant',
                'http://www.tao.lu/Ontologies/generis.rdf#AnonymousRole',
                array('controller'=>'oat\\taoDeliveryRdf\\controller\\Guest')));
            $this->setVersion('1.0.0');
        }
    }
}