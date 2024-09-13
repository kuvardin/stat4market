<?php

declare(strict_types=1);

namespace App\Traits;

trait CreationDateTrait
{
    protected ?int $creation_date;

    public function getCreationDate(): ?int
    {
        return $this->creation_date;
    }
}