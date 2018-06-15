<?php
/**
 * Default config header created during install
 */

use oat\taoDeliveryRdf\model\Delete\DeliveryDeleteService;

return new DeliveryDeleteService(array(
    'deleteDeliveryDataServices' => array(
        'taoDeliveryRdf/DeliveryAssemblyWrapper'
    )
));
