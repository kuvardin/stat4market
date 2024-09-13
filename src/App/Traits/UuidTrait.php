<?php

declare(strict_types=1);

namespace App\Traits;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

trait UuidTrait
{
    protected ?string $uuid_value;

    public function getUuidValue(): ?string
    {
        return $this->uuid_value;
    }

    public function getUuid(): ?UuidInterface
    {
        return $this->uuid_value === null ? null : Uuid::fromString($this->uuid_value);
    }
}