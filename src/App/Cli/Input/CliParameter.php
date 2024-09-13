<?php

declare(strict_types=1);

namespace App\Cli\Input;

class CliParameter
{
    readonly CliParameterType $type;
    readonly bool $required;
    readonly ?string $description;

    public function __construct(CliParameterType $type, bool $required, ?string $description)
    {
        $this->type = $type;
        $this->required = $required;
        $this->description = $description;
    }
}