<?php
/**
 * AssemblerService configuration
 */

use oat\taoDeliveryRdf\model\import\assemblerDataProviders\assemblerFileReaders\AssemblerFileReader;
use oat\taoDeliveryRdf\model\import\assemblerDataProviders\ConfigurableRdfExporter;
use oat\taoDeliveryRdf\model\import\assemblerDataProviders\serviceCallConverters\SerializedServiceCallConverter;
use oat\taoDeliveryRdf\model\import\AssemblerService;

return new AssemblerService([
    AssemblerService::OPTION_FILESYSTEM_ID => 'deliveryAssemblyExport',
    AssemblerService::OPTION_FILE_READER => new AssemblerFileReader(),
    AssemblerService::OPTION_SERVICE_CALL_CONVERTER => new SerializedServiceCallConverter(),
    AssemblerService::OPTION_RDF_EXPORTER => new ConfigurableRdfExporter(),
]);
