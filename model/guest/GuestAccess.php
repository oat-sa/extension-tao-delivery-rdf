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
namespace oat\taoDeliveryRdf\model\guest;

use oat\oatbox\Configurable;
use oat\tao\model\entryPoint\Entrypoint;

class GuestAccess extends Configurable implements Entrypoint
{

    public function getId() {
        return 'guestaccess';
    }
    
    public function getTitle() {
        return __('Guest access');
    }
    
    public function getLabel() {
        return __('TAO Guest Access');
    }
    
    public function getDescription() {
        return __('Access deliveries as a guest');
    }
    
    public function getUrl() {
        return _url("guest", "Guest", "taoDeliveryRdf");
    }

}