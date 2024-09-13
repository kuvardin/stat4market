<?php

declare(strict_types=1);

namespace App\Cli;

use App\Cli\Exceptions\CliException;
use App\Cli\Input\CliInput;
use App\Cli\Input\CliParameter;

abstract class CliCommand
{
    private function __construct()
    {
    }

    public static function requirePdoConnection(): bool
    {
        return true;
    }

    public static function getDescription(): ?string
    {
        return null;
    }

    /**
     * @return CliParameter[]
     */
    public static function getParameters(): array
    {
        return [];
    }

    /**
     * @throws CliException
     */
    abstract public static function execute(CliInput $input): int;
}