<?php

declare(strict_types=1);

namespace App\Cli\Input;

class CliInput
{
    protected array $input_data;
    protected array $flags = [];

    /**
     * @param CliParameter[] $parameters
     */
    public function __construct(
        readonly protected array $argv,
        readonly protected array $parameters,
    )
    {
        $index = 0;
        foreach ($this->argv as $arg_value) {
            if (preg_match('|^--([a-zA-Z0-9\-_]+)=(.+)$|sui', trim($arg_value), $match)) {
                $parameter_name = $match[1];
                $parameter_value = $match[2];
            } elseif (preg_match('|^--([a-zA-Z0-9\-_]+)$|sui', trim($arg_value), $match)) {
                $this->flags[] = $match[1];
            } else {
                $index++;
            }
        }
    }

    public function getArgument(int $index): ?string
    {
        return $this->argv[$index] ?? null;
    }

    public function getArgumentInt(int $index): int|null|false
    {
        if (!array_key_exists($index, $this->argv)) {
            return null;
        }

        $result = $this->argv[$index];
        $result_int = (int)$result;

        if ((string)$result_int === $result) {
            return $result_int;
        }

        return false;
    }

    public function hasFlag(string $name): bool
    {
        return in_array($name, $this->flags);
    }

    public function isVerbose(): bool
    {
        return true;
    }
}