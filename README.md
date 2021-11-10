# extension-tao-delivery-rdf
Manages deliveries using the ontology

_Note_: 
>This extension uses [Task Queue](https://github.com/oat-sa/extension-tao-task-queue) by default. 
>Please make sure you have properly set up your queue after install as it stated in the queue documentation.

## Export compiled delivery assembly package CLI command
Command: `php index.php "oat\taoDeliveryRdf\scripts\tools\ExportDeliveryAssembly" -uri RDF_DELIVERY_URI -format xml -out ~/path.zip`

### Options
- `uri` : URI of compiled delivery RDF resource.
- `format` : Output format for compiled test file `compact-test` in exported delivery assembly package. Optional. Default value - `xml`
- `out` : Filepath for exported delivery assembly package.

## Feature Flags

### Data Store

- `FEATURE_FLAG_ENABLE_DATA_STORE_STORAGE`: Enable `Data Store` integration.
- `FEATURE_FLAG_DATA_STORE_METADATA_V2`: Allows usage of the new metadata format while integration with `Data Store`.
