<?php

declare(strict_types=1);

namespace App\Cli\Input;

enum CliParameterType: string
{
    case Flag = 'flag';
    case String = 'string';
    case Integer = 'int';
    case Boolean = 'bool';
    case Float = 'float';
}