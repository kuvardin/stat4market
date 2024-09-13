<?php

declare(strict_types=1);

namespace App\Sessions;

trait SessionRequiredTrait
{
    protected int $session_id;

    public function getSessionId(): int
    {
        return $this->session_id;
    }

    public function getSession(): Session
    {
        return Session::findOneById($this->session_id);
    }
}