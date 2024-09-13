<?php

declare(strict_types=1);

namespace App\Traits;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

trait UuidRequiredTrait
{
    protected string $uuid_value;

    public function getUuidValue(): string
    {
        return $this->uuid_value;
    }

    public function getUuid(): UuidInterface
    {
        return Uuid::fromString($this->uuid_value);
    }
}