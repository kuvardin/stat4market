<?php

declare(strict_types=1);

namespace App\Sessions;

trait AuthorizationTrait
{
    protected ?int $authorization_id;

    public function getAuthorizationId(): ?int
    {
        return $this->authorization_id;
    }

    public function getAuthorization(): ?Authorization
    {
        return $this->authorization_id === null ? null : Authorization::findOneById($this->authorization_id);
    }
}