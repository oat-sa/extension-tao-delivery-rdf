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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 *
 */

declare(strict_types = 1);

namespace oat\taoDeliveryRdf\model\Delete;

use oat\oatbox\extension\AbstractAction;

/**
 * Usage example:
 * sudo -u www-data php index.php '\oat\taoDeliveryRdf\model\Delete\DeliveryDeleteAction' 'http://tao.local/tao.rdf#i5ec66a0a167263604f8a6c91908fa8ab3'
 * Class DeliveryDeleteAction
 * @package oat\taoDeliveryRdf\model\Delete
 */
class DeliveryDeleteAction extends AbstractAction
{
    /**
     * @param $params
     * @return \common_report_Report
     * @throws \Exception
     */
    public function __invoke($params)
    {
        if (!isset($params[0])) {
            throw new \common_exception_MissingParameter('Missing `deliveryId` as a first parameter in ' . static::class);
        }
        $task = $this->propagate(new DeliveryDeleteTask());
        return $task(['deliveryId' => $params[0]]);
    }
}
