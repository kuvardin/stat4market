<?php

declare(strict_types=1);

namespace App\Traits;

trait CreationDateRequiredTrait
{
    protected int $creation_date;

    public function getCreationDate(): int
    {
        return $this->creation_date;
    }
}