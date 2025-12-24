<?php

namespace oat\taoDeliveryRdf\model\DataStore;

use JsonSerializable;

class ResourceSyncDTO implements JsonSerializable
{
    private string $resourceId;
    private string $fileSystemId;
    private ?string $testUri;
    private bool $isDeleted;
    private ?string $tenantId;
    private ?string $firstTenantId;
    private int $maxTries;
    private array $deliveryMetaData;
    private array $testMetaData;
    private array $itemMetaData;

    public function __construct(
        string $resourceId,
        string $fileSystemId,
        ?string $testUri = null,
        bool $isDeleted = false,
        ?string $tenantId = null,
        ?string $firstTenantId = null,
        int $maxTries = 10,
        array $deliveryMetaData = [],
        array $testMetaData = [],
        array $itemMetaData = []
    ) {
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

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
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
}
