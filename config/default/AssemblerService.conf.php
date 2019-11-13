<?php
/**
 * AssemblerService configuration
 */

use oat\taoDeliveryRdf\model\import\assemblerDataProviders\AssemblerFileReader;
use oat\taoDeliveryRdf\model\import\assemblerDataProviders\SerializedServiceCallConverter;
use oat\taoDeliveryRdf\model\import\AssemblerService;

return new AssemblerService([
    AssemblerService::OPTION_FILESYSTEM_ID => 'deliveryAssemblyExport',
    AssemblerService::OPTION_FILE_READER => new AssemblerFileReader(),
    AssemblerService::OPTION_SERVICE_CALL_CONVERTER => new SerializedServiceCallConverter(),
]);