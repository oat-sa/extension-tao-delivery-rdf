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

namespace oat\taoDeliveryRdf\model\guest;

use oat\generis\model\GenerisRdf;
use oat\oatbox\user\User;
use oat\tao\model\TaoOntology;

/**
 * Virtual test-taker
 */
class GuestTestUser implements User
{
    protected $uri;

    public function __construct()
    {
        $this->uri = \common_Utils::getNewUri();
    }

    public function getIdentifier(): string
    {
        return $this->uri;
    }

    public function getPropertyValues($property): array
    {
        if ($property === GenerisRdf::PROPERTY_USER_UILG && defined('DEFAULT_ANONYMOUS_INTERFACE_LANG')) {
            return [DEFAULT_ANONYMOUS_INTERFACE_LANG];
        }

        return [];
    }

    public function getRoles(): array
    {
        return [
            TaoOntology::PROPERTY_INSTANCE_ROLE_DELIVERY => TaoOntology::PROPERTY_INSTANCE_ROLE_DELIVERY,
            GenerisRdf::INSTANCE_ROLE_ANONYMOUS => GenerisRdf::INSTANCE_ROLE_ANONYMOUS,
            TaoOntology::PROPERTY_INSTANCE_ROLE_BASE_USER => TaoOntology::PROPERTY_INSTANCE_ROLE_BASE_USER
        ];
    }
}
