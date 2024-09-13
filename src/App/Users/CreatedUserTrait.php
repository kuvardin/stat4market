<?php

declare(strict_types=1);

namespace App\Users;

trait CreatedUserTrait
{
    protected ?int $created_user_id;

    public function getCreatedUserId(): ?int
    {
        return $this->created_user_id;
    }

    public function getCreatedUser(): ?User
    {
        return $this->created_user_id === null ? null : User::findOneById($this->created_user_id);
    }
}