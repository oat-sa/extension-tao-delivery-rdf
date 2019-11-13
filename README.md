# extension-tao-delivery-rdf
Manages deliveries using the ontology

_Note_: 
>This extension uses [Task Queue](https://github.com/oat-sa/extension-tao-task-queue) by default. 
>Please make sure you have properly set up your queue after install as it stated in the queue documentation.

## AssemblerService

### Options
- `AssemblerService::OPTION_FILESYSTEM_ID` (`filesystemId`) : unique identifier of the directory to export files.
- `AssemblerService::OPTION_FILE_READER` (`fileReader`) : object which implements interface `AssemblerFileReaderInterface` and responsible for the reading of the files that should be exported.  
*Readers*:  
  - `AssemblerFileReader` - default file reader which returns stream pointer for the readable file;
  - `AssemblerFileReaderCollection` - Queue of the readers to make changes on the readable file;
  - `XmlAssemblerFileReader` - If the delivery has a file `compact-test.php` it will be read as a `compact-test.xml`;
  - other extensions can implement different readers (for example `taoEncryption` has reader `EncryptedFileReader` which only can be used in the collection with other file readers);
- `AssemblerService::OPTION_SERVICE_CALL_CONVERTER` - different options of the `runtime` views. The way to send ServiceCall data.  
*Converters*
  - `JsonServiceCallConverter` - JSON.
  - `SerializedServiceCallConverter` - Serialized PHP.

### Configuration examples

* Default configuration (backward compatibility)
```php
<?php
use oat\taoDeliveryRdf\model\import\assemblerDataProviders\AssemblerFileReader;
use oat\taoDeliveryRdf\model\import\assemblerDataProviders\SerializedServiceCallConverter;
use oat\taoDeliveryRdf\model\import\AssemblerService;

return new AssemblerService(array(
    AssemblerService::OPTION_FILESYSTEM_ID => 'deliveryAssemblyExport',
    AssemblerService::OPTION_FILE_READER => new AssemblerFileReader(),
    AssemblerService::OPTION_SERVICE_CALL_CONVERTER => new SerializedServiceCallConverter(),
));
```

* Preferable configuration (without executable code)
```php
<?php
use oat\taoDeliveryRdf\model\import\assemblerDataProviders\XmlAssemblerFileReader;
use oat\taoDeliveryRdf\model\import\assemblerDataProviders\JsonServiceCallConverter;
use oat\taoDeliveryRdf\model\import\AssemblerService;

return new AssemblerService(array(
    AssemblerService::OPTION_FILESYSTEM_ID => 'deliveryAssemblyExport',
    AssemblerService::OPTION_FILE_READER => new XmlAssemblerFileReader(),
    AssemblerService::OPTION_SERVICE_CALL_CONVERTER => new JsonServiceCallConverter(),
));
```

* Encrypted configuration (requires `taoEncryption` to be installed). All the files from the private directory will be encrypted.
```php
<?php
use oat\taoDeliveryRdf\model\import\assemblerDataProviders\AssemblerFileReaderCollection;
use oat\taoDeliveryRdf\model\import\assemblerDataProviders\JsonServiceCallConverter;
use oat\taoDeliveryRdf\model\import\assemblerDataProviders\XmlAssemblerFileReader;
use oat\taoDeliveryRdf\model\import\AssemblerService;
use oat\taoEncryption\Service\DeliveryAssembly\import\assemblerDataProviders\EncryptedFileReader;
use oat\taoEncryption\Service\EncryptionServiceFactory;

$encryptionServiceFactory = new EncryptionServiceFactory();
$encryptionService = $encryptionServiceFactory->createSymmetricService($algorithmName, $key);
$encryptedFileReader = new EncryptedFileReader();
$encryptedFileReader->setOption(EncryptedFileReader::OPTION_ENCRYPTION_SERVICE,$encryptionService);

return new AssemblerService(array(
    AssemblerService::OPTION_FILESYSTEM_ID => 'deliveryAssemblyExport',
    AssemblerService::OPTION_FILE_READER => new AssemblerFileReaderCollection([
        new XmlAssemblerFileReader(),
        $encryptedFileReader,
    ]),
    AssemblerService::OPTION_SERVICE_CALL_CONVERTER => new JsonServiceCallConverter(),
));
```



