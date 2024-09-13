<?php

declare(strict_types=1);

namespace App\Api\v1\Output;

use RuntimeException;

enum ApiFieldType: string
{
    case String = 'string';
    case Integer = 'int';
    case Float = 'float';
    case Boolean = 'bool';
    case Uuid = 'uuid';
    case Object = 'object';
    case Timestamp = 'timestamp';
    case Phrase = 'phrase';
    case Array = 'array';

    public function isScalar(): bool
    {
        return $this !== self::Array && $this !== self::Object;
    }

    public function getJsType(): string
    {
        return match ($this) {
            self::String, self::Uuid => 'String',
            self::Integer, self::Float => 'Number',
            self::Boolean => 'Boolean',
            self::Timestamp => 'Date',
            self::Phrase => 'Api.Phrase',
            self::Object => 'Object',
            self::Array => 'Array',
        };
    }

    public function getDartType(): string
    {
        return match($this) {
            self::String, self::Uuid => 'String',
            self::Integer, self::Timestamp => 'int',
            self::Float => 'double',
            self::Boolean => 'bool',
            self::Object => 'Object',
            self::Phrase => 'Phrase',
            self::Array => 'Map<dynamic, dynamic>',
            default => throw new RuntimeException("Unecpected API field type: {$this->value}"),
        };
    }
}
