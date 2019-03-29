<?php
/**
 * Default config header created during install
 */

use oat\taoDeliveryRdf\model\Delete\DeliveryDeleteService;

return new DeliveryDeleteService(array(
    'deleteDeliveryDataServices' => array(
        'taoDeliveryRdf/DeliveryAssemblyWrapper'
    ),
    DeliveryDeleteService::OPTION_LIMIT_DELIVERY_EXECUTIONS => 1000
));
