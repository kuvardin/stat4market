<?php

declare(strict_types=1);

namespace App\Api\v1\Input;

use RuntimeException;

enum ApiParameterType: string
{
    case String = 'string';
    case Integer = 'int';
    case Float = 'float';
    case Boolean = 'bool';
    case Phrase = 'phrase';
    case DateTime = 'date_time';
    case Date = 'date';
    case Uuid = 'uuid';
    case Array = 'array';
    case Enum = 'enum';

    public function isScalar(): bool
    {
        return $this !== self::Array;
    }

    public function getJsType(): string
    {
        switch ($this) {
            case self::String:
            case self::Uuid:
                return 'String';

            case self::Integer:
            case self::Float:
                return 'Number';

            case self::Boolean:
                return 'Boolean';

            case self::Phrase:
                return 'Api.Phrase';

            case self::DateTime:
            case self::Date:
                return 'Date';

            case self::Array:
                return 'Array';

            case self::Enum:
                return 'Enum';
        }

        throw new RuntimeException("Unexpected API parameter type: {$this->value}");
    }

    public function getDartType(): string
    {
        return match ($this) {
            self::String, self::Uuid => 'String',
            self::Integer => 'int',
            self::Float => 'double',
            self::Boolean => 'bool',
            self::Phrase => 'Phrase',
            self::DateTime, self::Date => 'DateTime',
            self::Array => 'Map',
            self::Enum => 'Enum',
            default => throw new RuntimeException("Unexpected API parameter type: {$this->value}"),
        };
    }
}
