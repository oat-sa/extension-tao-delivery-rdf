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
use oat\oatbox\service\ConfigurableService;
use core_kernel_classes_Resource;
use core_kernel_classes_Class;
use \core_kernel_classes_Property;
use oat\taoDelivery\model\AssignmentService;
use oat\taoDeliveryRdf\model\guest\GuestTestUser;
use oat\taoDelivery\model\RuntimeService;
use oat\taoDelivery\model\AttemptServiceInterface;

/**
 * Service to manage the assignment of users to deliveries
 *
 * @access public
 * @author Joel Bout, <joel@taotesting.com>
 * @package taoDelivery
 */
class GuestAssignment extends ConfigurableService implements AssignmentService
{
    /**
     * Interface part
     */
    const PROPERTY_GROUP_DELIVERY = 'http://www.tao.lu/Ontologies/TAOGroup.rdf#Deliveries';

    const DISPLAY_ATTEMPTS_OPTION = 'display_attempts';

    /**
     * (non-PHPdoc)
     * @see \oat\taoDelivery\model\AssignmentService::getAssignments()
     */
    public function getAssignments(User $user)
    {
        $assignments = array();
        foreach ($this->getAssignmentFactories($user) as $factory) {
            $assignments[] = $factory->toAssignment();
        }
        
        return $this->orderAssignments($assignments);
    }

    /**
     * @param User $user
     * @return array
     */
    public function getAssignmentFactories(User $user)
    {
        if (!$this->isGuestUser($user)) {
            \common_Logger::w('Non guest user '.$user->getIdentifier().' retrieved assignments from GuestAssignment');
            // no assignments for non guests
            return [];
        }
        $assignments = array();
        foreach ($this->getGuestAccessDeliveries() as $deliveryId) {
            $delivery = new \core_kernel_classes_Resource($deliveryId);
            $startable = $this->verifyTime($delivery) && $this->verifyToken($delivery, $user);
            $assignments[] = $this->getAssignmentFactory($delivery, $user, $startable);
        }
        return $assignments;
    }

    /**
     * @deprecated
     */
    public function getRuntime($deliveryId)
    {
        return $this->getServiceLocator()->get(RuntimeService::SERVICE_ID)->getRuntime($deliveryId);
    }
    
    
    /**
     * 
     * @param string $deliveryId
     * @return array identifiers of the users
     */
    public function getAssignedUsers($deliveryId)
    {
        return [];
    }
    
    /**
     * Search for deliveries configured for guest access
     *
     * @return array
     */
    public function getGuestAccessDeliveries()
    {
        $class = new core_kernel_classes_Class(DeliveryAssemblyService::CLASS_URI);

        return $class->searchInstances(
            array(
                DeliveryContainerService::PROPERTY_ACCESS_SETTINGS => DeliveryAssemblyService::PROPERTY_DELIVERY_GUEST_ACCESS
            ),
            array('recursive' => true)
        );
    }

    /**
     * Check if current user is guest
     *
     * @param User $user
     * @return bool
     */
    public function isGuestUser(User $user)
    {
        return ($user instanceof GuestTestUser);
    }

    /**
     * @param string $deliveryIdentifier
     * @param User $user
     * @return bool
     */
    public function isDeliveryExecutionAllowed($deliveryIdentifier, User $user)
    {
        $delivery = new \core_kernel_classes_Resource($deliveryIdentifier);
        return $this->verifyUserAssigned($delivery, $user)
            && $this->verifyTime($delivery)
            && $this->verifyToken($delivery, $user);
    }

    /**
     * @param core_kernel_classes_Resource $delivery
     * @param User $user
     * @return bool
     */
    protected function verifyUserAssigned(core_kernel_classes_Resource $delivery, User $user){
        return $this->isGuestUser($user) && $this->hasDeliveryGuestAccess($delivery);
    }
    
    /**
     * Check if delivery configured for guest access
     *
     * @param core_kernel_classes_Resource $delivery
     * @return bool
     * @throws \common_exception_InvalidArgumentType
     */
    protected function hasDeliveryGuestAccess(core_kernel_classes_Resource $delivery )
    {
        $returnValue = false;
    
        $properties = $delivery->getPropertiesValues(array(
            new core_kernel_classes_Property(DeliveryContainerService::PROPERTY_ACCESS_SETTINGS ),
        ));
        $propAccessSettings = current($properties[DeliveryContainerService::PROPERTY_ACCESS_SETTINGS ]);
        $accessSetting = (!(is_object($propAccessSettings)) or ($propAccessSettings=="")) ? null : $propAccessSettings->getUri();
    
        if( !is_null($accessSetting) ){
            $returnValue = ($accessSetting === DeliveryAssemblyService::PROPERTY_DELIVERY_GUEST_ACCESS);
        }
    
        return $returnValue;
    }

    /**
     * @param core_kernel_classes_Resource $delivery
     * @param User $user
     * @return bool
     */
    protected function verifyToken(core_kernel_classes_Resource $delivery, User $user)
    {
        $propMaxExec = $delivery->getOnePropertyValue(new \core_kernel_classes_Property(DeliveryContainerService::PROPERTY_MAX_EXEC));
        $maxExec = is_null($propMaxExec) ? 0 : $propMaxExec->literal;
        
        //check Tokens
        $usedTokens = count($this->getServiceLocator()->get(AttemptServiceInterface::SERVICE_ID)
            ->getAttempts($delivery->getUri(), $user));
    
        if (($maxExec != 0) && ($usedTokens >= $maxExec)) {
            \common_Logger::d("Attempt to start the compiled delivery ".$delivery->getUri(). "without tokens");
            return false;
        }
        return true;
    }

    /**
     * @param core_kernel_classes_Resource $delivery
     * @return bool
     */
    protected function verifyTime(core_kernel_classes_Resource $delivery)
    {
        $deliveryProps = $delivery->getPropertiesValues(array(
            DeliveryContainerService::PROPERTY_START,
            DeliveryContainerService::PROPERTY_END,
        ));
        
        $startExec = empty($deliveryProps[DeliveryContainerService::PROPERTY_START])
            ? null
            : (string)current($deliveryProps[DeliveryContainerService::PROPERTY_START]);
        $stopExec = empty($deliveryProps[DeliveryContainerService::PROPERTY_END])
            ? null
            : (string)current($deliveryProps[DeliveryContainerService::PROPERTY_END]);
        
        $startDate  =    date_create('@'.$startExec);
        $endDate    =    date_create('@'.$stopExec);
        if (!$this->areWeInRange($startDate, $endDate)) {
            \common_Logger::d("Attempt to start the compiled delivery ".$delivery->getUri(). " at the wrong date");
            return false;
        }
        return true;
    }
    
    /**
     * Check if the date are in range
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return boolean true if in range
     */
    protected function areWeInRange($startDate, $endDate){
        return (empty($startDate) || date_create() >= $startDate)
        && (empty($endDate) || date_create() <= $endDate);
    }
    
    /**
     * Order Assignments of a given user.
     * 
     * By default, this method relies on the taoDelivery:DisplayOrder property
     * to order the assignments (Ascending order). However, implementers extending
     * the GroupAssignment class are encouraged to override this method if they need
     * another behaviour.
     * 
     * @param array $assignments An array of assignments.
     * @return array The $assignments array ordered.
     */
    protected function orderAssignments(array $assignments) {
        usort($assignments, function ($a, $b) {
            return $a->getDisplayOrder() - $b->getDisplayOrder();
        });
        
        return $assignments;
    }

    /**
     * @param core_kernel_classes_Resource $delivery
     * @param User $user
     * @param $startable
     * @return AssignmentFactory
     */
    protected function getAssignmentFactory(\core_kernel_classes_Resource $delivery, User $user, $startable)
    {
        $displayAttempts = ($this->hasOption(self::DISPLAY_ATTEMPTS_OPTION)) ? $this->getOption(self::DISPLAY_ATTEMPTS_OPTION) : true;
        $factory = new AssignmentFactory($delivery, $user, $startable, $displayAttempts);
        $factory->setServiceLocator($this->getServiceLocator());
        return $factory;
    }
}
