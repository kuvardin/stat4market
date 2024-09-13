<?php

declare(strict_types=1);

namespace App\Sessions;

trait UserAgentByValueRequiredTrait
{
    protected string $user_agent_value;

    public function getUserAgentValue(): string
    {
        return $this->user_agent_value;
    }

    public function getUserAgent(): UserAgent
    {
        return new UserAgent($this->user_agent_value);
    }
}