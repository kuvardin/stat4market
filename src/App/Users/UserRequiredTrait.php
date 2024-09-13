<?php

declare(strict_types=1);

namespace App\Users;

trait UserRequiredTrait
{
    protected int $user_id;

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getUser(): User
    {
        return User::findOneById($this->user_id);
    }
}