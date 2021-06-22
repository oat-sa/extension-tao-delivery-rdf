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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 *
 * @author Sergei Mikhailov <sergei.mikhailov@taotesting.com>
 */

declare(strict_types=1);

namespace oat\taoDeliveryRdf\model\Delivery\DataAccess;

use InvalidArgumentException;
use oat\tao\model\service\InjectionAwareService;
use oat\taoDeliveryRdf\model\Delivery\Business\Domain\DeliveryNamespace;
use oat\taoDeliveryRdf\model\Delivery\Business\Contract\DeliveryNamespaceRegistryInterface;

final class DeliveryNamespaceRegistry extends InjectionAwareService implements DeliveryNamespaceRegistryInterface
{
    /** @var DeliveryNamespace */
    private $deliveryNamespace;
    /** @var ?DeliveryNamespace */
    private $localNamespace;

    public function __construct(DeliveryNamespace $localNamespace, DeliveryNamespace $deliveryNamespace = null)
    {
        parent::__construct();

        $this->localNamespace    = $localNamespace;
        $this->deliveryNamespace = $deliveryNamespace;

        $this->validate();
    }

    private function validate(): void
    {
        if (null === $this->deliveryNamespace) {
            return;
        }

        if ($this->deliveryNamespace->equals($this->localNamespace)) {
            throw new InvalidArgumentException(
                "Overridden namespace value must be different from a local one, \"$this->deliveryNamespace\" given"
            );
        }
    }

    public function get(): ?DeliveryNamespace
    {
        return $this->deliveryNamespace;
    }
}
