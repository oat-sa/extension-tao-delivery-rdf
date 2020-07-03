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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA
 *
 */

namespace oat\taoDeliveryRdf\model\event;

use core_kernel_classes_Resource;
use oat\tao\model\webhooks\WebhookSerializableEventInterface;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;

class DeliveryCreatedEvent extends AbstractDeliveryEvent implements WebhookSerializableEventInterface
{
    /**
     * @var core_kernel_classes_Resource
     */
    private $delivery;

    /**
     * @param core_kernel_classes_Resource $delivery
     */
    public function __construct(core_kernel_classes_Resource $delivery)
    {
        $this->deliveryUri = $delivery->getUri();
        $this->delivery = $delivery;
    }

    /**
     * Return a unique name for this event
     * @see \oat\oatbox\event\Event::getName()
     */
    public function getName()
    {
        return self::class;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'delivery' => $this->delivery->getUri(),
        ];
    }

    /**
     * @return string
     */
    public function getWebhookEventName()
    {
        return 'DeliveryCreatedEvent';
    }

    /**
     * @return array
     */
    public function serializeForWebhook(): array
    {
        $testUri = null;
        try {
            $testProperty = new \core_kernel_classes_Property(DeliveryAssemblyService::PROPERTY_ORIGIN);
            $testUri = $this->delivery->getOnePropertyValue($testProperty)->getUri();
        } catch (\Throwable $e) {}

        return [
            'deliveryId' => $this->deliveryUri,
            'testId' => $testUri,
        ];
    }
}
