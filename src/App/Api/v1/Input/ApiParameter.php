<?php

declare(strict_types=1);

namespace App\Api\v1\Input;

use Ramsey\Uuid\UuidInterface;
use RuntimeException;
use UnitEnum;

readonly class ApiParameter
{
    private function __construct(
        public ApiParameterType $type,
        public ?ApiParameterType $child_type = null,
        public ?int $required_and_empty_error = null,
        public ?string $description = null,
        public mixed $default_value = null,
        public ?bool $number_positive = null,
        public ?int $integer_min_value = null,
        public ?int $integer_max_value = null,
        public ?int $string_min_length = null,
        public ?int $string_max_length = null,
        public ?float $float_min_value = null,
        public ?float $float_max_value = null,
        public UnitEnum|string|null $enum_class = null,
        public bool $deprecated = false,
    )
    {
        if ($this->type === ApiParameterType::Array) {
            if ($this->child_type === null) {
                throw new RuntimeException('Field with type array must have child type');
            }

            if ($this->child_type === ApiParameterType::Array) {
                throw new RuntimeException('Array of array are denied');
            }
        } elseif ($this->type === ApiParameterType::Enum) {
            if ($this->child_type !== ApiParameterType::String && $this->child_type !== ApiParameterType::Integer) {
                throw new RuntimeException('Field with type enum must have child type (string or integer)');
            }
        } elseif ($this->child_type !== null) {
            throw new RuntimeException('Field with type scalar must not have child type');
        }
    }


    public static function integer(
        ?int $required_and_empty_error,
        string $description = null,
        int $default_value = null,
        ?int $min_value = null,
        ?int $max_value = null,
        ?bool $positive = null,
        bool $deprecated = false,
    ): self
    {
        return new self(
            type: ApiParameterType::Integer,
            required_and_empty_error: $required_and_empty_error,
            description: $description,
            default_value: $default_value,
            number_positive: $positive,
            integer_min_value: $min_value,
            integer_max_value: $max_value,
            deprecated: $deprecated,
        );
    }

    public static function string(
        ?int $required_and_empty_error,
        string $description = null,
        string $default_value = null,
        ?int $min_length = null,
        ?int $max_length = null,
        bool $deprecated = false,
    ): self
    {
        return new self(
            type: ApiParameterType::String,
            required_and_empty_error: $required_and_empty_error,
            description: $description,
            default_value: $default_value,
            string_min_length: $min_length,
            string_max_length: $max_length,
            deprecated: $deprecated,
        );
    }

    public static function float(
        ?int $required_and_empty_error,
        string $description = null,
        float $default_value = null,
        ?float $positive = null,
        ?float $min_value = null,
        ?float $max_value = null,
        bool $deprecated = false,
    ): self
    {
        return new self(
            type: ApiParameterType::Float,
            child_type: $required_and_empty_error,
            description: $description,
            default_value: $default_value,
            number_positive: $positive,
            float_min_value: $min_value,
            float_max_value: $max_value,
            deprecated: $deprecated,
        );
    }

    public static function boolean(
        ?int $required_and_empty_error,
        string $description = null,
        bool $default_value = null,
        bool $deprecated = false,
    ): self
    {
        return new self(
            type: ApiParameterType::Boolean,
            required_and_empty_error: $required_and_empty_error,
            description: $description,
            default_value: $default_value,
            deprecated: $deprecated,
        );
    }

    public static function uuid(
        ?int $required_and_empty_error,
        string $description = null,
        UuidInterface $default_value = null,
        bool $deprecated = false,
    ): self
    {
        return new self(
            type: ApiParameterType::Uuid,
            required_and_empty_error: $required_and_empty_error,
            description: $description,
            default_value: $default_value,
            deprecated: $deprecated,
        );
    }

    public static function array(
        ApiParameterType $child_type,
        ?int $required_and_empty_error,
        string $description = null,
        array $default_value = null,
        bool $deprecated = false,
    ): self
    {
        return new self(
            type: ApiParameterType::Array,
            child_type: $child_type,
            required_and_empty_error: $required_and_empty_error,
            description: $description,
            default_value: $default_value,
            deprecated: $deprecated,
        );
    }

    public function isRequired(): bool
    {
        return $this->required_and_empty_error !== null;
    }

    public function getJsType(): string
    {
        if ($this->type === ApiParameterType::Array) {
            return "{$this->child_type->getJsType()}[]";
        }

        if ($this->type === ApiParameterType::Enum) {
            return $this->child_type->getJsType();
        }

        if ($this->type->isScalar()) {
            return $this->type->getJsType();
        }

        throw new RuntimeException("Unexpected API parameter type: {$this->type->value}");
    }

    public function getDartType(): string
    {
        if ($this->type === ApiParameterType::Array) {
            return "List<{$this->child_type->getDartType()}>";
        }

        if ($this->type === ApiParameterType::Enum) {
            return $this->child_type->getDartType();
        }

        if ($this->type->isScalar()) {
            return $this->type->getDartType();
        }

        throw new RuntimeException("Unexpected API parameter type: {$this->type->value}");
    }

    public static function enum(
        UnitEnum|string $enum_class,
        ApiParameterType $child_type,
        ?int $required_and_empty_error,
        string $description = null,
        UnitEnum $default_value = null,
        bool $deprecated = false,
    ): self
    {
        if (!enum_exists($enum_class)) {
            $enum_class_name = is_string($enum_class) ? $enum_class : $enum_class::class;
            throw new RuntimeException("Enum $enum_class_name not found");
        }

        if ($child_type !== ApiParameterType::String && $child_type !== ApiParameterType::Integer) {
            throw new RuntimeException("Incorrect enum child type: {$child_type->value}");
        }

        return new self(
            type: ApiParameterType::Enum,
            child_type: $child_type,
            required_and_empty_error: $required_and_empty_error,
            description: $description,
            default_value: $default_value,
            enum_class: $enum_class,
            deprecated: $deprecated,
        );
    }
}
