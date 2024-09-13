<?php

declare(strict_types=1);

namespace App\Sessions;

trait WebBotCodeTrait
{
    protected ?string $web_bot_code_value;

    public function getWebBotCodeValue(): ?string
    {
        return $this->web_bot_code_value;
    }

    public function getWebBotCode(): ?WebBotCode
    {
        return $this->web_bot_code_value === null ? null : WebBotCode::from($this->web_bot_code_value);
    }

    public function isWebBot(): bool
    {
        return $this->web_bot_code_value !== null;
    }
}