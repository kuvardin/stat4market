<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Actions\Action;

interface CreatableInterface
{
    public const string COL_CREATION_ID = 'creation_id';
    public const string COL_CREATION_DATE = 'creation_date';

    public function getCreationId(): ?int;

    public function getCreation(): ?Action;
}