<?php

use oat\taoDeliveryRdf\model\export\AssemblyExporterService;
use oat\taoDeliveryRdf\model\assembly\AssemblyFilesReader;

return new AssemblyExporterService([
    AssemblyExporterService::OPTION_ASSEMBLY_FILES_READER   => new AssemblyFilesReader(),
    AssemblyExporterService::OPTION_RDF_EXPORTER            => new tao_models_classes_export_RdfExporter()
]);
