<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Actions\Action;

interface EditableInterface
{
    public const string COL_LAST_EDIT_ID = 'last_edit_id';
    public const string COL_LAST_EDIT_DATE = 'last_edit_date';

    public function getLastEditDate(): ?int;

    public function getLastEdit(): ?Action;

    public function getLastEditId(): ?int;

    public function isEdited(): bool;
}