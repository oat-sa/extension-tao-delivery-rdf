<?php

use oat\taoDeliveryRdf\model\assembly\CompiledTestConverterService;
use oat\taoQtiTest\models\PhpCodeCompilationDataService;
use oat\taoQtiTest\models\XmlCompilationDataService;

return new CompiledTestConverterService([
    CompiledTestConverterService::OPTION_PHP_COMPILATION_SERVICE => new PhpCodeCompilationDataService(),
    CompiledTestConverterService::OPTION_XML_COMPILATION_SERVICE => new XmlCompilationDataService()
]);
