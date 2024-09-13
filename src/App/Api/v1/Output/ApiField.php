<?php

declare(strict_types=1);

namespace App\Api\v1\Output;

use App\Api\v1\ApiModel;
use App\Api\v1\Interfaces\ApiModelIntIndexed;
use App\Api\v1\Interfaces\ApiModelStringIndexed;
use RuntimeException;

readonly class ApiField
{
    private function __construct(
        public ApiFieldType $type,
        public bool $nullable,
        public ApiModel|string|null $model_class = null,
        public ApiFieldType|null $array_child_type = null,
        public ApiModel|string|null $array_child_model_class = null,
        public ApiFieldType|string|null $array_child_model_index_type = null,
        public string|null $description = null,
    )
    {
        if ($this->type === ApiFieldType::Object) {
            if ($this->model_class === null) {
                throw new RuntimeException("Empty class for field with type {$this->type->value}");
            }
        } else {
            if ($this->model_class !== null) {
                throw new RuntimeException("Not empty class for field with type {$this->type->value}");
            }
        }

        if ($this->type === ApiFieldType::Array) {
            if ($this->nullable) {
                throw new RuntimeException('Array cannot be nullable');
            }

            if ($this->array_child_type === ApiFieldType::Object) {
                if ($this->array_child_model_class === null) {
                    throw new RuntimeException("Empty class for field with type {$this->array_child_type->value}");
                }

                switch ($this->array_child_model_index_type) {
                    case null:

                        break;

                    case ApiFieldType::Integer:
                        if (!is_subclass_of($this->array_child_model_class, ApiModelIntIndexed::class)) {
                            throw new RuntimeException(
                                'Model must be int indexed ' . $this->array_child_model_class::getName(),
                            );
                        }
                        break;

                    case ApiFieldType::String:
                        if (!is_subclass_of($this->array_child_model_class, ApiModelStringIndexed::class)) {
                            throw new RuntimeException(
                                'Model must be string indexed' . $this->array_child_model_class::getName()
                            );
                        }
                        break;

                    default:
                        throw new RuntimeException(
                            "Incorrect array_child_model_index_type: {$this->array_child_model_index_type}",
                        );
                }
            } else {
                if ($this->array_child_model_class !== null) {
                    throw new RuntimeException("Not empty class for field with type {$this->array_child_type->value}");
                }
            }

            if ($this->array_child_type === ApiFieldType::Array) {
                throw new RuntimeException('Array of array denied');
            }
        }

        if ($this->model_class !== null) {
            if (!class_exists($this->model_class)) {
                throw new RuntimeException("API model class {$this->model_class} not found");
            }

            if (!is_subclass_of($this->model_class, ApiModel::class)) {
                throw new RuntimeException("API model class {$this->model_class} must be extend for ApiModel");
            }
        }
    }

    public static function string(
        bool $nullable,
        string $description = null,
    ): self
    {
        return new self(
            type: ApiFieldType::String,
            nullable: $nullable,
            description: $description,
        );
    }

    public static function integer(
        bool $nullable,
        string $description = null,
    ): self
    {
        return new self(
            type: ApiFieldType::Integer,
            nullable: $nullable,
            description: $description,
        );
    }

    public static function float(
        bool $nullable,
        string $description = null,
    ): self
    {
        return new self(
            type: ApiFieldType::Float,
            nullable: $nullable,
            description: $description,
        );
    }

    public static function boolean(
        bool $nullable,
        string $description = null,
    ): self
    {
        return new self(
            type: ApiFieldType::Boolean,
            nullable: $nullable,
            description: $description,
        );
    }

    public static function uuid(
        bool $nullable,
        string $description = null,
    ): self
    {
        return new self(
            type: ApiFieldType::Uuid,
            nullable: $nullable,
            description: $description,
        );
    }

    public static function timestamp(
        bool $nullable,
        string $description = null,
    ): self
    {
        return new self(
            type: ApiFieldType::Timestamp,
            nullable: $nullable,
            description: $description,
        );
    }


    public static function phrase(
        bool $nullable,
        string $description = null,
    ): self
    {
        return new self(
            type: ApiFieldType::Phrase,
            nullable: $nullable,
            description: $description,
        );
    }

    public static function object(
        ApiModel|string $model_class,
        bool $nullable,
        string $description = null,
    ): self
    {
        return new self(
            type: ApiFieldType::Object,
            nullable: $nullable,
            model_class: $model_class,
            description: $description,
        );
    }

    /**
     * Массив без описания вложенных данных (использование нежелательно)
     */
    public static function array(
        string $description = null,
    ): self
    {
        return new self(
            type: ApiFieldType::Array,
            nullable: false,
            array_child_type: null,
            array_child_model_class: null,
            description: $description,
        );
    }

    /**
     * Массив данных скалярного типа
     */
    public static function arrayOfScalar(
        ApiFieldType $child_type,
        ApiFieldType|string $array_child_model_index_type = null,
        string $description = null,
    ): self
    {
        if (!$child_type->isScalar()) {
            throw new RuntimeException('Array child must be scalar');
        }

        return new self(
            type: ApiFieldType::Array,
            nullable: false,
            array_child_type: $child_type,
            array_child_model_class: null,
            array_child_model_index_type: $array_child_model_index_type,
            description: $description,
        );
    }

    /**
     * Массив объектов
     */
    public static function arrayOfObjects(
        ApiModel|string $child_model_class = null,
        ApiFieldType|string $array_child_model_index_type = null,
        string $description = null,
    ): self
    {
        return new self(
            type: ApiFieldType::Array,
            nullable: false,
            array_child_type: ApiFieldType::Object,
            array_child_model_class: $child_model_class,
            array_child_model_index_type: $array_child_model_index_type,
            description: $description,
        );
    }

    public static function scalar(
        ApiFieldType $type,
        bool $nullable,
        string $description = null,
    ): self {
        if (!$type->isScalar()) {
            throw new RuntimeException("Field type must be scalar, not {$type->name}");
        }

        return new self(
            type: $type,
            nullable: $nullable,
            description: $description,
        );
    }

    public function getJsType(): string
    {
        if ($this->type->isScalar()) {
            return $this->type->getJsType();
        }

        switch ($this->type) {
            case ApiFieldType::Array:
                if ($this->array_child_type !== null) {
                    if ($this->array_child_type->isScalar()) {
                        return "{$this->array_child_type->getJsType()}[]";
                    }

                    if ($this->array_child_type === ApiFieldType::Object) {
                        return "Api.{$this->array_child_model_class::getName()}[]";
                    }
                } else {
                    return 'Map';
                }
                break;

            case ApiFieldType::Object:
                return "Api.{$this->model_class::getName()}";
        }

        throw new RuntimeException("Unexpected API field type: {$this->type->value}");
    }

    public function getDartType(): string
    {
        if ($this->type->isScalar()) {
            return $this->type->getDartType();
        }

        if ($this->isMap()) {
            return sprintf(
                'Map<%s, %s>',
                $this->array_child_model_index_type->getDartType(),
                $this->array_child_type === ApiFieldType::Object
                    ? $this->array_child_model_class::getName()
                    : $this->array_child_type->getJsType(),
            );
        }

        switch ($this->type) {
            case ApiFieldType::Array:
                if ($this->array_child_type !== null) {
                    if ($this->array_child_model_class !== null) {
                        $array_child_model_class = $this->array_child_model_class::getName();
                        return "List<{$array_child_model_class}>";
                    } else {
                        return "List<{$this->array_child_type->getDartType()}>";
                    }
                } else {
                    return 'Object';
                }

            case ApiFieldType::Object:
                return $this->model_class::getName();

            default:
                throw new RuntimeException("Unexpected API field type: {$this->type->value}");
        }
    }

    public function isMap(): bool
    {
        return $this->array_child_model_index_type !== null;
    }
}
