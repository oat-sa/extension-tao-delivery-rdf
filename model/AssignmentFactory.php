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
namespace oat\taoDeliveryRdf\model;

use oat\oatbox\user\User;
use \core_kernel_classes_Property;
use oat\taoDelivery\model\AttemptServiceInterface;
use tao_helpers_Date;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
/**
 * Service to manage the assignment of users to deliveries
 *
 * @access public
 * @author Joel Bout, <joel@taotesting.com>
 * @package taoDelivery
 */
class AssignmentFactory implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    protected $delivery;
    
    private $user;
    
    private $startable;

    private $displayAttempts;

    private $displayDates;

    public function __construct(\core_kernel_classes_Resource $delivery, User $user, $startable, $displayAttempts = true, $displayDates = true)
    {
        $this->delivery = $delivery;
        $this->user = $user;
        $this->startable = $startable;
        $this->displayAttempts = $displayAttempts;
        $this->displayDates = $displayDates;
    }
    
    public function getDeliveryId()
    {
        return $this->delivery->getUri();
    }
    
    protected function getUserId()
    {
        return $this->user->getIdentifier();
    }
    
    protected function getLabel()
    {
        return $this->delivery->getLabel();    
    }
    
    protected function getDescription()
    {
        $deliveryProps = $this->delivery->getPropertiesValues(array(
            new core_kernel_classes_Property(DeliveryContainerService::PROPERTY_MAX_EXEC),
            new core_kernel_classes_Property(DeliveryContainerService::PROPERTY_START),
            new core_kernel_classes_Property(DeliveryContainerService::PROPERTY_END),
        ));
        
        $propMaxExec = current($deliveryProps[DeliveryContainerService::PROPERTY_MAX_EXEC]);
        $propStartExec = current($deliveryProps[DeliveryContainerService::PROPERTY_START]);
        $propEndExec = current($deliveryProps[DeliveryContainerService::PROPERTY_END]);
        
        $startTime = (!(is_object($propStartExec)) or ($propStartExec=="")) ? null : $propStartExec->literal;
        $endTime = (!(is_object($propEndExec)) or ($propEndExec=="")) ? null : $propEndExec->literal;
        $maxExecs = (!(is_object($propMaxExec)) or ($propMaxExec=="")) ? 0 : $propMaxExec->literal;
        
        $countExecs = count($this->getServiceLocator()->get(AttemptServiceInterface::SERVICE_ID)
            ->getAttempts($this->delivery->getUri(), $this->user));
        
        return $this->buildDescriptionFromData($startTime, $endTime, $countExecs, $maxExecs);
    }
    
    protected function getStartable()
    {
        return $this->startable;
    }
    
    public function getStartTime()
    {
        $prop = $this->delivery->getOnePropertyValue(new core_kernel_classes_Property(DeliveryContainerService::PROPERTY_START));
        return is_null($prop) ? null : (string)$prop;
    }
    
    public function getDeliveryOrder()
    {
        $prop = $this->delivery->getOnePropertyValue(new core_kernel_classes_Property(DeliveryAssemblyService::PROPERTY_DELIVERY_DISPLAY_ORDER_PROP));
        return is_null($prop) ? 0 : intval((string)$prop);
    }
    
    protected function buildDescriptionFromData($startTime, $endTime, $countExecs, $maxExecs)
    {
        $descriptions = array();

        if ($this->displayDates) {
            if (!empty($startTime) && !empty($endTime)) {
                $descriptions[] = __('Available from %1$s to %2$s',
                    tao_helpers_Date::displayeDate($startTime)
                    ,tao_helpers_Date::displayeDate($endTime)
                );
            } elseif (!empty($startTime) && empty($endTime)) {
                $descriptions[] = __('Available from %s', tao_helpers_Date::displayeDate($startTime));
            } elseif (!empty($endTime)) {
                $descriptions[] = __('Available until %s', tao_helpers_Date::displayeDate($endTime));
            }    
        }
        
        if ($maxExecs != 0 && $this->displayAttempts) {
            if ($maxExecs == 1) {
                $descriptions[] = __('Attempt %1$s of %2$s'
                    ,$countExecs
                    ,!empty($maxExecs)
                    ? $maxExecs
                    : __('unlimited'));
            } else {
                $descriptions[] = __('Attempts %1$s of %2$s'
                    ,$countExecs
                    ,!empty($maxExecs)
                    ? $maxExecs
                    : __('unlimited'));
        
            }
        }
        return $descriptions;
    }
    
    public function toAssignment()
    {
        return new Assignment(
            $this->getDeliveryId(),
            $this->getUserId(),
            $this->getLabel(),
            $this->getDescription(),
            $this->getStartable(),
            $this->getDeliveryOrder()
        );
    }
    
    public function __equals(AssignmentFactory $factory)
    {
        return $this->getDeliveryId() == $factory->getDeliveryId();
    }
}
