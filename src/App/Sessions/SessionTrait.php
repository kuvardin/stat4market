<?php

declare(strict_types=1);

namespace App\Sessions;

trait SessionTrait
{
    protected ?int $session_id;

    public function getSessionId(): ?int
    {
        return $this->session_id;
    }

    public function getSession(): ?Session
    {
        return $this->session_id === null ? null : Session::findOneById($this->session_id);
    }
}