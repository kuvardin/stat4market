<?php

declare(strict_types=1);

namespace App\Api\v1\Input;

use App\Api\v1\ApiSelectionData;
use App\Api\v1\ApiSelectionOptions;
use App\Api\v1\Exceptions\ApiException;
use App\Languages\Locale;
use App\Languages\Phrase;
use Kuvardin\DataFilter\DataFilter;
use Kuvardin\TinyOrm\Enums\SortDirection;
use Ramsey\Uuid\UuidInterface;
use RuntimeException;
use Ramsey\Uuid\Uuid;

class ApiInput
{
    /**
     * @var ApiParameter[]
     */
    protected array $parameters;

    protected array $data = [];

    public readonly ?ApiSelectionOptions $selection_options;

    protected ?ApiException $exception = null;

    /**
     * @param ApiParameter[] $parameters
     */
    public function __construct(
        array $parameters,
        array $input_data,
        Locale $language_code,
        ?ApiSelectionOptions $selection_options,
    )
    {
        $this->parameters = $parameters;

        $this->selection_options = $selection_options;

        $fields_with_errors = [];

        if ($input_data !== []) {
            $this->prepareData($input_data, $language_code);
        }

        ksort($this->data);

        foreach ($this->parameters as $parameter_name => $parameter) {
            if ($parameter->required_and_empty_error !== null && !isset($this->data[$parameter_name])) {
                if (!in_array($parameter_name, $fields_with_errors, true)) {
                    $this->addException($parameter_name, $parameter->required_and_empty_error);
                }
            }
        }
    }

    private function prepareData(array $input_data, Locale $language_code): void
    {
        foreach ($input_data as $input_data_key => $input_data_value) {
            if ($input_data_value === '') {
                $input_data_value = null;
            }

            $parameter = $this->parameters[$input_data_key] ?? null;
            if ($parameter !== null) {
                switch ($parameter->type) {
                    case ApiParameterType::Integer:
                        if (is_int($input_data_value)) {
                            $this->data[$input_data_key] = $input_data_value;
                        } elseif (is_string($input_data_value)) {
                            if ((string)(int)$input_data_value === $input_data_value) {
                                $this->data[$input_data_key] = (int)$input_data_value;
                            }
                        }

                        if ($input_data_value !== null && !isset($this->data[$input_data_key])) {
                            $fields_with_errors[] = $input_data_key;
                            $this->addException($input_data_key, 3025);
                        }
                        break;

                    case ApiParameterType::Enum:
                        if (is_string($input_data_value) || is_int($input_data_value)) {
                            $enum_item = $parameter->enum_class::tryFrom($input_data_value);
                            if ($enum_item === null) {
                                $fields_with_errors[] = $input_data_key;
                                $this->addException($input_data_key, 2016);
                            } else {
                                $this->data[$input_data_key] = $enum_item;
                            }
                        }
                        break;

                    case ApiParameterType::Boolean:
                        if (is_bool($input_data_value)) {
                            $this->data[$input_data_key] = $input_data_value;
                        } elseif (is_string($input_data_value)) {
                            if ($input_data_value === '0' || $input_data_value === '1') {
                                $this->data[$input_data_key] = $input_data_value === '1';
                            } else {
                                $input_data_value_lowercase = strtolower($input_data_value);
                                if (
                                    $input_data_value_lowercase === 'true' ||
                                    $input_data_value_lowercase === 'false'
                                ) {
                                    $this->data[$input_data_key] = $input_data_value_lowercase === 'true';
                                }
                            }
                        } elseif ($input_data_value === 0 || $input_data_value === 1) {
                            $this->data[$input_data_key] = $input_data_value === 1;
                        }
                        break;


                    case ApiParameterType::Uuid:
                        if (is_string($input_data_value) && Uuid::isValid($input_data_value)) {
                            $this->data[$input_data_key] = $input_data_value;
                        }

                        if ($input_data_value !== null && !isset($this->data[$input_data_key])) {
                            $fields_with_errors[] = $input_data_key;
                            $this->addException($input_data_key, 3026);
                        }
                        break;

                    case ApiParameterType::String:
                        if (is_string($input_data_value)) {
                            $input_data_value = DataFilter::getStringEmptyToNull($input_data_value, true);
                            if ($input_data_value !== null) {
                                $this->data[$input_data_key] = $input_data_value;
                            }
                        } elseif (is_int($input_data_value) || is_float($input_data_value)) {
                            $this->data[$input_data_key] = (string)$input_data_value;
                        }
                        break;

                    case ApiParameterType::Float:
                        if (is_float($input_data_value)) {
                            $this->data[$input_data_key] = $input_data_value;
                        } elseif (is_int($input_data_value)) {
                            $this->data[$input_data_key] = (float)$input_data_value;
                        } elseif (is_string($input_data_value) && is_numeric($input_data_value)) {
                            $this->data[$input_data_key] = (float)$input_data_value;
                        }

                        if (array_key_exists($input_data_key, $this->data)) {
                            if (
                                $parameter->float_min_value !== null
                                && $this->data[$input_data_key] < $parameter->float_min_value
                            ) {
                                $fields_with_errors[] = $input_data_key;
                                $this->addException($input_data_key, 3009);
                            }

                            if (
                                $parameter->float_max_value !== null
                                && $this->data[$input_data_key] > $parameter->float_max_value
                            ) {
                                $fields_with_errors[] = $input_data_key;
                                $this->addException($input_data_key, 3010);
                            }
                        }
                        break;

                    case ApiParameterType::Phrase:
                        if (is_array($input_data_value)) {
                            $phrase = null;

                            foreach ($input_data_value as $phrase_key => $phrase_value) {
                                $phrase_lang_code = Locale::tryFrom($phrase_key);
                                if (
                                    is_string($phrase_key)
                                    && $phrase_lang_code !== null
                                    && is_string($phrase_value)
                                ) {
                                    $phrase_value = DataFilter::getString($phrase_value, true, true);
                                    if ($phrase_value !== null) {
                                        if ($phrase === null) {
                                            $phrase = Phrase::make($phrase_lang_code, $phrase_value);
                                        } else {
                                            $phrase->setValue($phrase_lang_code, $phrase_value);
                                        }
                                    }
                                }
                            }

                            if ($phrase !== null) {
                                $this->data[$input_data_key] = $phrase;
                            }
                        } elseif (is_string($input_data_value)) {
                            $input_data_value = DataFilter::getStringEmptyToNull($input_data_value, true);
                            if ($input_data_value !== null) {
                                $this->data[$input_data_key] = Phrase::make($language_code, $input_data_value);
                            }
                        }
                        break;

                    case ApiParameterType::Array:
                        switch ($this->parameters[$input_data_key]->child_type) {
                            case ApiParameterType::Integer:
                                if (is_int($input_data_value)) {
                                    $this->data[$input_data_key] = [$input_data_value];
                                } elseif (is_string($input_data_value) || is_array($input_data_value)) {
                                    $result = [];
                                    $input_data_value_parts = is_string($input_data_value)
                                        ? explode(',', $input_data_value)
                                        : $input_data_value;

                                    foreach ($input_data_value_parts as $input_data_value_part) {
                                        $input_data_value_part = trim($input_data_value_part);
                                        if ((string)(int)$input_data_value_part === $input_data_value_part) {
                                            $result[] = (int)$input_data_value_part;
                                        } else {
                                            break 3;
                                        }
                                    }

                                    $this->data[$input_data_key] = $result === [] ? null : $result;
                                }
                                break;

                            case ApiParameterType::Uuid:
                                $uuids_values = [];
                                if (is_string($input_data_value)) {
                                    $uuids_values = explode(',', $input_data_value);
                                } elseif (is_array($input_data_value)) {
                                    $uuids_values = $input_data_value;
                                }

                                $result = [];
                                foreach ($uuids_values as $uuid_value) {
                                    if (!is_string($uuid_value) || !Uuid::isValid($uuid_value)) {
                                        $this->addException($input_data_key, 3014);
                                        break 3;
                                    }

                                    $result[] = $uuid_value;
                                }

                                $this->data[$input_data_key] = $result === [] ? null : $result;
                                break;

                            case ApiParameterType::String:
                                if (is_string($input_data_value)) {
                                    $this->data[$input_data_key] = [$input_data_value];
                                } elseif (is_int($input_data_value) || is_float($input_data_value)) {
                                    $this->data[$input_data_key] = [(string)$input_data_value];
                                } elseif (is_array($input_data_value)) {
                                    $result = [];

                                    foreach ($input_data_value as $input_data_value_part) {
                                        if (is_string($input_data_value_part)) {
                                            $result[] = trim($input_data_value_part);
                                        } elseif (
                                            is_int($input_data_value_part)
                                            || is_float($input_data_value_part)
                                        ) {
                                            $result[] = (string)$input_data_value_part;
                                        } else {
                                            break 3;
                                        }
                                    }

                                    $this->data[$input_data_key] = $result === [] ? null : $result;
                                }
                                break;
                        }
                        break;
                    case ApiParameterType::DateTime:
                        throw new \Exception('To be implemented');
                        break;
                    case ApiParameterType::Date:
                        throw new \Exception('To be implemented');
                }
            }
        }
    }

    public function getInt(string $name): ?int
    {
        return $this->getScalar($name, ApiParameterType::Integer);
    }

    public function requireInt(string $name): int
    {
        return $this->getScalar($name, ApiParameterType::Integer, true);
    }

    public function getFloat(string $name): ?float
    {
        return $this->getScalar($name, ApiParameterType::Float);
    }

    public function requireFloat(string $name): float
    {
        return $this->getScalar($name, ApiParameterType::Float, true);
    }

    public function getString(string $name): ?string
    {
        return $this->getScalar($name, ApiParameterType::String);
    }

    public function requireString(string $name): string
    {
        return $this->getScalar($name, ApiParameterType::String, true);
    }

    public function getUuid(string $name): ?UuidInterface
    {
        $uuid_value = $this->getScalar($name, ApiParameterType::Uuid);
        return $uuid_value === null ? null : Uuid::fromString($uuid_value);
    }

    public function requireUuid(string $name): UuidInterface
    {
        return Uuid::fromString($this->getScalar($name, ApiParameterType::Uuid, true));
    }

    public function getBool(string $name): ?bool
    {
        return $this->getScalar($name, ApiParameterType::Boolean);
    }

    public function requireBool(string $name): bool
    {
        return $this->getScalar($name, ApiParameterType::Boolean, true);
    }

    public function getPhrase(string $name): ?Phrase
    {
        return $this->getScalar($name, ApiParameterType::Phrase);
    }

    public function requirePhrase(string $name): Phrase
    {
        return $this->getScalar($name, ApiParameterType::Phrase, true);
    }

    protected function getScalar(string $name, ApiParameterType $type, bool $require = false): mixed
    {
        if (!array_key_exists($name, $this->parameters)) {
            throw new RuntimeException("Parameter with name $name not found");
        }

        if ($this->parameters[$name]->type !== $type) {
            throw new RuntimeException(sprintf(
                'Unable get parameter "%s" with type %s (type must be %s)',
                $name,
                $type->value,
                $this->parameters[$name]->type->value,
            ));
        }

        if ($require && !$this->parameters[$name]->isRequired()) {
            throw new RuntimeException("Unable require parameter \"$name\"");
        }

        return $this->data[$name] ?? null;
    }

    protected function getArray(string $name, ApiParameterType $child_type, bool $require = false): mixed
    {
        if (!array_key_exists($name, $this->parameters)) {
            throw new RuntimeException("Parameter with name $name not found");
        }

        if ($this->parameters[$name]->type !== ApiParameterType::Array) {
            throw new RuntimeException(
                "Unable get parameter \"$name\" with type Array " .
                "(type must be {$this->parameters[$name]->type->value})"
            );
        }

        if ($this->parameters[$name]->child_type !== $child_type) {
            throw new RuntimeException(
                "Unable get parameter \"$name\" with child type {$child_type->value} " .
                "(type must be {$this->parameters[$name]->child_type->value})"
            );
        }

        if ($require && !$this->parameters[$name]->isRequired()) {
            throw new RuntimeException("Unable require parameter \"$name\"");
        }

        return $this->data[$name] ?? null;
    }

    /**
     * @return int[]|null
     */
    public function getArrayOfInt(string $name): ?array
    {
        return $this->getArray($name, ApiParameterType::Integer);
    }

    /**
     * @return int[]
     */
    public function requireArrayOfInt(string $name): array
    {
        return $this->getArray($name, ApiParameterType::Integer, true);
    }

    /**
     * @return string[]|null
     */
    public function getArrayOfString(string $name): ?array
    {
        return $this->getArray($name, ApiParameterType::String);
    }

    /**
     * @return string[]
     */
    public function requireArrayOfString(string $name): array
    {
        return $this->getArray($name, ApiParameterType::String, true);
    }

    /**
     * @return string[]
     */
    public function requireArrayOfUuid(string $name): array
    {
        return $this->getArray($name, ApiParameterType::Uuid, true);
    }

    public function __toString(): string
    {
        return http_build_query($this->data);
    }

    public function requireSelectionData(int $total_amount): ApiSelectionData
    {
        if ($this->selection_options === null) {
            throw new RuntimeException('Selection options are empty');
        }

        $selection_data = new ApiSelectionData(
            limit_max: $this->selection_options->requireLimitMax(),
            sort_by_variants: $this->selection_options->getSortByVariants(),
            total_amount: $total_amount,
        );

        $limit = $this->getInt(ApiSelectionOptions::FIELD_LIMIT);
        if ($limit === null || $limit < 1 || $limit > $selection_data->limit_max) {
            $limit = $this->selection_options->requireLimitMax();
        }

        $selection_data->setLimit($limit);

        $sort_by_alias = $this->getString(ApiSelectionOptions::FIELD_SORT_BY);
        if ($sort_by_alias === null || $this->selection_options->getSortByVariant($sort_by_alias) === null) {
            $sort_by_alias = $this->selection_options->getSortByDefault();
        }

        $selection_data->setSortBy($this->selection_options->getSortByVariant($sort_by_alias));

        $sort_direction = null;
        $sort_direction_value = $this->getString(ApiSelectionOptions::FIELD_SORT_DIRECTION);
        if ($sort_direction_value !== null) {
            $sort_direction = SortDirection::tryFrom(strtoupper($sort_direction_value));
        }

        $selection_data->sort_direction = $sort_direction ?? $this->selection_options->getSortDirectionDefault();

        $page = $this->getInt(ApiSelectionOptions::FIELD_PAGE) ?? 1;
        $selection_data->setPage($page);

        return $selection_data;
    }

    public function addException(string $field_name, int $code): void
    {
        if (!array_key_exists($field_name, $this->parameters)) {
            throw new RuntimeException("API parameter with name $field_name not found");
        }

        $this->exception = ApiException::withField($code, $field_name, $this, $this->exception);
    }

    public function getException(): ?ApiException
    {
        return $this->exception;
    }

    public function hasParameter(string $parameter_name): bool
    {
        return array_key_exists($parameter_name, $this->parameters);
    }
}