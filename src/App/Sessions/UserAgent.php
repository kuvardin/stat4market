<?php

declare(strict_types=1);

namespace App\Sessions;

class UserAgent
{
    readonly public string $value;
    protected ?OperationSystem $operation_system = null;
    protected ?WebBotCode $web_bot = null;
    protected ?bool $is_web_bot = null;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function searchOperationSystem(string $user_agent): ?OperationSystem
    {
        if (preg_match('|^[^\(]+\(([^)]+)\)|u', $user_agent, $match)) {
            switch (true) {
                case str_starts_with($match[1], 'iPhone'):
                case str_starts_with($match[1], 'iPad'):
                    return OperationSystem::IOS;

                case str_starts_with($match[1], 'Macintosh'):
                    return OperationSystem::MacOS;

                case str_starts_with($match[1], 'Windows'):
                    return OperationSystem::Windows;

                case str_contains($match[1], 'Android'):
                    return OperationSystem::Android;

                case str_starts_with($match[1], 'X11; Ubuntu'):
                case str_starts_with($match[1], 'X11; Linux'):
                    return OperationSystem::Linux;
            }
        }

        return null;
    }

    public function getOperationSystem(): ?OperationSystem
    {
        return $this->operation_system ??= self::searchOperationSystem($this->value);
    }

    public function getWebBotCode(): ?WebBotCode
    {
        if ($this->is_web_bot === false) {
            return null;
        }

        if ($this->web_bot === null) {
            $this->web_bot = WebBotCode::makeByUserAgent($this->value);
            $this->is_web_bot = $this->web_bot !== null;
        }

        return $this->web_bot;
    }

    public function isWebBot(): bool
    {
        return $this->getWebBotCode() !== null;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}