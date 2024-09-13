<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Actions\Action;
use App\Sessions\Session;

interface DeletableInterface
{
    public const string COL_DELETION_ID = 'deletion_id';
    public const string COL_DELETION_DATE = 'deletion_date';

    public function delete(?Session $session, int $deletion_date = null): void;

    public function getDeletionId(): ?int;

    public function getDeletion(): ?Action;

    public function getDeletionDate(): ?int;

    public function isDeleted(): bool;
}