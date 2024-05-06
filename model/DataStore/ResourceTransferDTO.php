<?php

namespace oat\taoDeliveryRdf\model\DataStore;

use Exception;
use JsonSerializable;

class ResourceTransferDTO implements \Serializable, JsonSerializable
{
    private string $resourceId;
    private string $fileSystemId;
    private ?string $testUri = null;
    private bool $isDeleted;
    private ?string $tenantId;
    private ?string $firstTenantId = null;
    private int $maxTries = 1;
    private array $deliveryMetaData = [];
    private array $testMetaData = [];
    private array $itemMetaData = [];

    public function __construct(string $resourceId, string $fileSystemId, ?string $testUri, bool $isDeleted, ?string $tenantId, ?string $firstTenantId, int $maxTries, array $deliveryMetaData, array $testMetaData, array $itemMetaData)
    {
        $this->resourceId = $resourceId;
        $this->fileSystemId = $fileSystemId;
        $this->testUri = $testUri;
        $this->isDeleted = $isDeleted;
        $this->tenantId = $tenantId;
        $this->firstTenantId = $firstTenantId;
        $this->maxTries = $maxTries;
        $this->deliveryMetaData = $deliveryMetaData;
        $this->testMetaData = $testMetaData;
        $this->itemMetaData = $itemMetaData;
    }

    public function getMetadata(): array
    {
        return [
            'deliveryMetaData' => $this->deliveryMetaData,
            'testMetaData' => $this->testMetaData,
            'itemMetaData' => $this->itemMetaData
        ];
    }

    public function serialize()
    {
        $vars = get_object_vars($this);
        return serialize($vars);
    }

    public function unserialize($data)
    {
        [$this->resourceId] = unserialize($data);
    }

    public function jsonSerialize()
    {
        $t = 1;
    }


    //------------------GENERATED---------------------

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function getFileSystemId(): string
    {
        return $this->fileSystemId;
    }

    public function getTestUri(): ?string
    {
        return $this->testUri;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }

    public function getFirstTenantId(): ?string
    {
        return $this->firstTenantId;
    }

    public function getMaxTries(): int
    {
        return $this->maxTries;
    }

    public function getDeliveryMetaData(): array
    {
        return $this->deliveryMetaData;
    }

    public function getTestMetaData(): array
    {
        return $this->testMetaData;
    }

    public function getItemMetaData(): array
    {
        return $this->itemMetaData;
    }
}