<?php

declare(strict_types=1);

namespace App\Sessions;

trait UserAgentByValueTrait
{
    protected ?string $user_agent_value;

    public function getUserAgentValue(): ?string
    {
        return $this->user_agent_value;
    }

    public function getUserAgent(): ?UserAgent
    {
        return $this->user_agent_value === null ? null : new UserAgent($this->user_agent_value);
    }
}