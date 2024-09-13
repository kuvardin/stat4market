<?php

declare(strict_types=1);

namespace App\Postgresql;

use App\Utils\CaseSwitcher;
use Kuvardin\DataFilter\DataFilter;
use Ramsey\Uuid\UuidInterface;
use RuntimeException;

class ColumnInfo
{
    readonly public array $data;
    readonly public string $name;
    readonly public bool $is_nullable;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->name = DataFilter::requireNotEmptyString($data['column_name']);
        $this->is_nullable = DataFilter::requireBoolByValues($data['is_nullable'], 'YES', 'NO');
    }

    public function getPhpType(): string
    {
        switch ($this->data['data_type']) {
            case 'character varying':
            case 'bytea':
            case 'text':
                return 'string';

            case 'bigint':
            case 'integer':
                return 'int';

            case 'double precision':
            case 'real':
                return 'float';

            case 'json':
                return 'array';

            case 'boolean':
                return 'bool';

            case 'uuid':
                return '\\' . UuidInterface::class;
        }

        throw new RuntimeException("Unknown column data type: {$this->data['data_type']}");
    }

    public function getPhpTypeFull(): string
    {
        return ($this->is_nullable ? '?' : '') . $this->getPhpType();
    }

    public function getNameInCamelCase(bool $ucfirst = true): string
    {
        return CaseSwitcher::snakeToCamel($this->name, $ucfirst);
    }
}